# Pitfalls Research

**Domain:** Laravel package modernization — PHP/Laravel version drop, Rector one-time pass, PHPUnit-to-Pest migration, CI matrix rebuild
**Researched:** 2026-03-02
**Confidence:** MEDIUM-HIGH (codebase examined directly; Rector/Pest findings cross-referenced with official docs and community sources; Symfony API behaviour confirmed via source inspection of WorkflowRegistry.php)

---

## Critical Pitfalls

### Pitfall 1: Rector Mutates the Global `event()` Mock in WorkflowSubscriberTest

**What goes wrong:**
`WorkflowSubscriberTest.php` uses a dual-namespace trick: the test class lives inside `namespace Ringierimu\StateWorkflow\Tests`, but the `event()` mock is declared in the global `namespace {}` block at the bottom of the same file. Rector's `StringHelperToStringFacadeRector` and related helper-to-facade rules scan for bare function calls like `event()`, `config()`, and `str_*`. If any Rector rule rewrites the global `event()` mock declaration or the calls inside `WorkflowSubscriber.php` to a Facade call (`Event::dispatch()`), the test's interception mechanism silently breaks — the mock is no longer called, `$events` stays empty, and `assertCount(24, $events)` fails with a misleading count-of-zero error.

**Why it happens:**
The current codebase uses `event()` as a plain function in production code (`src/Subscribers/WorkflowSubscriber.php`) and shadows it with a global function redeclaration in tests. This is a legitimate PHP namespace fallback pattern. Rector does not know that the global function redeclaration is a test mock — it sees a helper function and may try to modernise it.

**How to avoid:**
- Before running Rector, add the test file to `withSkip()` in `rector.php`:
  ```php
  $rectorConfig->withSkip([
      __DIR__ . '/tests/WorkflowSubscriberTest.php',
  ]);
  ```
- Explicitly skip `StringHelperToStringFacadeRector` if it appears in any chosen set, or run Rector with `--dry-run` first and grep the diff for any change to `WorkflowSubscriberTest.php` or `WorkflowSubscriber.php` `event()` calls before committing.
- When migrating to Pest, replace this global-mock pattern with `Event::fake()` / `Event::assertDispatched()` so the fragile global redeclaration is removed entirely. This is the permanent fix.

**Warning signs:**
- Rector diff shows changes inside `tests/WorkflowSubscriberTest.php` or touches the `event(` call in `src/Subscribers/WorkflowSubscriber.php`.
- After Rector runs, `WorkflowSubscriberTest` reports `assertCount(24, $events)` failing with `0`.
- `grep -n 'function event' tests/WorkflowSubscriberTest.php` returns nothing after Rector runs.

**Phase to address:** Rector pass phase — configure `withSkip` for this file before executing Rector. Then permanently eliminate the pattern in the Pest migration phase.

---

### Pitfall 2: Overly-Broad Rector Set Selection Rewrites Public API Surface

**What goes wrong:**
`driftingly/rector-laravel` ships several opinionated set lists (`LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL`, `LARAVEL_CODE_QUALITY`, etc.) that modernise code beyond what the project requires. For a _package_ (not an application), rules that convert string helpers to Facade calls or rewrite config patterns can:
- Change publicly-visible method signatures or return types in traits that consumers depend on.
- Introduce Facade static calls inside `src/` that only resolve within a Laravel container, breaking standalone usage of the package's classes.
- Add return-type hints to methods overriding Eloquent `Authenticatable` (the fixture User model), causing fatal `Declaration of ... must be compatible` errors on PHP 8.2 strict mode.

**Why it happens:**
Developers reach for the most comprehensive set list to "get everything done in one pass." The project's `PROJECT.md` constraint is "apply once, then remove" — that pressure pushes toward broad sets. Package code has different safety requirements than application code.

**How to avoid:**
- Scope the rector.php `withPaths()` to `src/` only; exclude `tests/` and `database/migrations/`.
- Prefer targeted set lists: `LevelSetList::UP_TO_PHP_82` for the PHP upgrade portion; avoid application-oriented Laravel sets (`LARAVEL_CODE_QUALITY`, `LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL`) on package source.
- Run `vendor/bin/rector process --dry-run` and review every changed file before committing. Any change to a public method signature in `HasWorkflowTrait`, `StateWorkflow`, or `WorkflowRegistry` must be manually validated against the backwards-compatibility constraint.
- After the dry run, use `withSkip([RuleName::class => ['src/path/to/file.php']])` to exclude any rule that touches the public API.

