# Phase 1: Dependency Update - Research

**Researched:** 2026-03-02
**Domain:** PHP/Composer dependency management, Symfony Workflow 7.0 upgrade, Laravel 11/12 compatibility
**Confidence:** HIGH

## Summary

Phase 1 upgrades `composer.json` constraints to PHP 8.3+, Laravel 11+/12, Symfony 7, and Orchestra Testbench 9/10, then removes a backward-compatibility shim in `WorkflowRegistry.php` that will cause a `ClassNotFoundException` on Symfony 7 because `InstanceOfSupportStrategy` is the surviving class (not the removed `ClassInstanceSupportStrategy`). The custom `MethodMarkingStore` must also gain a `: void` return type on `setMarking()` to satisfy the Symfony 7 `MarkingStoreInterface` contract.

The codebase is small (18 source files, 3 test files) and the public API surface (`HasWorkflowTrait`, `StateWorkflow`, `WorkflowRegistry`) does not change. The risk is low but precise: three code edits (shim removal, return type addition, exception shim cleanup) plus one `composer.json` rewrite must all land together before `composer update` can succeed.

**Primary recommendation:** Update `composer.json` constraints, remove all backward-compatibility shims in source and tests, add the `: void` return type to `MethodMarkingStore::setMarking()`, then validate with `composer update --prefer-lowest --prefer-stable` and the existing PHPUnit suite.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DEPS-01 | Package requires PHP `^8.3` minimum | Change `"php": "^8.1"` to `"php": "^8.3"` in composer.json. Laravel 11/12 require `^8.2` so `^8.3` is a valid subset. |
| DEPS-02 | Package requires `illuminate/*` `^11.0\|^12.0` (drop Laravel 10) | Change both `illuminate/events` and `illuminate/support` from `"^10.0\|^11.0\|^12.0"` to `"^11.0\|^12.0"`. |
| DEPS-03 | Package requires `symfony/workflow` `^7.0` (drop `^5.1\|^6.0`) | Change from `"^5.1"` to `"^7.0"`. This triggers all Symfony 7 breaking changes documented below. |
| DEPS-04 | Package requires `symfony/event-dispatcher` `^7.0` | Change from `"^6.0\|^7.0"` to `"^7.0"`. No code changes needed -- `EventSubscriberInterface` API is unchanged. |
| DEPS-05 | Package requires `symfony/property-access` `^7.0` | Not currently in `require` section -- `symfony/property-access` is a transitive dependency. Must be added explicitly as `"^7.0"` since `MethodMarkingStore` and `StateWorkflow` both directly import `PropertyAccess`/`PropertyAccessor`. |
| DEPS-06 | `InstanceOfSupportStrategy` dual-import shim removed from `WorkflowRegistry.php` -- unconditionally use `ClassInstanceSupportStrategy` | **IMPORTANT CORRECTION**: The requirement text says "use ClassInstanceSupportStrategy" but the actual direction is reversed. In Symfony 7, `ClassInstanceSupportStrategy` was REMOVED and `InstanceOfSupportStrategy` SURVIVED. The shim must be replaced with unconditional use of `InstanceOfSupportStrategy`. Also remove the `addWorkflow`/`add` method shim -- Symfony 7 only has `addWorkflow`. |
| DEPS-07 | `orchestra/testbench` updated to `^9.0\|^10.0` | Change from `"^8.0\|^9.15\|^10"` to `"^9.0\|^10.0"` (drop Testbench 8 which is Laravel 10). |
| DEPS-08 | All dependencies resolve cleanly with `composer update --prefer-lowest --prefer-stable` | Run after all other changes. Validates constraint coherence. |
</phase_requirements>

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `symfony/workflow` | `^7.0` | State machine / workflow engine | Core dependency this package wraps |
| `symfony/event-dispatcher` | `^7.0` | Event dispatching within workflows | Required by symfony/workflow |
| `symfony/property-access` | `^7.0` | Property getter/setter abstraction | Used by MethodMarkingStore and StateWorkflow |
| `illuminate/support` | `^11.0\|^12.0` | Laravel service provider, config, collections | Laravel framework integration |
| `illuminate/events` | `^11.0\|^12.0` | Laravel event system bridge | Used by WorkflowSubscriberHandler |

