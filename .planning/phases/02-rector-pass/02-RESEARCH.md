# Phase 2: Rector Pass - Research

**Researched:** 2026-03-02
**Domain:** PHP 8.3 source modernization via Rector
**Confidence:** HIGH

## Summary

Phase 2 applies Rector to modernize 19 PHP source files in `src/` to PHP 8.3 idioms, then removes Rector from the project. The codebase is small and well-structured with clear modernization targets: untyped properties, missing return/parameter type declarations, pre-PHP 8.0 constructor patterns, and `isset()` ternary patterns that should be null coalescing operators.

The key risk is RECT-02 (public API preservation). Four classes have protected public APIs: `HasWorkflowTrait`, `StateWorkflow`, `WorkflowRegistry`, and `WorkflowSubscriberHandler`. Per user decisions, **adding** type declarations (return types, parameter types) is allowed — only behavioral signature changes (renaming, removing, reordering, changing semantics) are prohibited.

**Primary recommendation:** Install rector/rector and driftingly/rector-laravel, configure a rector.php targeting only `src/`, run dry-run to review, apply, verify tests pass, then remove Rector and its config in a separate commit.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- Rector rule sets: PHP 8.0-8.3 upgrade sets + driftingly/rector-laravel rules
- Dead code removal rules included (unused imports, unreachable code, unused private methods)
- Add missing return type declarations where inferrable
- Add missing parameter type declarations where inferrable
- Apply only to src/ directory — tests/ excluded per RECT-01
- Type additions (return types, param types) are allowed on all classes — additive changes are not considered breaking
- No behavioral signature changes on protected classes: HasWorkflowTrait, StateWorkflow, WorkflowRegistry, WorkflowSubscriberHandler
- Interfaces: protect since they're implemented externally
- Auto-apply approach: run Rector dry-run, review diff for API signature violations, apply, run full test suite
- If tests pass, commit — no human pause needed
- Failure handling: Claude's discretion per failure
- Dry-run diff is ephemeral — not saved as an artifact
- Remove rector/rector and driftingly/rector-laravel from composer.json require-dev after applying
- Delete rector.php config file
- Run composer update to clean lock file
- Separate commits: first commit applies Rector modernizations, second commit removes Rector from dependencies
- Style conformance after Rector: Claude's discretion

### Claude's Discretion
- Exact Rector rule set composition
- Whether to protect interfaces (likely yes for externally-implemented ones)
- Failure resolution strategy per test failure
- Whether to run a style fixer after Rector transforms
- Exact PHP 8.3 idiom choices where multiple valid options exist

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| RECT-01 | `driftingly/rector-laravel` applied to `src/` directory only (not `tests/`) | rector.php `withPaths([__DIR__.'/src'])` configuration; 19 files in src/ identified |
| RECT-02 | Rector dry-run reviewed — no public API signatures changed in HasWorkflowTrait, StateWorkflow, or WorkflowRegistry | API surface catalogued; type additions are allowed per user decision; behavioral changes blocked |
| RECT-03 | Rector and driftingly/rector-laravel removed from dev dependencies after applying | Two-commit strategy: apply first, remove second |
| RECT-04 | All existing PHPUnit tests pass after Rector changes | Test suite must be run after apply; failures resolved per Claude's discretion |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| rector/rector | ^2.0 | PHP code transformation engine | The standard tool for automated PHP modernization |
| driftingly/rector-laravel | ^2.0 | Laravel-specific Rector rules | Provides Laravel-aware refactoring rules (service provider, facades, etc.) |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| (none) | - | - | Rector and its Laravel extension are sufficient |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| driftingly/rector-laravel | Manual refactoring | More effort, no Laravel-specific rules |
| rector/rector | PHP-CS-Fixer | PHP-CS-Fixer is style only, not modernization |

**Installation:**
```bash
composer require --dev rector/rector driftingly/rector-laravel
```

## Architecture Patterns

### Rector Configuration Pattern
```php
// rector.php (project root)
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        LaravelSetList::LARAVEL_110,
    ])
    ->withTypeCoverageLevel(0)  // Start conservative, can increase
    ->withDeadCodeLevel(0)      // Start conservative
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    );
```

### Dry-Run-Then-Apply Pattern
```bash
# Step 1: Dry run to see what changes
vendor/bin/rector process --dry-run

# Step 2: Review output — check for API violations on protected classes
# Step 3: Apply changes
vendor/bin/rector process

# Step 4: Run tests
vendor/bin/phpunit
```

### Anti-Patterns to Avoid
- **Running Rector on tests/:** Tests will be migrated to Pest in Phase 3; Rector changes to PHPUnit tests would conflict
- **Applying all rules at once without dry-run:** May produce unexpected API changes
- **Keeping Rector as permanent dependency:** This is an apply-once tool per project constraint

## Codebase Modernization Analysis

### File-by-File Modernization Targets

**High-impact files (complex, many targets):**