**Warning signs:**
- Rector diff shows modifications to method signatures in `src/Traits/HasWorkflowTrait.php`, `src/Workflow/StateWorkflow.php`, or `src/WorkflowRegistry.php`.
- Type hint additions appear on methods that are extended by consumer models (anything in `HasWorkflowTrait`).
- A rule rewrites `event($ev)` calls in production source to `Event::dispatch($ev)`.

**Phase to address:** Rector pass phase — set selection and dry-run review.

---

### Pitfall 3: `InstanceOfSupportStrategy` Dual-Import Is a Symfony Version Time Bomb

**What goes wrong:**
`WorkflowRegistry.php` currently imports **both** `InstanceOfSupportStrategy` (Symfony 3.x–4.0) and `ClassInstanceSupportStrategy` (Symfony 4.1+) and picks the correct one at runtime via `class_exists()` (lines 87–89). This compatibility shim was written to support Symfony 5.x. When the composer constraint is tightened from `symfony/workflow: ^5.1` to a range that explicitly allows Symfony 7 (`^6.0|^7.0`), `InstanceOfSupportStrategy` no longer exists in the installed package — the import itself will cause an autoloader `ClassNotFoundException` on boot, before the `class_exists()` guard can execute.

Additionally, Symfony 7 requires PHP 8.2 minimum. If `symfony/workflow` is left at `^5.1`, Composer may resolve Symfony 5.x even when the platform is PHP 8.2, meaning the test matrix tests a Symfony version that will never be used in production after dropping PHP 8.1.

**Why it happens:**
The broad `^5.1` constraint was written when Symfony 6 and 7 did not exist. Developers updating PHP/Laravel minimums often forget to audit what Symfony version the constraint actually resolves to under `prefer-stable` vs `prefer-lowest`.

**How to avoid:**
- Update `symfony/workflow` and `symfony/property-access` constraints to `^6.0|^7.0` (dropping the `^5.1` range entirely, since Symfony 5 is EOL as of January 2024).
- Remove the `InstanceOfSupportStrategy` use statement and the `class_exists` guard in `WorkflowRegistry::registerWorkflow()`. Replace with the unconditional `ClassInstanceSupportStrategy` call.
- Verify the Symfony event dispatcher constraint (`symfony/event-dispatcher: ^6.0|^7.0`) stays aligned.
- Run `composer why symfony/workflow` in each CI matrix combination to confirm the resolved version.

**Warning signs:**
- `php artisan serve` or `composer install` throws `Class "Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy" not found` after updating constraints.
- `composer show symfony/workflow` in CI reports version 5.x installed even though PHP 8.2+ is targeted.
- A `prefer-lowest` CI run installs Symfony 6.0 while `prefer-stable` installs Symfony 7.x — and both execute different code paths in `registerWorkflow()` at runtime.

**Phase to address:** Dependency constraint update phase (composer.json update).

---

### Pitfall 4: PHPUnit `TestCase` Extension Is Not Automatically Carried Over in Pest Migration

**What goes wrong:**
The current `TestCase.php` extends `Orchestra\Testbench\TestCase` and sets up: service provider registration (`getPackageProviders()`), config injection (`getEnvironmentSetUp()`), and multi-directory migration loading (`defineDatabaseMigrations()`). When converting to Pest, the `uses()` declaration in `tests/Pest.php` must point at this _custom_ `TestCase`, not the bare Orchestra one. If `pest-plugin-drift` (the automated converter) generates `uses(\Orchestra\Testbench\TestCase::class)->in('.');`, the package-specific setup is silently skipped — the `workflow` config key is never set, `StateWorkflowServiceProvider` is never registered, and every test fails with `WorkflowRegistry` config-not-found or service-not-bound errors.

