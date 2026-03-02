# Phase 3: Pest Migration and Test Expansion - Research

**Researched:** 2026-03-02
**Domain:** PHP Testing (Pest 4 + Laravel Event Testing)
**Confidence:** HIGH

## Summary

Phase 3 migrates 2 existing PHPUnit test files (`UserUnitTest.php`, `WorkflowSubscriberTest.php`) to Pest 4 closure-based syntax and adds 3 new test scenarios (multi-workflow, event ordering, error handling). The migration is straightforward for `UserUnitTest` (pure assertion-based tests), but `WorkflowSubscriberTest` requires significant refactoring because it relies on a global `event()` function override in `tests/Fixtures/Helpers.php` via the `funkjedi/composer-include-files` package.

The critical architectural challenge is that `WorkflowSubscriber.php` (the Symfony event subscriber) calls Laravel's `event()` helper directly to dispatch events. In the current test setup, `Helpers.php` replaces this function to capture event names into a `$events` global array. The Pest migration must replace this with Laravel's `Event::fake()` pattern, which intercepts events at the dispatcher level rather than at the function level.

**Primary recommendation:** Install `pestphp/pest` v3 (latest stable for PHP 8.3+ and PHPUnit 11), `pestphp/pest-plugin-laravel` for Laravel-specific helpers, create `tests/Pest.php` with `uses()` binding, convert files incrementally, then add new test files.

## Standard Stack

### Core Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `pestphp/pest` | `^3.0` | Test framework (Pest v3 is current stable for PHP 8.3+, requires PHPUnit 11) |
| `pestphp/pest-plugin-laravel` | `^3.0` | Laravel-specific test helpers, artisan commands |

**Note on Pest versioning:** Pest v3 (not v4) is the current stable release line as of early 2026. It supports PHP 8.2+ and PHPUnit 11. The requirements doc references "Pest 4" but the actual latest stable is Pest 3.x. Plans should install `pestphp/pest: ^3.0`. If Pest 4 has been released by execution time, `^3.0` will still resolve correctly; if not, it installs the latest v3.

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| pestphp/pest | PHPUnit directly | Already decided to migrate to Pest — no alternatives |
| pest-plugin-laravel | Manual bindings | Plugin provides `artisan test` integration, `uses()` convenience |
| pest-plugin-drift | Manual conversion | Drift auto-converts PHPUnit to Pest syntax but may over-convert; manual is safer for 2 files |

**Installation:**
```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
```

The `--with-all-dependencies` flag is important because Pest 3 requires PHPUnit 11, and the current `composer.json` allows `^10.0|^11.0`. Composer may need to resolve upward.

## Architecture Patterns

### Pest.php Configuration

The `tests/Pest.php` file binds the base `TestCase` class for all test files:

```php
<?php

use Ringierimu\StateWorkflow\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
```

This makes every test file automatically extend `TestCase` (which extends Orchestra Testbench) without explicit class inheritance. The `ConfigTrait` and `RefreshDatabase` traits are already used by `TestCase`, so they propagate automatically.

**Key insight:** The `ConfigTrait::getWorflowConfig()` method (note: typo in original — `Worflow` not `Workflow`) is available to all Pest tests through the `TestCase` binding. In Pest closure syntax, access it via `$this->getWorflowConfig()` inside test closures.

### Test File Conversion Pattern

**PHPUnit (before):**
```php
class UserUnitTest extends TestCase
{
    public function test_it_return_workflow_instance()
    {
        $this->assertInstanceOf(StateWorkflow::class, $this->user->workflow());
    }
}
```

**Pest (after):**
```php
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;

test('it returns workflow instance', function () {
    expect($this->user->workflow())->toBeInstanceOf(StateWorkflow::class);
});
```

`$this->user` works because `TestCase::setUp()` creates the user and assigns it to `$this->user`. Pest closures are bound to the TestCase instance.

### Event Testing Pattern (Critical Refactor)

**Current approach (to be removed):**
- `tests/Fixtures/Helpers.php` overrides global `event()` function
- `composer.json` uses `funkjedi/composer-include-files` to autoload it
- `WorkflowSubscriberTest` uses `global $events` to capture dispatched events
- This is a namespace-level hack that breaks with Laravel's real event system