| File | Targets |
|------|---------|
| `WorkflowRegistry.php` | Typed properties (`$registry`, `$config`, `$dispatcher`), null coalescing (`isset($x) ? $x : $y` x2), constructor could use promotion, return type declarations |
| `HasWorkflowTrait.php` | Typed properties (`$workflow`, `$context`, `$stateHistoryModel`), return type declarations on all 10 methods, parameter type declarations (`$transition`) |
| `WorkflowSubscriber.php` | Return type declarations on all 7 methods (void for event handlers, array for getSubscribedEvents) |
| `WorkflowSubscriberHandler.php` | Typed property (`$name`), constructor property promotion, return type declarations, parameter types |
| `StateWorkflowServiceProvider.php` | Return type declarations (boot/register: void, configPath/migrationPath: string, provides: array) |

**Medium-impact files (some targets):**

| File | Targets |
|------|---------|
| `BaseEvent.php` | Constructor property promotion (`$originalEvent`), return type declaration |
| `GuardEvent.php` | Return type declaration on `getOriginalEvent()` |
| `MethodMarkingStore.php` | Constructor property promotion (`$property`, `$singleState`), readonly on immutable properties |
| `StateWorkflow.php` | Typed property (`$config`), constructor param null coalescing, return type on `getState()` |
| `StateWorkflowDumpCommand.php` | Return type on `handle()` (void or int), constructor types |
| `StateWorkflowHistory.php` | Typed properties (`$fillable`, `$casts`) |

**Low-impact files (minimal/no targets):**

| File | Targets |
|------|---------|
| `CompletedEvent.php` | Empty class body — no changes expected |
| `EnterEvent.php` | Empty class body — no changes expected |
| `EnteredEvent.php` | Empty class body — no changes expected |
| `LeaveEvent.php` | Empty class body — no changes expected |
| `TransitionEvent.php` | Empty class body — no changes expected |
| `StateWorkflowInterface.php` | Parameter type + return type (interface — protect) |
| `WorkflowEventSubscriberInterface.php` | Parameter type (interface — protect) |
| `WorkflowRegistryInterface.php` | Parameter types + return types (interface — protect) |

### Protected API Surface (RECT-02)

These classes have public APIs consumed by external packages. Type additions are allowed; behavioral changes are not.

**HasWorkflowTrait** (9 public methods):
- `workflow(): StateWorkflow` — adding return type OK
- `state(): mixed` — adding return type OK
- `applyTransition($transition, $context = []): Marking` — adding param/return types OK
- `canTransition($transition): bool` — adding param/return types OK
- `getEnabledTransition(): array` — adding return type OK
- `stateHistory(): HasMany` — adding return type OK
- `saveStateHistory(array $transitionData): Model` — adding return type OK
- `authenticatedUserId(): ?int` — adding return type OK
- `configName(): string` — adding return type OK
- `authUserForeignKey(): string` — adding return type OK
- `modelPrimaryKey(): string` — adding return type OK
- `getContext(): array` — adding return type OK

**StateWorkflow** (1 own public method + inherited):
- `getState($object): mixed` — adding param/return types OK

**WorkflowRegistry** (5 public methods):
- `get($subject, $workflowName = null): WorkflowInterface` — adding types OK
- `registerWorkflow(StateWorkflow $workflow, string $className): void` — adding return type OK
- `addWorkflows($name, array $workflowData): void` — adding types OK
- `addSubscriber($class, $name): void` — adding types OK
- Constructor — types OK

**WorkflowSubscriberHandler** (2 public methods):
- `subscribe($event): void` — adding types OK
- Constructor — types OK

**Interfaces (protect — externally implemented):**
- `StateWorkflowInterface::getState($object)` — Do NOT add parameter/return types (would break implementations)
- `WorkflowEventSubscriberInterface::subscribe($event)` — Do NOT add types
- `WorkflowRegistryInterface::get($object, $workflowName = null)` — Do NOT add types
- `WorkflowRegistryInterface::addSubscriber($class, $name)` — Do NOT add types

**CRITICAL:** Rector may try to add types to interface methods. These must be excluded or reverted if they appear in the dry-run. Adding types to interface methods is a breaking change for all implementors.

### Specific Null Coalescing Opportunities

In `WorkflowRegistry::getMarkingStoreInstance()`:
```php
// Current (line 166-167):
$markingStoreData = isset($workflowData['marking_store']) ? $workflowData['marking_store'] : [];
$propertyPath = isset($workflowData['property_path']) ? $workflowData['property_path'] : 'current_state';

// After Rector:
$markingStoreData = $workflowData['marking_store'] ?? [];
$propertyPath = $workflowData['property_path'] ?? 'current_state';
```

In `StateWorkflow::getState()`:
```php
// Current (line 50):
$propertyPath = isset($this->config['property_path']) ? $this->config['property_path'] : 'current_state';

// After Rector:
$propertyPath = $this->config['property_path'] ?? 'current_state';
```

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| PHP modernization | Manual find-and-replace | Rector | Handles edge cases, AST-aware, consistent |
| Laravel-specific rules | Custom Rector rules | driftingly/rector-laravel | Community-maintained, tested |
| Code style fixing | Manual formatting | StyleCI (existing) or PHP-CS-Fixer | Rector output may need style normalization |

## Common Pitfalls