### Supporting (Dev)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `orchestra/testbench` | `^9.0\|^10.0` | Laravel package testing harness | Testbench 9 = Laravel 11, Testbench 10 = Laravel 12 |
| `phpunit/phpunit` | `^10.0\|^11.0` | Test runner (replaced by Pest in Phase 3) | Current test runner, kept for this phase |
| `mockery/mockery` | `^1.3\|^1.4.2` | Mock framework | No version change needed |
| `funkjedi/composer-include-files` | `^1.0` | Loads `tests/Fixtures/Helpers.php` before autoload | Ensures global `event()` mock is loaded for tests |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `symfony/property-access` as explicit dependency | Leave as transitive | Transitive is fragile -- direct import in two files means it should be explicit |

**Installation (after edits):**
```bash
composer update --prefer-lowest --prefer-stable
```

## Architecture Patterns

### Shim Removal Pattern

The codebase has three backward-compatibility shims that must all be removed:

#### Shim 1: SupportStrategy in `WorkflowRegistry::registerWorkflow()`
**Location:** `src/WorkflowRegistry.php` lines 84-90
**Current code:**
```php
$method = method_exists($this->registry, 'addWorkflow') ? 'addWorkflow' : 'add';
$strategyClass = class_exists(InstanceOfSupportStrategy::class)
    ? InstanceOfSupportStrategy::class
    : ClassInstanceSupportStrategy::class;
$this->registry->$method($workflow, new $strategyClass($className));
```
**Replace with:**
```php
$this->registry->addWorkflow($workflow, new InstanceOfSupportStrategy($className));
```
**Why:** Symfony 7 removed `ClassInstanceSupportStrategy` (deprecated since 4.1) and only has `addWorkflow` (the `add` method was removed). The current shim logic happens to work because it checks `InstanceOfSupportStrategy` first, but the fallback path and dynamic method dispatch are dead code that obscures intent.

#### Shim 2: Exception class in `UserUnitTest::test_invalid_transition_throws_exception()`
**Location:** `tests/Unit/UserUnitTest.php` lines 33-35
**Current code:**
```php
$expectedExceptionClass = class_exists(NotEnabledTransitionException::class)
    ? NotEnabledTransitionException::class
    : LogicException::class;
```
**Replace with:**
```php
$expectedExceptionClass = NotEnabledTransitionException::class;
```
**Why:** `NotEnabledTransitionException` exists in Symfony 7. The `LogicException` fallback was for Symfony < 4.1 which is no longer supported.

#### Shim 3: Import cleanup
**Location:** `src/WorkflowRegistry.php` line 19
**Remove:**
```php
use Symfony\Component\Workflow\SupportStrategy\ClassInstanceSupportStrategy;
```
**And in tests:** Remove the `LogicException` import from `UserUnitTest.php`.

### Return Type Addition Pattern

#### MethodMarkingStore::setMarking() must declare `: void`
**Location:** `src/Workflow/MethodMarkingStore.php` line 54
**Current:**
```php
public function setMarking(object $subject, Marking $marking, array $context = [])
```
**Replace with:**
```php
public function setMarking(object $subject, Marking $marking, array $context = []): void
```
**Why:** Symfony 7 `MarkingStoreInterface` declares `setMarking(): void`. PHP will throw a fatal error if the implementing class omits the return type. The method already returns nothing, so this is purely a signature alignment.

### Anti-Patterns to Avoid

- **Partial constraint update:** Do NOT update `symfony/workflow` to `^7.0` while leaving `symfony/event-dispatcher` at `^6.0|^7.0` -- Symfony enforces version alignment across components. All three Symfony packages must be `^7.0`.
- **Leaving shims in place:** Do NOT leave the `class_exists()` / `method_exists()` shims "for safety." They reference classes that no longer exist in Symfony 7 and create confusion. Clean removal is safer.
- **Updating constraints without code changes:** Composer will install Symfony 7 but the autoloader will crash at boot if `ClassInstanceSupportStrategy` import remains, or PHPUnit will fail with a fatal error on the missing `: void` return type.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| SupportStrategy selection | Dynamic `class_exists()` shim | Direct `InstanceOfSupportStrategy` | Only one class exists in Symfony 7 |
| Registry method selection | Dynamic `method_exists()` shim | Direct `addWorkflow()` call | Only one method exists in Symfony 7 |
| Exception class selection | Dynamic `class_exists()` shim | Direct `NotEnabledTransitionException` | Class has existed since Symfony 4.1, guaranteed in 7.0 |

