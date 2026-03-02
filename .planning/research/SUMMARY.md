# Project Research Summary

**Project:** ringierimu/state-workflow
**Domain:** Laravel package modernization (PHP/Laravel version upgrade + tooling migration)
**Researched:** 2026-03-02
**Confidence:** HIGH

## Executive Summary

This is a Laravel package modernization project, not a greenfield build. The `ringierimu/state-workflow` package wraps Symfony Workflow as a Laravel-native integration, and its current state (PHP 8.1 minimum, PHPUnit, StyleCI, EOL Laravel/Symfony constraints) is measurably behind the 2026 ecosystem baseline. The recommended approach is a sequential pipeline: update version constraints first, apply Rector to modernize source syntax, migrate the test suite from PHPUnit to Pest 3, expand test coverage, then rebuild the CI matrix. This ordering is non-negotiable — each stage creates the stable foundation the next stage depends on, and skipping steps creates failures that are expensive to attribute and untangle.

The primary risks are concentrated in two files: `WorkflowRegistry.php` (which contains a Symfony version-compatibility shim that will crash on boot once Symfony 5 is dropped) and `WorkflowSubscriberTest.php` (which uses a fragile global function override that is incompatible with Pest's execution model). Both require deliberate intervention — the registry shim must be removed during the dependency constraint phase, and the global event mock must be replaced with `Event::fake()` during the Pest migration phase. Every other task in the modernization is mechanical and low-risk.

The gap between the current package and the Spatie skeleton baseline (the de-facto reference for modern Laravel open-source packages) defines the full scope of work. The baseline is achievable in a single focused branch. Post-modernization quality layers (PHPStan/Larastan, Pint, architecture tests, type coverage enforcement, Dependabot) are well-understood P2 additions that can be appended once the core upgrade is green.

---

## Key Findings

### Recommended Stack

The modernization targets PHP `^8.2` and Laravel `^11.0|^12.0`. PHP 8.1 and Laravel 10 both reached EOL in 2024–2025 and must be dropped. The test framework migrates from PHPUnit to Pest 3 (`^3.7`) — critically, **not Pest 4**, which requires PHP 8.3+. Rector (`^2.0`) and `driftingly/rector-laravel` (`^2.0`) handle the one-time source modernization pass; both are removed after application. The Symfony dependency constraints must be updated from `^5.1` to `^7.0` to align with what Laravel 11 actually requires internally.

**Core technologies:**
- PHP `^8.2`: minimum runtime — PHP 8.1 EOL Dec 2024; 8.2 enables readonly classes, typed properties, and modern syntax
- Laravel `^11.0|^12.0`: both actively supported; Laravel 10 EOL Feb 2025
- Orchestra Testbench `^9.0|^10.0`: v9 for Laravel 11, v10 for Laravel 12 — must be dual-constrained
- Pest `^3.7`: the correct choice for PHP 8.2 minimum (Pest 4 requires PHP 8.3+)
- `rector/rector ^2.0` + `driftingly/rector-laravel ^2.0`: apply-once modernization tools; remove after use
- `symfony/workflow ^7.0`: must align to Symfony 7 — current `^5.1` is EOL and will cause autoloader crashes

**Key removals:**
- `funkjedi/composer-include-files` — replaced by Pest.php bootstrapping
- `minimum-stability: dev` — must become `stable` for a released library
- PHPUnit direct dependency — Pest 3 manages PHPUnit 11 as a transitive dependency

### Expected Features

**Must have (table stakes — P1):**
- PHP 8.2 minimum + Laravel 11 minimum in `composer.json` — foundational; everything depends on this
- Rector modernization pass applied: typed properties, return types, parameter types, PHP 8.2 idioms
- PHPUnit tests migrated to Pest 3 — all 6 existing tests converted and passing
- Expanded Pest tests: multiple workflows per model, subscriber event paths, error paths (identified gap in PROJECT.md)
- Updated CI matrix: PHP `[8.2, 8.3, 8.4]` x Laravel `[11.*, 12.*]` with correct testbench version mapping
- Updated README: version table, badges, installation instructions reflecting current state

**Should have (competitive — P2, add after P1 lands):**
- Laravel Pint replacing StyleCI — first-party, free, runs locally and in CI
- PHPStan/Larastan integration with baseline — easiest to add on top of a clean Rector-processed codebase
- Pest architecture tests — ~10 lines; catches structural regressions permanently
- Pest type coverage CI check (`--type-coverage --min=100`) — enforces typing discipline Rector established
- Dependabot for composer — pure addition, no risk

**Defer (v2+):**
- PHP 8.3 typed constants, PHP 8.4 property hooks on `HasWorkflowTrait`
- Mutation testing in CI (slow; invest after suite is comprehensive)
- New workflow features (async transitions, config validation) — out of scope for modernization

### Architecture Approach

The modernization is a **sequential transformation pipeline** where each stage produces a verified artifact consumed by the next. The stages are: (1) dependency contract update, (2) Rector structural modernization, (3) Pest migration, (4) test coverage expansion, (5) CI matrix rebuild. The public API — `HasWorkflowTrait`, events, interfaces — is frozen throughout; zero breaking changes to consumers. The two highest-risk components are `WorkflowRegistry.php` (Symfony shim removal) and `WorkflowSubscriberTest.php` (global event mock replacement), both of which require manual intervention that automated tools cannot safely handle.

**Major pipeline stages:**
1. **Dependency contract** — `composer.json` constraints updated; `composer install` verified clean before any source changes
2. **Rector pass on `src/` only** — automated syntax modernization; PHPUnit suite used as green-baseline gate
3. **Pest migration on `tests/` only** — drift-assisted conversion + manual fixes for global event mock; `tests/Pest.php` bootstrap verified
4. **Test coverage expansion** — new Pest tests for multi-workflow, subscriber events, error paths
5. **CI matrix rebuild** — GitHub Actions matrix updated with correct PHP/Laravel/testbench version mapping

### Critical Pitfalls

1. **`InstanceOfSupportStrategy` autoloader crash** — `WorkflowRegistry.php` imports a Symfony 3.x class alongside the modern `ClassInstanceSupportStrategy`. When `symfony/workflow` is updated to `^7.0`, the import causes a `ClassNotFoundException` before any `class_exists()` guard can fire. **Prevention:** Remove both use statements and the class_exists shim in the dependency update phase; use only `ClassInstanceSupportStrategy` unconditionally.

2. **Rector mutates the global `event()` mock** — `WorkflowSubscriberTest.php` uses a dual-namespace global function override that Rector may rewrite. If the mock is altered, `assertCount(24, $events)` silently returns 0. **Prevention:** Add `tests/WorkflowSubscriberTest.php` to `withSkip()` in `rector.php` before running; replace pattern with `Event::fake()` in Pest migration.

3. **Global `event()` mock incompatible with Pest execution model** — The `namespace {}` global function trick is a blocker for Pest migration, not optional cleanup. It creates non-deterministic ordering bugs at scale. **Prevention:** Replace with `Event::fake()` / `Event::assertDispatched()` during Pest migration phase; this is a required refactor, not deferred.

4. **Rector on `tests/` directory** — Running Rector across both `src/` and `tests/` conflicts with drift-based Pest conversion and may malform test files. **Prevention:** Scope `rector.php` `withPaths()` strictly to `src/` only.

5. **Pest `uses()` pointing at wrong base class** — If `tests/Pest.php` targets `Orchestra\Testbench\TestCase` instead of the package's custom `TestCase`, service provider registration and config injection are silently skipped, causing binding resolution failures. **Prevention:** Verify `uses(Ringierimu\StateWorkflow\Tests\TestCase::class)->in(__DIR__)` immediately after running drift; run full suite before adding any new tests.

---

## Implications for Roadmap

Based on research, the architecture imposes a strict sequential dependency chain. Phases cannot be reordered without creating compounding attribution problems. The suggested structure maps directly to the five pipeline stages identified in ARCHITECTURE.md.

### Phase 1: Dependency Contract
**Rationale:** Composer constraints must be updated before any other tool runs. Rector reads the installed vendor tree to determine available APIs — running it against Laravel 10 packages then bumping to Laravel 11 means some transforms target wrong API signatures. This is the only phase with zero prerequisites.
**Delivers:** Clean `composer install` resolving PHP 8.2, Laravel 11/12, Symfony 7, Pest 3 dev stack — with the Symfony 5 autoloader crash defused.
**Addresses:** PHP 8.2 minimum, Laravel 11 minimum, up-to-date `composer.json` constraints (all P1 table stakes)
**Avoids:** `InstanceOfSupportStrategy` autoloader crash (Pitfall 3); Rector targeting wrong installed APIs (Pitfall 2); `minimum-stability: dev` left in released library

### Phase 2: Rector Modernization Pass
**Rationale:** PHPUnit is the safety net for Rector's changes. Running Rector while PHPUnit is still the test runner means any breakage is immediately detectable and attributable to Rector alone. Once PHPUnit is removed, this safety net is gone.
**Delivers:** Source files in `src/` modernized to PHP 8.2 idioms — typed properties, return types, parameter types; Rector and its config removed from the repository.
**Addresses:** Full return type declarations, parameter type declarations, typed class properties (P1 table stakes)
**Avoids:** Rector on `tests/` directory (Anti-Pattern 1); public API surface rewrite from overly-broad set selection (Pitfall 2); `rector.php` left as permanent fixture (Anti-Pattern 3)
**Gate:** PHPUnit suite must be green after Rector apply before proceeding.

### Phase 3: Pest Migration
**Rationale:** Drift handles ~80% of mechanical conversion; the remaining 20% requires targeted manual work on two known problem areas (`WorkflowSubscriberTest.php` global mock, `Pest.php` base class verification). This phase is the highest-complexity task in the modernization and must produce a fully green Pest suite before expansion.
**Delivers:** All 6 existing tests running under Pest 3; PHPUnit removed; `tests/Pest.php` bootstrap verified; global event mock replaced with `Event::fake()`.
**Addresses:** Pest 3.x as test framework (P1 table stakes)
**Avoids:** Global event mock ordering bugs (Pitfall 6, blocker); wrong base class in `uses()` (Pitfall 4); `faker()` vs `fake()` rename (Pitfall 5)
**Gate:** `vendor/bin/pest` reports all 6 tests passing; `vendor/bin/pest --order=random` passes consistently.

### Phase 4: Test Coverage Expansion
**Rationale:** New tests must be written in Pest, and must be written on top of a stable migrated suite. Writing Pest tests before migration is complete means working against a mixed environment where fixtures, TestCase, and helpers are in flux.
**Delivers:** Expanded test suite covering multiple workflows per model, subscriber event dispatching, error paths, and edge cases identified in PROJECT.md.
**Addresses:** Expanded test coverage for edge cases (P1, explicitly required in PROJECT.md)
**Avoids:** Writing tests before migration is stable (Anti-Pattern 2)

### Phase 5: CI Matrix Rebuild
**Rationale:** CI is infrastructure — it validates what's already working locally. It should be updated after the expanded test suite is in place so the matrix validates the full scope of tests from day one, not just the legacy 6.
**Delivers:** GitHub Actions matrix running PHP `[8.2, 8.3, 8.4]` x Laravel `[11.*, 12.*]` x `[prefer-lowest, prefer-stable]` with correct testbench version pinning via `matrix.include`.
**Addresses:** Updated CI matrix (P1 table stakes)
**Avoids:** Testbench version mismatch (Integration Gotcha); `prefer-lowest` omission hiding minimum-version incompatibilities (Anti-Pattern 4)

### Phase 6: Documentation and P2 Enhancements
**Rationale:** README update and P2 tooling (Pint, PHPStan, architecture tests, type coverage, Dependabot) are terminal nodes with no downstream dependents. They can be applied in any order once the core upgrade is green. PHPStan is most valuable added after Rector, since baseline is cleanest on already-typed code.
**Delivers:** README with correct version badges and installation instructions; Laravel Pint replacing StyleCI; PHPStan/Larastan baseline; Pest architecture tests; Dependabot config.
**Addresses:** All P2 features (Pint, PHPStan, arch tests, type coverage, Dependabot); README update (P1)

### Phase Ordering Rationale

- Phases 1–5 are strictly sequential: each phase's output is a required input for the next phase's tooling to operate correctly.
- Phase 6 is the only phase that can be partially parallelized (README, Dependabot, and Pint are independent of each other).
- The pipeline order matches the risk profile: highest-consequence operations (Symfony constraint change, Rector) run first when the most safety nets exist; lowest-consequence operations (docs, config files) run last.
- The `prefer-lowest` CI job in Phase 5 provides retrospective validation of minimum version declarations established in Phase 1.

### Research Flags

Phases with well-documented patterns (research phase not needed):
- **Phase 1 (Dependency contract):** Constraint syntax and testbench version mapping are fully documented and verified.
- **Phase 2 (Rector):** Rector configuration, ruleset selection, and dry-run workflow are documented in STACK.md with exact config.
- **Phase 5 (CI matrix):** GitHub Actions YAML with testbench matrix.include pattern is documented in STACK.md with working example.
- **Phase 6 (P2 enhancements):** Pint, PHPStan, Dependabot all follow established patterns from Spatie skeleton.

Phases likely needing closer attention during execution:
- **Phase 3 (Pest migration):** The `WorkflowSubscriberTest.php` global mock replacement requires careful manual refactoring. The drift tool handles 80% mechanically but this file needs bespoke attention. No additional research needed — the problem and solution are fully documented in PITFALLS.md and ARCHITECTURE.md.
- **Phase 4 (Test expansion):** The specific test scenarios for multi-workflow models and subscriber error paths require domain knowledge of the package's behavior. Consider reviewing `src/Subscribers/WorkflowSubscriber.php` event dispatch logic to enumerate all cases before writing tests.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Pest 4 PHP requirement confirmed via official pestphp.com docs. Testbench v9/v10 versioning confirmed via official packages.tools. All version constraints cross-referenced across multiple sources. |
| Features | HIGH | Verified against Pest 3 official docs, Spatie skeleton (current state), and Laravel official docs. Feature dependency graph derived from actual tool behavior, not inference. |
| Architecture | HIGH | Based on direct codebase inspection of all source files, test files, and CI config. Pipeline ordering validated against tool documentation (Rector vendor-tree dependency, Pest migration guide). |
| Pitfalls | HIGH | All critical pitfalls derived from direct codebase inspection — the problematic code patterns are present and identified by file and line number. Symfony shim and global event mock are confirmed existing issues, not theoretical risks. |

**Overall confidence:** HIGH

### Gaps to Address

- **Symfony 7 `MethodMarkingStore` constructor API:** PITFALLS.md flags this as a potential breaking change when upgrading from `symfony/workflow ^5.1` to `^7.0`. The Symfony 7 marking store API is stable, but the constructor signature for `MethodMarkingStore` should be spot-checked against `src/Workflow/MethodMarkingStore.php` at the start of Phase 1 before committing to the constraint update. Resolution: verify during Phase 1 execution, not pre-planning.

- **`mockery/mockery` removal decision:** Listed in `require-dev` but no mock usage found in the 6-test suite. Decision to remove is straightforward after Pest migration, but should be confirmed by checking the converted test suite for any Mockery-specific syntax drift may have missed. Resolution: add as a checklist item at the end of Phase 3.

- **`prefer-lowest` testbench edge case:** Architecture research recommends including `prefer-lowest` in the CI matrix; STACK.md's reference CI YAML omits it intentionally for simplicity. The 12-combination matrix (`prefer-lowest` + `prefer-stable`) is recommended in PITFALLS.md. Roadmapper should choose one approach. Recommendation: include both — the matrix is small and the safety value is high for a package with newly-tightened minimum constraints.

---

## Sources

### Primary (HIGH confidence)
- Official Pest docs (pestphp.com) — Pest 3 features, Pest 4 PHP 8.3 requirement, drift migration guide, `uses()` configuration
- Official Orchestra Testbench docs (packages.tools/testbench) — v9/v10 Laravel version mapping
- Packagist listings for `orchestra/testbench`, `pestphp/pest`, `rector/rector` — verified version constraints
- Direct codebase inspection: `composer.json`, `phpunit.xml`, `src/**`, `tests/**`, `.github/workflows/main.yml`

### Secondary (MEDIUM confidence)
- driftingly/rector-laravel GitHub — `LaravelLevelSetList` and `LaravelSetList` constants verified against source
- Spatie `package-skeleton-laravel` — reference baseline for modern package CI, testing, and tooling patterns
- getrector.com documentation — `withSkip()` API, dry-run workflow, common mistakes
- freek.dev — testbench version mapping in CI matrix `include:` blocks (widely cited community reference)

### Tertiary (MEDIUM confidence — CI YAML syntax)
- Spatie `laravel-permission` `run-tests.yml` — reference for matrix `include`/`exclude` pattern
- Community sources confirming Pest 4 PHP 8.3+ requirement (benjamincrozat.com, Medium, multiple)

---

*Research completed: 2026-03-02*
*Ready for roadmap: yes*