### Pitfall 1: Interface Type Addition
**What goes wrong:** Rector adds return/parameter types to interface methods, breaking all implementors
**Why it happens:** Rector doesn't know which interfaces are implemented externally
**How to avoid:** After dry-run, check all interface files for changes. Rector configuration can skip specific files or use `withSkip()` for interface files
**Warning signs:** Changes in `src/Interfaces/*.php` files

### Pitfall 2: Constructor Promotion on Protected-API Classes
**What goes wrong:** Constructor property promotion changes visibility of properties
**Why it happens:** Rector promotes `protected $x` in constructor to `protected $x` as promoted param — visibility preserved, but if it changes from protected to public (or vice versa) that's a break
**How to avoid:** Rector preserves visibility during promotion — this is safe. But verify in dry-run.
**Warning signs:** Constructor changes in HasWorkflowTrait, WorkflowSubscriberHandler

### Pitfall 3: Return Type Mismatch with Parent
**What goes wrong:** Adding return types that conflict with parent class signatures (Symfony Workflow class)
**Why it happens:** StateWorkflow extends Symfony\Component\Workflow\Workflow — Rector may add types that conflict
**How to avoid:** Rector is AST-aware and should respect parent signatures. Verify in dry-run.
**Warning signs:** Changes to StateWorkflow constructor or inherited method signatures

### Pitfall 4: Rector Version Compatibility
**What goes wrong:** Rector version incompatible with PHP version or driftingly/rector-laravel version
**Why it happens:** Fast-moving ecosystem with frequent breaking changes
**How to avoid:** Use latest stable versions; check composer resolve before proceeding
**Warning signs:** Composer dependency resolution failures

### Pitfall 5: `readonly` on Eloquent Model Properties
**What goes wrong:** Rector marks `$fillable` or `$casts` as readonly, but Laravel needs to mutate them
**Why it happens:** Rector sees they're set once and marks readonly
**How to avoid:** Rector should not do this on Eloquent models (the Laravel set handles it), but verify in dry-run
**Warning signs:** `readonly` keyword on StateWorkflowHistory properties

## Code Examples

### rector.php Configuration (Recommended)
```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        LaravelSetList::LARAVEL_110,
    ])
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    )
    ->withSkip([
        // Protect interface method signatures from type additions
        __DIR__ . '/src/Interfaces',
    ]);
```

### Dry-Run Command
```bash
vendor/bin/rector process --dry-run 2>&1
```

### Apply Command
```bash
vendor/bin/rector process
```

### Test Verification
```bash
vendor/bin/phpunit
```

### Cleanup Commands
```bash
composer remove --dev rector/rector driftingly/rector-laravel
rm rector.php
composer update
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Untyped properties | Typed properties (PHP 7.4+) | PHP 7.4 (2019) | All properties can have types |
| `isset()` ternary | Null coalescing `??` (PHP 7.0+) | PHP 7.0 (2015) | Cleaner null handling |
| Constructor + manual assign | Constructor property promotion (PHP 8.0+) | PHP 8.0 (2020) | Less boilerplate |
| No `readonly` | `readonly` properties (PHP 8.1+) | PHP 8.1 (2021) | Immutability enforcement |
| No `match` | `match` expressions (PHP 8.0+) | PHP 8.0 (2020) | Replaces switch with expression |
| PHPDoc types only | Native union types (PHP 8.0+) | PHP 8.0 (2020) | Runtime type enforcement |
| No intersection types | Intersection types (PHP 8.1+) | PHP 8.1 (2021) | Combine type constraints |
| No enums | Native enums (PHP 8.1+) | PHP 8.1 (2021) | Type-safe enumerations |
| No `never` return | `never` return type (PHP 8.1+) | PHP 8.1 (2021) | Functions that never return |

## Open Questions

1. **Exact driftingly/rector-laravel version compatibility with rector/rector ^2.0**
   - What we know: driftingly/rector-laravel 2.x targets rector 1.x+
   - What's unclear: Exact version pinning for latest stable
   - Recommendation: Let composer resolve; use `^2.0` for both and see what resolves

2. **Whether `Process` constructor in StateWorkflowDumpCommand needs array argument**
   - What we know: Symfony Process 7.0 requires array argument, not string
   - What's unclear: Whether Rector catches this or if it's already handled
   - Recommendation: Check dry-run output for this file specifically — may need manual fix

## Sources

### Primary (HIGH confidence)
- Direct codebase analysis — all 19 PHP files in src/ examined
- CONTEXT.md user decisions — locked constraints for API protection and rule sets
- REQUIREMENTS.md — RECT-01 through RECT-04 specifications

### Secondary (MEDIUM confidence)
- Rector documentation patterns — standard configuration approach
- driftingly/rector-laravel — known Laravel rule sets

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Rector is the standard PHP modernization tool
- Architecture: HIGH - Configuration pattern is well-established
- Modernization targets: HIGH - Direct source code analysis, every file examined
- Pitfalls: HIGH - Based on known Rector behavior patterns

**Research date:** 2026-03-02
**Valid until:** 2026-04-02 (30 days — stable domain)
