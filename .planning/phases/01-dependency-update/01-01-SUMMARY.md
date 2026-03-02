---
phase: 01-dependency-update
plan: 01
subsystem: infra
tags: [composer, symfony, laravel, php8.3, dependency-management]

# Dependency graph
requires:
  - phase: none
    provides: initial codebase with legacy constraints
provides:
  - Modern dependency constraints (PHP ^8.3, Laravel ^11.0|^12.0, Symfony ^7.0)
  - Clean source code without backward-compatibility shims
  - Symfony 7 MarkingStoreInterface compliance (setMarking(): void)
  - Validated vendor tree with passing test suite
affects: [02-rector-modernize, 03-pest-migration, 04-ci-finalize]

# Tech tracking
tech-stack:
  added: []
  patterns: [direct-api-usage-no-shims]

key-files:
  created: []
  modified:
    - composer.json
    - src/WorkflowRegistry.php
    - src/Workflow/MethodMarkingStore.php
    - tests/Unit/UserUnitTest.php

key-decisions:
  - "Testbench constraint set to ^9.15|^10.0 (not ^9.0) — v9.0.0 has $latestResponse static property bug"

patterns-established:
  - "Direct Symfony 7 API: no class_exists/method_exists shims — use the modern API directly"

requirements-completed: [DEPS-01, DEPS-02, DEPS-03, DEPS-04, DEPS-05, DEPS-06, DEPS-07, DEPS-08]

# Metrics
duration: 5min
completed: 2026-03-02
---

# Plan 01-01: Dependency Update Summary

**PHP ^8.3 / Laravel ^11.0|^12.0 / Symfony ^7.0 constraints with all backward-compatibility shims removed and full test suite green**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-02
- **Completed:** 2026-03-02
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments
- Updated composer.json to require PHP ^8.3, illuminate/* ^11.0|^12.0, symfony/* ^7.0, testbench ^9.15|^10.0
- Removed ClassInstanceSupportStrategy import and method_exists/class_exists shims from WorkflowRegistry
- Added `: void` return type to MethodMarkingStore::setMarking() for Symfony 7 MarkingStoreInterface compliance
- Removed LogicException import and class_exists shim from UserUnitTest
- Validated with composer update --prefer-lowest --prefer-stable (exits 0, Symfony 7.0.0 installed)
- Full PHPUnit test suite passes on both prefer-lowest (PHPUnit 10.5) and prefer-stable (PHPUnit 11.5)

## Task Commits

Each task was committed atomically:

1. **Task 1: Update composer.json constraints and remove all backward-compatibility shims** - `24a1b87` (feat)
2. **Task 2: Validate clean dependency resolution and test suite** - No commit (validation-only; composer.lock is gitignored for libraries)

## Files Created/Modified
- `composer.json` - Updated require/require-dev version constraints for PHP 8.3+, Laravel 11+, Symfony 7
- `src/WorkflowRegistry.php` - Removed ClassInstanceSupportStrategy import and dynamic shim in registerWorkflow()
- `src/Workflow/MethodMarkingStore.php` - Added `: void` return type to setMarking() signature
- `tests/Unit/UserUnitTest.php` - Removed LogicException import and class_exists ternary shim

## Decisions Made
- Testbench minimum set to ^9.15 instead of ^9.0 — v9.0.0 has a `$latestResponse` static property bug that causes all tests to error. The original codebase already had ^9.15 as the floor, confirming this was intentional.

## Deviations from Plan

### Auto-fixed Issues

**1. [Constraint Fix] Testbench minimum version adjusted from ^9.0 to ^9.15**
- **Found during:** Task 2 (test suite validation with --prefer-lowest)
- **Issue:** orchestra/testbench 9.0.0 has undeclared `$latestResponse` static property causing all tests to error
- **Fix:** Changed constraint from `^9.0|^10.0` to `^9.15|^10.0` matching original project floor
- **Files modified:** composer.json
- **Verification:** All 6 tests pass with 38 assertions on prefer-lowest (testbench 9.15.0)
- **Committed in:** 24a1b87 (amended Task 1 commit)

---

**Total deviations:** 1 auto-fixed (constraint adjustment)
**Impact on plan:** Necessary fix — the plan's MUST_HAVE `composer.json` artifact specified `^9.0|^10.0` but the original codebase floor was `^9.15` for good reason. No scope creep.

## Issues Encountered
None beyond the testbench constraint adjustment documented above.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All dependency constraints modernized — Rector (Phase 2) can target PHP 8.3 features
- Symfony 7 APIs are the only APIs in use — no legacy shims to confuse Rector rules
- Test suite green on modern stack — baseline for Pest migration (Phase 3)

---
*Phase: 01-dependency-update*
*Completed: 2026-03-02*