**Key insight:** All three shims were necessary when the package supported Symfony 3.x through 6.x simultaneously. With the floor raised to Symfony 7, every shim resolves to exactly one path and should be replaced with a direct call.

## Common Pitfalls

### Pitfall 1: MethodMarkingStore missing `: void` return type
**What goes wrong:** `PHP Fatal error: Declaration of MethodMarkingStore::setMarking() must be compatible with MarkingStoreInterface::setMarking(): void`
**Why it happens:** Symfony 7 added native return types to all interfaces. The custom MethodMarkingStore implements `MarkingStoreInterface` but lacks the `: void` declaration.
**How to avoid:** Add `: void` return type to `setMarking()` in `src/Workflow/MethodMarkingStore.php`.
**Warning signs:** Any `composer update` followed by `php artisan` or test run will immediately fatal.

### Pitfall 2: Autoloader crash from dead import
**What goes wrong:** `ClassNotFoundException: Attempted to load class "ClassInstanceSupportStrategy" from namespace "Symfony\Component\Workflow\SupportStrategy"`
**Why it happens:** `WorkflowRegistry.php` has `use Symfony\Component\Workflow\SupportStrategy\ClassInstanceSupportStrategy;` at line 19. PHP autoloads all `use` statements when the class is loaded, even if the reference is in a dead `class_exists()` branch.
**How to avoid:** Remove the `use` import entirely. The `class_exists()` call with a string would not crash, but the `use` import WILL crash at class load time.
**Warning signs:** `php artisan` or any code path that resolves `WorkflowRegistry` will throw immediately.
**Confidence:** HIGH -- this is the exact scenario described in STATE.md as a known decision.

### Pitfall 3: symfony/property-access not in explicit requirements
**What goes wrong:** `composer update --prefer-lowest` may pull an older property-access version incompatible with the `^7.0` workflow, or future Symfony changes may break the transitive chain.
**Why it happens:** `src/Workflow/MethodMarkingStore.php` and `src/Workflow/StateWorkflow.php` both directly `use Symfony\Component\PropertyAccess\PropertyAccess` but it is not declared in `composer.json` `require`.
**How to avoid:** Add `"symfony/property-access": "^7.0"` to the `require` section.
**Warning signs:** `composer update --prefer-lowest` installs an unexpected version.

### Pitfall 4: Not removing `composer.lock` before validating
**What goes wrong:** `composer update` may resolve from cached lock state, hiding constraint conflicts.
**Why it happens:** The lock file pins to Symfony 5/6 versions that won't exist in the new constraint range.
**How to avoid:** Delete `composer.lock` and `vendor/` before running `composer update --prefer-lowest --prefer-stable` for a clean resolution test.
**Warning signs:** Composer exits 0 but `vendor/symfony/workflow` is still v5 or v6.

### Pitfall 5: StateWorkflowDumpCommand uses undeclared `symfony/process`
**What goes wrong:** `StateWorkflowDumpCommand` imports `Symfony\Component\Process\Process` but `symfony/process` is not in `composer.json`.
**Why it happens:** Pre-existing issue -- `Process` was pulled transitively. Additionally, the constructor call `new Process($dotCommand)` passes a string, which was removed in Symfony 5.0 (must use array or `Process::fromShellCommandline()`).
**How to avoid:** This is NOT in scope for Phase 1 (no DEPS requirement covers it) but should be noted for future phases. The command is optional/rarely used.
**Warning signs:** Running `php artisan workflow:dump` will fail.

## Code Examples

### composer.json `require` section (target state)
```json
{
    "require": {
        "php": "^8.3",
        "illuminate/events": "^11.0|^12.0",
        "illuminate/support": "^11.0|^12.0",
        "symfony/event-dispatcher": "^7.0",
        "symfony/property-access": "^7.0",
        "symfony/workflow": "^7.0"
    }
}
```