**Why it happens:**
`pest-plugin-drift` auto-generates `uses()` from the existing PHPUnit base class. If it detects that test files already extend `TestCase` (the package's custom one), it should be fine — but if any test file was not extending the custom class explicitly, drift defaults to the wrong base.

**How to avoid:**
- After running drift, open `tests/Pest.php` and verify `uses()` points at `Ringierimu\StateWorkflow\Tests\TestCase::class`:
  ```php
  uses(Ringierimu\StateWorkflow\Tests\TestCase::class)->in(__DIR__);
  ```
- Run the full test suite immediately after migration with `vendor/bin/pest` and confirm all 6 tests pass before adding any new tests.
- Do not split `uses()` into separate `->in('Unit')` and `->in('Feature')` calls until the base class wire-up is confirmed working across all directories.

**Warning signs:**
- Pest tests throw `Illuminate\Contracts\Container\BindingResolutionException` or `Target class [workflow] does not exist`.
- `$this->user` is null or undefined in test output.
- `vendor/bin/pest` reports 0 assertions even though tests appear to run.

**Phase to address:** Pest migration phase — first action after running drift is to verify `tests/Pest.php`.

---

### Pitfall 5: `faker()` vs `fake()` Rename Breaks Faker Usage Silently

**What goes wrong:**
The current `TestCase.php` uses `$this->faker->name` and `$this->faker->unique()->safeEmail` via the `WithFaker` trait (Orchestra Testbench inherits this from Laravel's testing traits). In Pest 3, the `faker()` helper function was renamed to `fake()`. If drift converts tests that call the old `faker()` function and the old Pest Faker plugin is still in `composer.json`, tests may run without error but generate wrong data or silently use an un-seeded faker instance.

**Why it happens:**
The rename happened between Pest 2 and Pest 3. Migration guides mention it, but `pest-plugin-drift` does not always catch all usages, especially when faker access goes through `$this->faker` (the PHPUnit property) rather than the standalone `faker()` function.

**How to avoid:**
- After migration, search for all `$this->faker` usages and replace with `fake()` (the Pest 3 function):
  ```bash
  grep -rn 'faker' tests/
  ```
- Remove `pestphp/pest-plugin-faker` from `composer.json` if present; the `fake()` helper is built into Pest 3.
- Update `TestCase.php` if it still uses the `WithFaker` trait — in Pest, this trait is unnecessary when using `fake()` directly.

**Warning signs:**
- `Call to undefined function faker()` errors in Pest output.
- Tests pass but user `email` or `name` fields are empty strings.
- `composer.json` still lists `pestphp/pest-plugin-faker` alongside Pest 3.

**Phase to address:** Pest migration phase — post-drift cleanup checklist.

---

### Pitfall 6: Global `event()` Namespace Mock Cannot Coexist with Pest's File-per-Test Execution Model

**What goes wrong:**
`WorkflowSubscriberTest.php` declares a global `event()` function using a bare `namespace {}` block with an `if (!function_exists('event'))` guard. PHPUnit loads all test files into the same PHP process, so this global function is declared once and reused. Pest's architecture is identical in this respect — but if any future test file in a different namespace also tries to declare or use `event()`, the `function_exists` guard means the _first-loaded_ declaration wins. In a project that grows from 6 tests to 20+, this creates unpredictable test ordering dependencies.

More immediately: if `pestphp/pest` initialises its own autoloading and any Pest plugin internally calls Laravel's `event()` helper before the test file's global mock is parsed, the guard fails and the mock is never registered.

**Why it happens:**
The global function redeclaration pattern was a PHPUnit-era workaround for the lack of injectable event dispatcher in `WorkflowSubscriber`. It was acceptable with 6 tests; it becomes fragile at any larger scale.

**How to avoid:**
- This pattern _must_ be replaced as part of the Pest migration, not deferred. The correct Pest/Laravel approach is:
  ```php
  Event::fake();
  // ... run transition ...
  Event::assertDispatched('workflow.guard');
  ```
- Inject `Illuminate\Contracts\Events\Dispatcher` into `WorkflowSubscriber` rather than calling `event()` directly. This makes the subscriber testable without global mocking.
- Remove the `namespace {}` block from `WorkflowSubscriberTest.php` entirely once `Event::fake()` is in place.

**Warning signs:**
- Tests produce inconsistent results depending on execution order (`vendor/bin/pest --order=random` fails).
- Adding a second test file that uses `Event::fake()` causes the existing `WorkflowSubscriberTest` assertions to fail.
- `assertCount(24, $events)` passes in isolation but fails when the full suite runs.

**Phase to address:** Pest migration phase — this is a blocker, not optional cleanup.

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Keep `symfony/workflow: ^5.1` constraint | No migration work | Composer may pin Symfony 5 (EOL) even on PHP 8.2; CI matrix misrepresents production | Never — Symfony 5 is EOL Jan 2024 |
| Skip Rector dry-run review, just commit output | Faster Rector pass | Silent API breakage in trait methods; `InstanceOfSupportStrategy` import removed incorrectly | Never on a package with backwards-compat constraint |
| Use `class_exists()` shim for Symfony API differences | Supports multiple Symfony versions | Both imports present; newer Symfony throws autoload error before guard fires | Never after dropping Symfony 5 support |
| Leave global `event()` mock in Pest tests | No refactoring needed | Non-deterministic ordering bugs; blocks `Event::fake()` adoption; fragile with Pest plugins | Never — replace in Pest migration phase |
| Run Rector on all directories including `tests/` | One command covers everything | Rector mutates the namespace-based event mock; fixture migrations altered unexpectedly | Never — always scope to `src/` only |
| Copy existing CI matrix and just swap PHP 8.1 → 8.2 | Quick update | Testbench version not updated to match new Laravel minimum; `prefer-lowest` resolves wrong Orchestra version | Never — testbench versions must be mapped explicitly |

---

## Integration Gotchas

Common mistakes when connecting to external services or frameworks.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| `driftingly/rector-laravel` | Installing as a regular `require-dev` dependency and forgetting to remove it | Install, run, verify diff, commit, then `composer remove driftingly/rector-laravel rector/rector` — the `PROJECT.md` constraint is apply-once |
| Orchestra Testbench 10.x | Assuming `^10` works with both Laravel 11 and 12 | Testbench 10.x targets Laravel 12; Testbench 9.x targets Laravel 11. Constraint must be `^9.0\|^10.0` |
| `symfony/event-dispatcher` | Leaving constraint at `^6.0\|^7.0` while `symfony/workflow` is widened | Both constraints must align — Symfony packages should always share the same major version range |
| Pest 3 + `pestphp/pest-plugin-drift` | Running drift then immediately running Pest without verifying `tests/Pest.php` | Validate `uses()` target class before running any test |
| GitHub Actions `actions/cache@v3` | Cache key uses `composer.json` hash; after Rector pass, `rector.php` is removed from `require-dev` and the cache is stale | After changing `composer.json`, clear caches manually or use `composer.lock` as cache key |

---

## Performance Traps

Not applicable at the scale of this modernisation project — no performance regressions are introduced by the planned changes. Performance concerns (reflection caching, workflow instance caching) are explicitly out of scope per `PROJECT.md`.

---

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Rector pass complete:** Verify `InstanceOfSupportStrategy` use statement is removed from `WorkflowRegistry.php` and the `class_exists` shim is gone — not just that Rector ran without errors.
- [ ] **Pest migration complete:** Verify `vendor/bin/pest` (not `vendor/bin/phpunit`) runs and reports all 6 legacy tests passing before writing new tests.
- [ ] **Dependency constraints updated:** Verify `composer show symfony/workflow` in CI reports 6.x or 7.x — not 5.x — after dropping PHP 8.1.
- [ ] **CI matrix updated:** Verify the matrix has `php: [8.2, 8.3, 8.4]` and `laravel: [11.*, 12.*]` with correct testbench version mapping — not just that the old `8.1` and `10.*` entries are removed.
- [ ] **`prefer-lowest` run validates:** Run `composer update --prefer-lowest --prefer-stable` locally and confirm tests still pass — this catches cases where the minimum specified version is incompatible with the new PHP floor.
- [ ] **Global `event()` mock removed:** Confirm `grep -rn 'function event' tests/` returns nothing in the final Pest test suite.
- [ ] **`fake()` not `faker()`:** Confirm `grep -rn '\$this->faker\|faker()' tests/` returns nothing after Pest migration.
- [ ] **Rector removed from dev deps:** Confirm `composer show driftingly/rector-laravel` returns "not found" before merging the modernisation branch.

---

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Rector corrupts `WorkflowSubscriberTest.php` | LOW | `git checkout tests/WorkflowSubscriberTest.php`; add file to Rector skip list; re-run |
| Rector breaks public trait method signatures | MEDIUM | `git diff HEAD~1 src/Traits/HasWorkflowTrait.php`; identify changed signatures; manually revert each to preserve backwards compat; add specific rules to `withSkip()` |
| `InstanceOfSupportStrategy` autoload crash on boot | LOW | Add `symfony/workflow: ^6.0\|^7.0` constraint; remove both use statements; unconditionally use `ClassInstanceSupportStrategy`; run tests |
| Pest `uses()` points at wrong base class | LOW | Edit `tests/Pest.php`; set correct `uses(\Ringierimu\StateWorkflow\Tests\TestCase::class)`; re-run pest |
| Global `event()` mock produces non-deterministic results | HIGH | Must refactor `WorkflowSubscriber` to accept injected dispatcher; replace test with `Event::fake()`; this is a multi-file change |
| CI matrix installs wrong Testbench version | LOW | Update matrix `include:` block to map `laravel: 11.*` → `testbench: ^9.0` and `laravel: 12.*` → `testbench: ^10.0`; re-run CI |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Rector mutates global `event()` mock | Rector pass — add skip before running | `git diff tests/WorkflowSubscriberTest.php` shows no changes |
| Rector rewrites public API surface | Rector pass — dry-run review | `git diff src/Traits/HasWorkflowTrait.php` shows no signature changes |
| `InstanceOfSupportStrategy` autoload crash | Dependency constraint update | `composer show symfony/workflow` shows 6.x or 7.x; boot smoke test passes |
| Pest `uses()` wrong base class | Pest migration — first verification step | `vendor/bin/pest` shows 6 tests passing with correct assertions |
| `faker()` rename | Pest migration — post-drift cleanup | `grep -rn 'faker()' tests/` returns nothing |
| Global `event()` mock ordering bug | Pest migration — treated as blocker | `vendor/bin/pest --order=random` passes consistently |
| CI matrix Testbench version mismatch | CI matrix update phase | All matrix combinations green; `prefer-lowest` job passes |
| Symfony 5 pinned via broad constraint | Dependency constraint update | CI `prefer-lowest` job shows Symfony 6.x minimum installed |

---

## Sources

- Codebase direct inspection: `/src/WorkflowRegistry.php` lines 19–20, 86–90 (dual Symfony import shim)
- Codebase direct inspection: `/tests/WorkflowSubscriberTest.php` lines 58–68 (global `event()` namespace mock)
- Codebase direct inspection: `/composer.json` (current constraints: `symfony/workflow: ^5.1`, `orchestra/testbench: ^8.0|^9.15|^10`)
- Codebase direct inspection: `/.github/workflows/main.yml` (current matrix: PHP 8.1/8.2/8.3, Laravel 10/11/12)
- [Rector: 5 Common Mistakes in Rector Config](https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them) — run on own code only; add one set at a time; exclude generated files
- [Rector: Ignoring Rules or Paths](https://getrector.com/documentation/ignoring-rules-or-paths) — `withSkip()` API for file/rule exclusions
- [Rector: `ReadOnlyPropertyRector` ignores clones on PHP 8.1 — GitHub Issue #6898](https://github.com/rectorphp/rector/issues/6898) — known false positive with readonly properties
- [Rector: Parent class return type addition breaks code — GitHub Issue #8557](https://github.com/rectorphp/rector/issues/8557) — return type inference can break child class compatibility
- [Pest: Migrating from PHPUnit](https://pestphp.com/docs/migrating-from-phpunit-guide) — `uses()` setup, DocBlock annotations, Faker rename
- [Pest: Configuring Tests](https://pestphp.com/docs/configuring-tests) — `tests/Pest.php` setup with `uses()->in()`
- [Orchestra Testbench: Define Databases](https://packages.tools/testbench/the-basic/databases) — `defineDatabaseMigrations()` pattern for packages
- [Orchestra Testbench: RefreshDatabase migration ordering issue — GitHub Issue #206](https://github.com/orchestral/testbench/issues/206) — package migrations running before Laravel migrations
- [Symfony: `InstanceOfSupportStrategy` deprecated in 4.1, `ClassInstanceSupportStrategy` is canonical](https://github.com/symfony/symfony/blob/4.1/UPGRADE-4.1.md)
- [driftingly/rector-laravel: Rule overview](https://github.com/driftingly/rector-laravel/blob/main/docs/rector_rules_overview.md) — `LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL` and similar opinionated sets
- [freek.dev: GitHub Actions for Laravel packages](https://freek.dev/1546-using-github-actions-to-run-the-tests-of-laravel-projects-and-packages) — testbench version mapping in CI matrix `include:` blocks
- [dereuromark/composer-prefer-lowest](https://github.com/dereuromark/composer-prefer-lowest) — validates minimum version declarations match actual resolvable versions
- [tomasvotruba.com: How adding Type Declarations makes Your Code Dangerous](https://tomasvotruba.com/blog/how-adding-type-declarations-makes-your-code-dangerous) — strict_types side effects with Eloquent magic methods

---

*Pitfalls research for: ringierimu/state-workflow Laravel package modernization*
*Researched: 2026-03-02*
