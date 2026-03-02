# Architecture Research

**Domain:** Laravel package modernization (PHP/Laravel version upgrade + tooling migration)
**Researched:** 2026-03-02
**Confidence:** HIGH — codebase directly inspected; tool behaviors verified against official sources

---

## Standard Architecture

### System Overview: Modernization as a Pipeline

The modernization is not a feature build — it is a sequential transformation pipeline where each stage
produces a validated artifact consumed by the next stage. Skipping or reordering steps creates compounding
problems that are expensive to untangle.

```
┌──────────────────────────────────────────────────────────────────────┐
│  Stage 1: Dependency Contract                                         │
│  composer.json constraints → enforce PHP 8.2 / Laravel 11 minimums  │
└────────────────────────────────┬─────────────────────────────────────┘
                                 │ verified: composer install succeeds
                                 ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Stage 2: Structural Modernization                                    │
│  rector/rector + driftingly/rector-laravel → one-time apply pass     │
└────────────────────────────────┬─────────────────────────────────────┘
                                 │ verified: PHPUnit suite still green
                                 ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Stage 3: Test Framework Migration                                    │
│  PHPUnit → Pest 3 via pest-plugin-drift + manual cleanup             │
└────────────────────────────────┬─────────────────────────────────────┘
                                 │ verified: Pest suite green
                                 ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Stage 4: Test Coverage Expansion                                     │
│  New Pest tests: multi-workflow, subscriber events, error paths       │
└────────────────────────────────┬─────────────────────────────────────┘
                                 │ verified: all new tests pass
                                 ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Stage 5: CI Matrix Restructure                                       │
│  GitHub Actions: PHP [8.2, 8.3, 8.4] × Laravel [11.*, 12.*]         │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Component Boundaries

### What Changes, What Doesn't, and What Touches What

| Component | Files | Changes in modernization | Risk if touched carelessly |
|-----------|-------|--------------------------|---------------------------|
| **composer.json** | `composer.json` | PHP/Laravel minimums, Rector dev dep (added then removed), PHPUnit→Pest swap | Breaks CI if constraints wrong; downstream consumers need only update their constraint |
| **Rector config** | `rector.php` (new, then deleted) | Exists only during Stage 2; removed after apply | Permanent residue if not cleaned up — violates "apply-once" decision |
| **Source files** | `src/**/*.php` | Rector modernizes syntax/APIs; no logic changes | Rector should be dry-run-verified before apply; wrong ruleset breaks functionality |
| **PHPUnit config** | `phpunit.xml` | Replaced by `pest.config.php` / `tests/Pest.php` | phpunit.xml and Pest config must not coexist in ambiguous state during migration |
| **Test files (existing)** | `tests/TestCase.php`, `tests/Unit/UserUnitTest.php`, `tests/WorkflowSubscriberTest.php` | Converted to Pest syntax by drift + manual fixes | WorkflowSubscriberTest uses namespace-level global `event()` override — drift won't handle this correctly |
| **Test fixtures** | `tests/Fixtures/**` | Stay largely the same; `ConfigTrait` may gain typed properties | Fixtures are shared across tests; changes cascade to all tests |
| **GitHub Actions** | `.github/workflows/main.yml` | Matrix drops PHP 8.1/Laravel 10, adds PHP 8.4, updates `actions/checkout` and `setup-php` versions | Wrong matrix leaves dead CI combinations or misses PHP 8.4 |
| **Public API** | `HasWorkflowTrait`, events, interfaces | Zero changes — public API is frozen | Any accidental change here is a breaking change for consumers |

### Dependency Direction in the Source Architecture

```
config/workflow.php
    ↓
StateWorkflowServiceProvider  ←→  WorkflowRegistry
                                       ↓
                         Symfony DefinitionBuilder / Transition / Registry
                                       ↓
                              StateWorkflow (extends Symfony Workflow)
                              MethodMarkingStore (implements MarkingStoreInterface)
                                       ↓
                         WorkflowSubscriber (Symfony EventSubscriberInterface)
                                       ↓ (fires Laravel events via event())
                         WorkflowSubscriberHandler (abstract, Laravel Event)
                                       ↓ (extended by consumer)
                         UserEventSubscriber (test fixture)
                                       ↑
                              HasWorkflowTrait (mixin on Eloquent models)
                              StateWorkflowHistory (Eloquent, polymorphic)
```

This means: Rector changes to `src/` files flow downstream through all layers. A broken `WorkflowRegistry`
breaks everything. A broken `HasWorkflowTrait` breaks model integration and history persistence.

---

## Recommended Project Structure After Modernization

```
state-workflow/
├── .github/
│   └── workflows/
│       └── main.yml              # Updated CI matrix (PHP 8.2/8.3/8.4 × L11/L12)
├── config/
│   └── workflow.php              # Unchanged
├── database/
│   └── migrations/               # Unchanged
├── src/                          # Rector-modernized; no structural changes
│   ├── Console/Commands/
│   ├── Events/
│   ├── Interfaces/
│   ├── Models/
│   ├── Subscribers/
│   ├── Traits/
│   ├── Workflow/
│   ├── StateWorkflowServiceProvider.php
│   └── WorkflowRegistry.php
├── tests/
│   ├── Fixtures/                 # Unchanged structure; minor PHP 8.2 type hint additions
│   ├── Unit/
│   │   └── UserTest.php          # Renamed from UserUnitTest.php → Pest convention
│   ├── Pest.php                  # NEW: Pest bootstrap (uses() bindings)
│   ├── TestCase.php              # Converted to abstract class Pest uses
│   └── WorkflowSubscriberTest.php  # Converted; global event() override needs manual fix
├── composer.json                 # Updated constraints + Pest deps
└── pest.config.php               # OR phpunit.xml updated for Pest runner
```

---

## Architectural Patterns for Each Modernization Stage

### Pattern 1: Version Bump Before Any Code Changes

**What:** Update `composer.json` minimum constraints first, run `composer update`, verify the install
resolves cleanly before touching any source code.

**When to use:** Always — this is the correct order.

**Why:** If you run Rector before updating constraints, Rector may apply rules targeting an API version
that does not exist in the currently-installed packages. Rector reads the installed vendor tree to
determine available APIs.

**Example:**
```json
"require": {
    "php": "^8.2",
    "illuminate/events": "^11.0|^12.0",
    "illuminate/support": "^11.0|^12.0",
    "symfony/event-dispatcher": "^7.0",
    "symfony/workflow": "^7.0",
    "symfony/property-access": "^7.0"
}
```

**Trade-offs:** Dropping Laravel 10 / PHP 8.1 support in composer.json before Rector means Rector runs
against the correct installed version. Downside: no going back easily once lock file updates.

---

### Pattern 2: Rector Apply-Once with Dry-Run Gate

**What:** Install `rector/rector` and `driftingly/rector-laravel` as dev dependencies, create `rector.php`,
run `--dry-run` to inspect diffs, then apply, then remove both packages and the config file.

**When to use:** Package modernization where Rector is a migration tool, not a permanent linter.

**Configuration (`rector.php`):**
```php
<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])  // src/ only — not tests/
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ]);
```

**Critical scoping decision:** Apply Rector only to `src/` during the structural pass. Test files will be
migrated separately by `pest-plugin-drift`. Mixing both in one Rector pass creates conflicts — drift
produces Pest syntax, Rector expects PHPUnit structure.

**Dry-run workflow:**
```bash
vendor/bin/rector process --dry-run   # inspect diffs
vendor/bin/rector process             # apply
composer remove rector/rector driftingly/rector-laravel --dev
rm rector.php
```

**Trade-offs:** Apply-once keeps dev dependencies lean. Risk: Rector rules can be aggressive on edge
cases in `WorkflowRegistry.php` (which has Symfony class existence checks via class_exists() and
method_exists()). Verify these manually after apply.

---

### Pattern 3: Pest Migration — Drift First, Manual Fixes Second

**What:** Use `pest-plugin-drift` for mechanical conversion of PHPUnit test classes to Pest functions,
then fix the cases drift cannot handle automatically.

**Install sequence:**
```bash
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev
composer require orchestra/pest-plugin-testbench --dev
composer require pestphp/pest-plugin-drift --dev
vendor/bin/pest --drift
composer remove pestphp/pest-plugin-drift --dev  # remove after use
```

**What drift converts automatically:**
- `public function test_foo()` → `it('foo', function () { ... })`
- `$this->assertEquals(...)` → `expect(...)->toBe(...)`
- `$this->assertInstanceOf(...)` → `expect(...)->toBeInstanceOf(...)`
- `$this->assertCount(...)` → `expect(...)->toHaveCount(...)`
- `$this->assertTrue(...)` → `expect(...)->toBeTrue()`
- `$this->assertFalse(...)` → `expect(...)->toBeFalse()`

**What drift cannot handle (manual fixes required):**

1. **`WorkflowSubscriberTest.php` uses a namespace-level `global $events` hack** with a `function event()`
   override in the global namespace (`namespace {}`). This pattern breaks in Pest because Pest test files
   are not classes — the global function override must be moved to `tests/Fixtures/Helpers.php` (which
   already has a version of this guard) and the test rewritten to use Pest's `beforeEach()` + closure state.

2. **`TestCase.php` as abstract base class**: Pest uses `uses(TestCase::class)->in(...)` in `tests/Pest.php`
   rather than class inheritance. The abstract `TestCase` stays as-is (Pest wraps it), but the drift-converted
   tests will need `uses()` declarations pointing at it.

3. **`$this->user` references in test closures**: Drift converts `$this->user` but Pest closures are bound
   to `TestCase` so `$this` works — verify this actually resolves after drift, since `setUp()` logic in
   `TestCase` creates `$this->user`.

4. **`WithFaker` usage**: `$this->faker->...` works in bound closures but verify after migration.

**`tests/Pest.php` pattern for Orchestra Testbench packages:**
```php
<?php

use Ringierimu\StateWorkflow\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
```

**Trade-offs:** Drift handles ~80% of mechanical conversion. The `WorkflowSubscriberTest.php` global event
override is the highest-risk manual task. Failing to fix it produces either test isolation failures or
global function redeclaration errors across the test suite.

---

### Pattern 4: GitHub Actions Matrix Restructure

**What:** Replace the current matrix (`php: [8.1, 8.2, 8.3]`, `laravel: [10.*, 11.*, 12.*]`) with the new
minimum support target.

**New matrix:**
```yaml
strategy:
  fail-fast: false
  matrix:
    php: [8.2, 8.3, 8.4]
    laravel: [11.*, 12.*]
    dependency-version: [prefer-lowest, prefer-stable]
    include:
      - laravel: 11.*
        testbench: ^9.0
      - laravel: 12.*
        testbench: ^10.0
```

**Testbench version pinning:** The current `composer.json` has `orchestra/testbench: ^8.0|^9.15|^10`.
After dropping Laravel 10, tighten to `^9.0|^10.0`. In the CI matrix, pin testbench alongside laravel
using `matrix.include` so the right testbench version installs with the right Laravel version.

**Version compatibility (verified):**
| Laravel | Testbench | PHP minimum | Pest 3 |
|---------|-----------|-------------|--------|
| 11.x    | ^9.0      | 8.2         | yes    |
| 12.x    | ^10.0     | 8.2         | yes    |

**Updated install step:**
```yaml
- name: Install Composer dependencies
  run: |
    composer require \
      "laravel/framework:${{ matrix.laravel }}" \
      "orchestra/testbench:${{ matrix.testbench }}" \
      --no-interaction --no-update
    composer update --${{ matrix.dependency-version }} --prefer-dist --no-interaction
```

**Also update:**
- `actions/checkout@v3` → `actions/checkout@v4`
- `actions/cache@v3` → `actions/cache@v4`
- Test run command: `vendor/bin/phpunit` → `vendor/bin/pest`

---

## Data Flow: Modernization Operation Order

### Why This Order is Non-Negotiable

```
1. Bump composer.json constraints
        ↓  [gate: composer install succeeds]
2. Run rector on src/ only
        ↓  [gate: PHPUnit suite passes — proves src/ not broken]
3. Install Pest + drift, run --drift on tests/
        ↓  [gate: Pest suite passes with same assertions]
4. Remove PHPUnit config, install Pest.php bootstrap
        ↓  [gate: pest suite still passes]
5. Add new Pest tests (multi-workflow, subscriber, error paths)
        ↓  [gate: all new tests pass]
6. Update GitHub Actions matrix
        ↓  [gate: CI matrix green across all combinations]
7. Remove rector.php + rector dev deps (if not done in step 2)
        ↓
8. Update README / docs
```

**Why version bumps come first:**
Rector inspects installed vendor tree. Running Rector against Laravel 10 packages and then bumping to
Laravel 11 means some Rector transforms may have targeted wrong API signatures.

**Why Rector runs before Pest migration:**
PHPUnit is the current test harness. Running Rector first preserves a green PHPUnit suite as the safety net.
If Rector breaks something, PHPUnit catches it immediately. If you migrate to Pest first, you lose the
verified baseline before Rector runs.

**Why new tests come after migration (not before):**
Writing new Pest tests before the existing suite migrates means you're writing against a mixed
PHPUnit/Pest environment. Tests interact with the same fixtures — TestCase, ConfigTrait, User model.
Keep the environment stable first.

---

## Risk Points Where Things Could Break

### Risk 1: WorkflowRegistry `class_exists` / `method_exists` Compatibility Checks (HIGH)

**Location:** `src/WorkflowRegistry.php` lines 86-90
```php
$method = method_exists($this->registry, 'addWorkflow') ? 'addWorkflow' : 'add';
$strategyClass = class_exists(InstanceOfSupportStrategy::class)
    ? InstanceOfSupportStrategy::class
    : ClassInstanceSupportStrategy::class;
```
These checks guard against Symfony Workflow component version differences. With Symfony 7.x as the new
minimum (paired with Laravel 11), these ternaries should resolve to the modern branch every time.
Rector may simplify these to the non-ternary form if rules detect dead branches. **Verify the resulting
code still resolves correctly against the installed symfony/workflow version.**

### Risk 2: `WorkflowSubscriberTest.php` Global Event Override (HIGH)

**Location:** `tests/WorkflowSubscriberTest.php` lines 58-68
```php
namespace {
    $events = null;
    if (!function_exists('event')) {
        function event($ev) { global $events; $events[] = $ev; }
    }
}
```
This pattern (dual-namespace file + global function declaration) is PHPUnit-specific. Pest test files
execute in closures, not as class methods. The `global $events` array approach works in PHPUnit because
each test class is instantiated in isolation. In Pest, function redeclaration guards can fail if multiple
test files load in the same process with function caching.

**Resolution:** Move event capture to `beforeEach()` / `afterEach()` closure state using a reference,
or use a static variable. The `tests/Fixtures/Helpers.php` already has a guarded `event()` override —
reconcile these two definitions before Pest migration.

### Risk 3: Symfony Workflow Version Gap (MEDIUM)

**Current `composer.json`:** `"symfony/workflow": "^5.1"` (also `property-access: ^5.1`, `event-dispatcher: ^6.0|^7.0`)

Symfony 5.x is EOL. With Laravel 11 requiring Symfony 7.x components internally, this constraint is
already inconsistent. The modernization should align `symfony/workflow` and `symfony/property-access`
to `^7.0`. This is a source change risk: Symfony 7 workflow API is stable and backward-compatible for
the APIs used here (Definition, Transition, Registry, MarkingStoreInterface), but verify
`MethodMarkingStore` constructor signature hasn't changed.

### Risk 4: `mockery/mockery` Dev Dependency — Now Unused (LOW)

Mockery is listed in `require-dev` but no mock usage exists in the current 6-test suite. After Pest
migration, Pest's built-in `mock()` / expectation API may make Mockery redundant. Safe to remove when
cleaning up dev dependencies after migration; not a blocker.

### Risk 5: `funkjedi/composer-include-files` Plugin (LOW)

The `tests/Fixtures/Helpers.php` global `event()` function is loaded via this composer plugin. After
Pest migration, if the global override approach changes, this autoloading may no longer be needed.
The plugin must stay in `allow-plugins` until the helper's role is confirmed.

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: Rector on Tests Directory

**What people do:** Point `rector.php` `withPaths()` at `./` or `[src/, tests/]`.

**Why it's wrong:** Rector does not understand Pest syntax. Running it on Pest-converted tests produces
malformed output. Running it on PHPUnit tests before drift migration adds unnecessary churn — drift will
re-convert the same files.

**Do this instead:** Scope Rector strictly to `src/` for the structural pass.

---

### Anti-Pattern 2: Migrating Tests Before Establishing a Green Baseline

**What people do:** Jump straight to Pest migration without verifying PHPUnit passes on the updated
dependencies.

**Why it's wrong:** If `composer update` introduces an incompatibility (e.g., Symfony 7 API change in
`MethodMarkingStore`), you won't know whether failures are from the framework update or the test migration.

**Do this instead:** Run `vendor/bin/phpunit` after each stage. Green PHPUnit = safe to proceed to next stage.

---

### Anti-Pattern 3: Leaving `rector.php` in the Repository

**What people do:** Commit `rector.php` as a permanent fixture "for future use."

**Why it's wrong:** Project decision is apply-once. A committed `rector.php` invites re-running Rector
on already-modernized code, or applying incompatible future rulesets inadvertently.

**Do this instead:** Remove `rector.php` and the Rector dev dependencies in the same commit that applies
the Rector changes.

---

### Anti-Pattern 4: Skipping `prefer-lowest` in CI Matrix

**What people do:** Drop `dependency-version: [prefer-lowest, prefer-stable]` to reduce CI time.

**Why it's wrong:** `prefer-lowest` tests that the package works with the minimum versions declared in
`composer.json`, not just the latest. Dropping it means a Symfony 7.0.0 edge case could ship undetected.

**Do this instead:** Keep both dependency versions in the matrix. The matrix is already small (3 PHP ×
2 Laravel × 2 dep versions = 12 combinations).

---

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Symfony Workflow component | `StateWorkflow extends Symfony\Workflow\Workflow` | Version bump from ^5.1 to ^7.0 is the main risk point |
| Symfony EventDispatcher | `WorkflowSubscriber implements EventSubscriberInterface` | Already on ^6.0\|^7.0; lock to ^7.0 |
| Laravel Eloquent ORM | `StateWorkflowHistory` + `HasWorkflowTrait` | No changes needed; Eloquent API stable |
| Symfony PropertyAccess | `MethodMarkingStore` + `StateWorkflow::getState()` | Bump from ^5.1 to ^7.0; verify constructor API |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Rector ↔ src/ | One-time file transformation | Dry-run mandatory before apply |
| drift ↔ tests/ | One-time file transformation | Fixtures excluded from drift; only test classes |
| TestCase.php ↔ Pest.php | `uses(TestCase::class)` binding | TestCase stays as abstract class; Pest wraps it |
| Helpers.php ↔ WorkflowSubscriberTest | Global `event()` override | Must be reconciled before Pest migration |
| CI matrix ↔ composer.json constraints | Matrix laravel/testbench versions must align | Use `matrix.include` to pin testbench per Laravel version |

---

## Build Order Implications for Roadmap

Based on the dependency analysis above, the roadmap phases must reflect these hard sequencing constraints:

**Phase 1 (Dependency contract)** must complete before any other phase starts. It establishes the installed
environment that all other tools operate against.

**Phase 2 (Rector)** must complete and be verified green via PHPUnit before Phase 3 starts. PHPUnit is the
safety net for Rector's changes.

**Phase 3 (Pest migration)** and **Phase 4 (new tests)** are naturally sequential — a stable migrated suite
is the foundation for coverage expansion.

**Phase 5 (CI)** can be started in parallel with Phase 4 if desired, since CI matrix changes are
infrastructure — they don't block local test authoring. However, merge order should be: new tests land
before CI matrix changes are relied upon, so the matrix validates the expanded suite from day one.

**Documentation** (README) is a terminal node with no dependents — it can be deferred until all code
phases are complete.

---

## Sources

- Codebase inspection: `composer.json`, `phpunit.xml`, `src/**`, `tests/**`, `.github/workflows/main.yml` (2026-03-02)
- [driftingly/rector-laravel GitHub](https://github.com/driftingly/rector-laravel) — install, LaravelSetList/LaravelLevelSetList constants, dry-run workflow (MEDIUM confidence — verified against GitHub source)
- [Pest: Migrating from PHPUnit](https://pestphp.com/docs/migrating-from-phpunit-guide) — drift plugin, uses() binding, manual conversion cases (HIGH confidence — official docs)
- [pestphp/drift GitHub](https://github.com/pestphp/drift) — automatic conversion scope (MEDIUM confidence — official repo)
- [orchestra/pest-plugin-testbench Packagist](https://packagist.org/packages/orchestra/pest-plugin-testbench) — Testbench + Pest integration (HIGH confidence — official)
- [orchestral/testbench GitHub](https://github.com/orchestral/testbench) — version compatibility table (HIGH confidence — official)
- [packages.tools/testbench](https://packages.tools/testbench) — Testbench 9.x (L11) / 10.x (L12) compatibility (HIGH confidence — official)
- [Using GitHub Actions for Laravel packages — freek.dev](https://freek.dev/1546-using-github-actions-to-run-the-tests-of-laravel-projects-and-packages) — matrix include pattern for testbench pinning (MEDIUM confidence — community, widely cited)

---

*Architecture research for: Laravel package modernization (ringierimu/state-workflow)*
*Researched: 2026-03-02*