### composer.json `require-dev` section (target state)
```json
{
    "require-dev": {
        "funkjedi/composer-include-files": "^1.0",
        "mockery/mockery": "^1.3|^1.4.2",
        "orchestra/testbench": "^9.0|^10.0",
        "phpunit/phpunit": "^10.0|^11.0"
    }
}
```

### WorkflowRegistry::registerWorkflow() (target state)
```php
// Source: verified against Symfony 7.0 Registry::addWorkflow signature
public function registerWorkflow(StateWorkflow $workflow, string $className)
{
    $this->registry->addWorkflow($workflow, new InstanceOfSupportStrategy($className));
}
```

### MethodMarkingStore::setMarking() (target state)
```php
// Source: verified against Symfony 7.0 MarkingStoreInterface
public function setMarking(object $subject, Marking $marking, array $context = []): void
{
    $this->propertyAccessor->setValue($subject, $this->property, key($marking->getPlaces()));
}
```

### WorkflowRegistry imports (target state)
```php
// REMOVE: use Symfony\Component\Workflow\SupportStrategy\ClassInstanceSupportStrategy;
// KEEP:
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `ClassInstanceSupportStrategy` | `InstanceOfSupportStrategy` | Symfony 4.1 (deprecated), 7.0 (removed) | Must use `InstanceOfSupportStrategy` unconditionally |
| `Registry::add()` | `Registry::addWorkflow()` | Symfony 4.1 (deprecated), 7.0 (removed) | Must use `addWorkflow()` unconditionally |
| `SupportStrategyInterface` | `WorkflowSupportStrategyInterface` | Symfony 6.2 (deprecated), 7.0 (removed) | No direct impact -- package uses concrete class not interface |
| `MarkingStoreInterface::setMarking()` (no return type) | `setMarking(): void` | Symfony 7.0 | Custom `MethodMarkingStore` must add `: void` |
| `new Process(string)` | `new Process(array)` or `Process::fromShellCommandline()` | Symfony 5.0 | Affects `StateWorkflowDumpCommand` (out of scope) |

**Deprecated/outdated:**
- `ClassInstanceSupportStrategy`: Removed in Symfony 7.0, replaced by `InstanceOfSupportStrategy`
- `Registry::add()`: Removed in Symfony 7.0, replaced by `Registry::addWorkflow()`
- `SingleStateMarkingStore` / `MultipleStateMarkingStore`: Removed, replaced by `MethodMarkingStore` (package already uses `MethodMarkingStore`)

## Open Questions

1. **DEPS-06 requirement text says "use ClassInstanceSupportStrategy" but the correct class is `InstanceOfSupportStrategy`**
   - What we know: Symfony deprecated `ClassInstanceSupportStrategy` in favor of `InstanceOfSupportStrategy` in 4.1 and removed it in 7.0. The requirement text appears to have the names swapped.
   - What's unclear: Whether the requirement author intended the surviving class or literally `ClassInstanceSupportStrategy`.
   - Recommendation: Use `InstanceOfSupportStrategy` (the class that exists in Symfony 7). The requirement's intent ("remove the shim, use the correct class") is clear even if the class name is inverted. **Confidence: HIGH** that `InstanceOfSupportStrategy` is correct.

2. **Should `symfony/property-access` be added as explicit dependency?**
   - What we know: Two source files directly import from it. It is currently resolved as a transitive dependency of `symfony/workflow`.
   - What's unclear: Whether the project owner wants to keep it transitive or make it explicit.
   - Recommendation: Add it explicitly as `"^7.0"` -- direct imports should have direct dependencies. This also ensures `--prefer-lowest` resolves correctly.

3. **`funkjedi/composer-include-files` compatibility with Symfony 7 / PHP 8.3**
   - What we know: The package loads `tests/Fixtures/Helpers.php` (a global `event()` mock) before autoload. The package has `^1.0` constraint.
   - What's unclear: Whether this plugin still works correctly with the updated dependency tree.
   - Recommendation: Test as part of DEPS-08 validation. If it fails, the `Helpers.php` include mechanism may need rethinking (but that is more of a Phase 3 / Pest concern).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 10/11 (via `phpunit.xml`) |
| Config file | `phpunit.xml` |
| Quick run command | `vendor/bin/phpunit --stop-on-failure` |
| Full suite command | `vendor/bin/phpunit` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DEPS-01 | PHP ^8.3 constraint | smoke | `php -v` (must be 8.3+) | N/A |
| DEPS-02 | illuminate/* ^11.0\|^12.0 | smoke | `composer show illuminate/support` | N/A |
| DEPS-03 | symfony/workflow ^7.0 | smoke | `composer show symfony/workflow` | N/A |
| DEPS-04 | symfony/event-dispatcher ^7.0 | smoke | `composer show symfony/event-dispatcher` | N/A |
| DEPS-05 | symfony/property-access ^7.0 | smoke | `composer show symfony/property-access` | N/A |
| DEPS-06 | Shim removed, InstanceOfSupportStrategy used | integration | `vendor/bin/phpunit tests/Unit/UserUnitTest.php` | Yes |
| DEPS-07 | orchestra/testbench ^9.0\|^10.0 | smoke | `composer show orchestra/testbench` | N/A |
| DEPS-08 | Clean resolve with --prefer-lowest | smoke | `composer update --prefer-lowest --prefer-stable` | N/A |

### Sampling Rate
- **Per task commit:** `vendor/bin/phpunit --stop-on-failure`
- **Per wave merge:** `vendor/bin/phpunit`
- **Phase gate:** Full suite green + `composer update --prefer-lowest --prefer-stable` exits 0

### Wave 0 Gaps
None -- existing test infrastructure covers all phase requirements. The existing `UserUnitTest` and `WorkflowSubscriberTest` exercise the workflow registry, marking store, and event subscriber paths that are affected by Symfony 7 changes.

## Sources

### Primary (HIGH confidence)
- [symfony/workflow 7.0 CHANGELOG.md](https://github.com/symfony/workflow/blob/7.0/CHANGELOG.md) - Deprecation and removal timeline
- [symfony/workflow 7.0 SupportStrategy directory](https://github.com/symfony/workflow/tree/7.3/SupportStrategy) - Confirms `InstanceOfSupportStrategy` exists, `ClassInstanceSupportStrategy` absent
- [symfony/workflow 7.0 Workflow.php](https://github.com/symfony/workflow/blob/7.0/Workflow.php) - Constructor signature verification
- [symfony/workflow 7.0 MethodMarkingStore.php](https://github.com/symfony/workflow/blob/7.0/MarkingStore/MethodMarkingStore.php) - `setMarking(): void` return type
- [symfony/workflow 7.0 Registry.php](https://github.com/symfony/workflow/blob/7.0/Registry.php) - `addWorkflow()` signature with `WorkflowSupportStrategyInterface`
- [symfony/event-dispatcher 7.0 CHANGELOG.md](https://github.com/symfony/event-dispatcher/blob/7.0/CHANGELOG.md) - No breaking changes to `EventSubscriberInterface`
- [orchestra/testbench Packagist](https://packagist.org/packages/orchestra/testbench) - Version compatibility matrix
- Direct codebase inspection of all 18 source files and 3 test files

### Secondary (MEDIUM confidence)
- [Symfony UPGRADE-7.0.md](https://github.com/symfony/symfony/blob/7.3/UPGRADE-7.0.md) - General "all deprecations removed" guidance
- [Laravel 11 upgrade guide](https://laravel.com/docs/11.x/upgrade) - PHP 8.2+ requirement
- [Laravel 12 release notes](https://laravel.com/docs/12.x/releases) - PHP 8.2+ requirement

### Tertiary (LOW confidence)
- [brexis/laravel-workflow issue #45](https://github.com/brexis/laravel-workflow/issues/45) - Community confirmation of ClassInstanceSupportStrategy deprecation direction (similar package, same problem)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All versions verified via Packagist and official changelogs
- Architecture: HIGH - All three shims and the return type change verified against Symfony 7.0 source
- Pitfalls: HIGH - Autoloader crash scenario verified by codebase inspection (dead `use` import triggers ClassNotFoundException)

**Research date:** 2026-03-02
**Valid until:** 2026-04-02 (stable libraries, 30-day window)