**New approach with Event::fake():**
```php
use Illuminate\Support\Facades\Event;
use Ringierimu\StateWorkflow\Events\GuardEvent;
use Ringierimu\StateWorkflow\Events\LeaveEvent;
use Ringierimu\StateWorkflow\Events\TransitionEvent;
use Ringierimu\StateWorkflow\Events\EnterEvent;
use Ringierimu\StateWorkflow\Events\EnteredEvent;
use Ringierimu\StateWorkflow\Events\CompletedEvent;

test('workflow subscriber emits events on transition', function () {
    Event::fake();

    $this->user->applyTransition('create');

    Event::assertDispatched(GuardEvent::class);
    Event::assertDispatched(LeaveEvent::class);
    Event::assertDispatched(TransitionEvent::class);
    Event::assertDispatched(EnterEvent::class);
    Event::assertDispatched(EnteredEvent::class);
    Event::assertDispatched(CompletedEvent::class);
});
```

**CRITICAL ARCHITECTURAL ISSUE:** The `WorkflowSubscriber` class dispatches events using `event('workflow.guard', $event)` — this calls Laravel's global `event()` helper with a **string event name** as the first argument and the event object as the second argument. `Event::fake()` intercepts calls to the event dispatcher, but `Event::assertDispatched()` typically checks for class-based events.

When `event('workflow.guard', $event)` is called:
1. Laravel's event dispatcher receives `'workflow.guard'` as the event name
2. The `$event` (a `GuardEvent` instance) is passed as a payload argument
3. `Event::assertDispatched(GuardEvent::class)` would NOT match because the dispatcher sees the string `'workflow.guard'`, not the class name

**Solution options:**
1. **Assert on string event names:** `Event::assertDispatched('workflow.guard')` — matches how events are actually dispatched
2. **Refactor WorkflowSubscriber to use class-based dispatch:** Change `event('workflow.guard', $event)` to `event($event)` — but this changes production code and is out of scope for a test-only phase
3. **Use Event::assertDispatched with string names for the string events, and class-based for class events** — the subscriber dispatches both string names AND the `WorkflowSubscriberHandler` registers listeners on class names via `$event->listen($this->key($method), ...)` where `key()` resolves to class names like `GuardEvent::class`

**After deeper analysis:** The `WorkflowSubscriberHandler::subscribe()` method registers listeners on event keys derived from method names (e.g., `onGuard` maps to `GuardEvent::class` via the `key()` method). But the actual dispatch in `WorkflowSubscriber` uses `event('workflow.guard', $event)` — these are STRING event dispatches. The `WorkflowSubscriberHandler`'s registration uses a different mechanism entirely.

**Recommended approach:** Use `Event::fake()` and assert on the string event names that are actually dispatched:
```php
Event::assertDispatched('workflow.guard');
Event::assertDispatched('workflow.user.guard');
Event::assertDispatched('workflow.user.guard.create');
```

For event ordering verification (TEST-05), use `Event::assertDispatched` with a closure to capture dispatch order, or use `Event::fake()` with `Event::dispatched()` to get the ordered list.

**However**, there is an additional complication: `Event::fake()` intercepts event dispatching, meaning the `WorkflowSubscriberHandler` listeners that are registered via `subscribe()` will NOT actually fire. This is the correct behavior for testing that events are dispatched, but it means `enteredEvent()` (which calls `$model->save()` and `$model->saveStateHistory()`) will not execute. The existing `UserUnitTest` tests rely on `applyTransition()` which triggers the full event chain including model save. If `Event::fake()` is active globally, those tests would break.

**Resolution:** Only use `Event::fake()` in the subscriber event test file, NOT globally. The `UserUnitTest` tests should continue to use the real event system (no faking). This is natural with Pest since `Event::fake()` is called per-test, not globally.

### Multi-Workflow Test Pattern (TEST-04)

The `ConfigTrait::getWorflowConfig()` currently returns a single workflow config for `'user'`. For multi-workflow testing, the config needs a second workflow entry. The CONTEXT.md specifies using a new state column (`subscription_state`) on the existing User model.

**Approach:**
1. Add `subscription_state` column to User model's `$fillable`
2. Create a migration fixture for the column (or extend existing migration)
3. Define a second workflow config with key `'user_subscription'` (or similar) pointing to the same `User::class` but different `property_path`
4. Test that both workflows can be applied independently

**Key concern:** The `HasWorkflowTrait::workflow()` method caches the workflow in `$this->workflow`. For multi-workflow, the model needs to support passing a workflow name. Looking at the code: `workflow()` calls `app(WorkflowRegistryInterface::class)->get($this, $this->configName())`. The `configName()` method derives the config key from the class name. For a second workflow, we need to call `get($this, 'user_subscription')` directly via the registry rather than through the trait helper.

### Error Handling Test Pattern (TEST-06)

Test that when a subscriber handler throws an exception, it propagates to the caller:

```php
test('subscriber exception propagates to caller', function () {
    // Register a subscriber that throws
    // Apply transition
    // Assert exception is thrown, transition does not silently succeed
});
```

This requires a test-only subscriber fixture that throws an exception in one of its `on*` methods. The existing `UserEventSubscriber` only logs — a new fixture or a modified config is needed.

## Don't Hand-Roll

| Use This | Instead of | Reason |
|----------|------------|--------|
| `pestphp/pest` | Custom test runner | Standard PHP testing framework |
| `uses()` in Pest.php | Manual TestCase extension | Pest's built-in mechanism |
| `expect()` API | `$this->assert*()` | Pest-native, more readable |
| `Event::fake()` | Global function override | Laravel's official testing pattern |
| `Event::assertDispatched()` | Manual event tracking | Built-in assertion with proper failure messages |

## Common Pitfalls

### 1. Helpers.php Autoload Conflict (HIGH confidence)
**Problem:** The `funkjedi/composer-include-files` package loads `tests/Fixtures/Helpers.php` which overrides the global `event()` function. If this runs when Pest boots, it will override Laravel's `event()` helper before any test runs, breaking `Event::fake()`.
**Prevention:** Remove the `Helpers.php` file AND the `extra.include_files` entry from `composer.json`, then run `composer dump-autoload`. The `funkjedi/composer-include-files` package can also be removed from `require-dev` if no other included files remain.

### 2. TestCase setUp() Compatibility (HIGH confidence)
**Problem:** Pest's `uses()` binding requires that `setUp()` is called `setUp(): void` (PHPUnit 10+ style). The existing `TestCase` already uses this signature. No issue expected, but verify.
**Prevention:** Confirm `TestCase::setUp()` signature matches `public function setUp(): void`.

### 3. WorkflowSubscriberTest Namespace Block (HIGH confidence)
**Problem:** The current `WorkflowSubscriberTest.php` uses a namespace block syntax `namespace Ringierimu\StateWorkflow\Tests { ... }` which is unusual. This was likely done to work with the global `event()` override. Pest files don't use class/namespace blocks.
**Prevention:** Convert to standard Pest file format — no namespace block needed since Pest.php handles test binding.

### 4. Event::fake() Scope (HIGH confidence)
**Problem:** `Event::fake()` is global within a test — if called in a test that also needs real event dispatching (like model save in `enteredEvent`), the save won't happen.
**Prevention:** Only use `Event::fake()` in tests specifically testing event dispatch. For tests that need the full workflow (model state changes, history records), do NOT fake events.

### 5. Pest Plugin Version Mismatch (MEDIUM confidence)
**Problem:** `pestphp/pest-plugin-laravel` version must match `pestphp/pest` major version.
**Prevention:** Install both together: `composer require pestphp/pest pestphp/pest-plugin-laravel --dev`.

### 6. Random Order Test Isolation (MEDIUM confidence)
**Problem:** `RefreshDatabase` should handle isolation, but if tests share static state or class-level caches (like the `$workflow` property on the User model), random ordering might fail.
**Prevention:** Ensure the User model's `$workflow` cache is reset between tests (it's an instance property, so new instances from `RefreshDatabase` should handle this). Verify with `vendor/bin/pest --order-by=random`.

### 7. composer.json scripts update (HIGH confidence)
**Problem:** Current `scripts.test` is `"phpunit"`. Needs to be `"vendor/bin/pest"` or Pest won't run via `composer test`.
**Prevention:** Update the `scripts.test` entry in `composer.json`.

## Open Questions

1. **Pest v3 vs v4 naming**
   - What we know: The requirements doc says "Pest 4" but current latest stable is Pest v3.x
   - What's unclear: Whether Pest 4 exists by execution time
   - Recommendation: Use `^3.0` constraint — if v4 drops, it likely requires a new major constraint anyway. The behavior is the same regardless of version naming.

2. **funkjedi/composer-include-files removal**
   - What we know: Only used for `tests/Fixtures/Helpers.php` autoloading
   - What's unclear: Whether any other code depends on this package
   - Recommendation: Remove both the package and the Helpers.php file. Check that no other `extra.include_files` entries exist.

3. **Second workflow config name**
   - What we know: Need a second workflow key for multi-workflow test
   - What's unclear: Whether the `HasWorkflowTrait::configName()` method supports multiple workflows per model
   - Recommendation: Test via `WorkflowRegistry::get($user, 'second_key')` directly rather than through the trait convenience method.

---

*Phase: 03-pest-migration-and-test-expansion*
*Research completed: 2026-03-02*
