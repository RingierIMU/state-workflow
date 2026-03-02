---
phase: 02-rector-pass
plan: 01
subsystem: refactoring
tags: [rector, php83, modernization, type-declarations, constructor-promotion]

# Dependency graph
requires:
  - phase: 01-dependency-update
    provides: PHP 8.3+ / Laravel 11+ / Symfony 7 constraints installed
provides:
  - All 19 src/ files modernized to PHP 8.3 idioms
  - Typed properties, constructor promotion, return types, null coalescing
  - Dead code removed (unused PHPDoc tags, redundant assignments)
affects: [02-rector-pass, 03-pest-migration]

# Tech tracking
tech-stack:
  added: [rector/rector 2.3.8, driftingly/rector-laravel 2.1.9]
  patterns: [constructor-property-promotion, typed-properties, null-coalescing, arrow-functions, override-attribute]

key-files:
  created: [rector.php]
  modified: [src/Events/BaseEvent.php, src/Events/GuardEvent.php, src/Models/StateWorkflowHistory.php, src/StateWorkflowServiceProvider.php, src/Subscribers/WorkflowSubscriber.php, src/Subscribers/WorkflowSubscriberHandler.php, src/Traits/HasWorkflowTrait.php, src/Workflow/MethodMarkingStore.php, src/Workflow/StateWorkflow.php, src/WorkflowRegistry.php, src/Console/Commands/StateWorkflowDumpCommand.php]

key-decisions:
  - "Skipped src/Interfaces/ from Rector to protect externally-implemented interface method signatures"
  - "Type additions on protected API classes (HasWorkflowTrait, StateWorkflow, WorkflowRegistry, WorkflowSubscriberHandler) are allowed per user decision"
  - "Laravel ModelCastsPropertyToCastsMethodRector applied to StateWorkflowHistory — $casts property converted to casts() method"

patterns-established:
  - "Constructor property promotion: all applicable constructors now use promoted parameters"
  - "Typed properties: all class properties have explicit types"
  - "Return type declarations: all methods have return types where inferrable"
  - "#[Override] attribute: added on methods overriding parent class methods"

requirements-completed: [RECT-01, RECT-02, RECT-04]

# Metrics
duration: 3min
completed: 2026-03-02
---

# Plan 02-01: Rector Pass Summary

**11 src/ files modernized to PHP 8.3 idioms via Rector — constructor promotion, typed properties, return types, null coalescing, dead code removal, #[Override] attributes**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-02
- **Completed:** 2026-03-02
- **Tasks:** 2
- **Files modified:** 11 src/ files + composer.json + rector.php

## Accomplishments
- All 11 applicable src/ files modernized (5 empty event subclasses + 3 interface files were untouched — correct)
- Constructor property promotion applied to BaseEvent, MethodMarkingStore, StateWorkflow, WorkflowRegistry
- Typed properties added across all classes (Registry, EventDispatcher, PropertyAccessor, etc.)
- Return type declarations added to all methods where inferrable
- Null coalescing `??` replaced `isset()` ternaries in WorkflowRegistry and StateWorkflow
- Dead PHPDoc removed (redundant @param/@return tags matching native types)
- Laravel-specific: $casts property converted to casts() method in StateWorkflowHistory
- #[Override] attribute added to overriding methods
- Closures converted to arrow functions where single-expression
- `get_called_class()` replaced with `static::class`
- `readonly` applied to MethodMarkingStore immutable properties
- Full PHPUnit test suite passes: 6 tests, 38 assertions

## Task Commits

1. **Task 1: Install Rector and create rector.php** - `d07d8fd` (chore)
2. **Task 2: Run Rector, review API safety, apply, verify tests** - `10949e1` (refactor)

## Files Created/Modified
- `rector.php` - Rector configuration targeting src/ with PHP 8.3 + Laravel 11 rules
- `src/Console/Commands/StateWorkflowDumpCommand.php` - Added void return type on handle()
- `src/Events/BaseEvent.php` - Constructor promotion, typed property
- `src/Events/GuardEvent.php` - Removed redundant PHPDoc, #[Override] attribute
- `src/Models/StateWorkflowHistory.php` - $casts property to casts() method, #[Override]
- `src/StateWorkflowServiceProvider.php` - Return types, arrow functions, #[Override], void types
- `src/Subscribers/WorkflowSubscriber.php` - Void return types on all event handlers
- `src/Subscribers/WorkflowSubscriberHandler.php` - Void return, static::class, arrow function, string casts
- `src/Traits/HasWorkflowTrait.php` - Return type declarations (string, string, string)
- `src/Workflow/MethodMarkingStore.php` - Constructor promotion, readonly properties, typed property
- `src/Workflow/StateWorkflow.php` - Constructor promotion, typed property, return type, null coalescing
- `src/WorkflowRegistry.php` - Constructor promotion, typed properties, return types, null coalescing, void types

## Decisions Made
- Skipped src/Interfaces/ directory from Rector type declaration rules to protect externally-implemented interface signatures
- Allowed type additions (return types, parameter types) on protected API classes per user decision — these are additive, not breaking
- Did not manually fix Process constructor in StateWorkflowDumpCommand — symfony/process is not installed in this package, so it's a pre-existing issue

## Deviations from Plan
None - plan executed exactly as written

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All src/ files modernized, ready for Rector cleanup (Plan 02-02)
- rector.php and Rector packages still present — will be removed in Plan 02-02
- PHPUnit tests confirmed green, ready for Pest migration in Phase 3

---
*Phase: 02-rector-pass*
*Completed: 2026-03-02*
