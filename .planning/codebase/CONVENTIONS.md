# Coding Conventions

**Analysis Date:** 2026-03-02

## Naming Patterns

**Files:**
- PascalCase for classes: `StateWorkflow.php`, `WorkflowRegistry.php`, `HasWorkflowTrait.php`
- camelCase for method files: `StateWorkflowServiceProvider.php`, `WorkflowSubscriberHandler.php`
- Event files are named after the event type: `GuardEvent.php`, `LeaveEvent.php`, `TransitionEvent.php`
- Test files use PascalCase with `Test` suffix: `UserUnitTest.php`, `WorkflowSubscriberTest.php`

**Functions/Methods:**
- camelCase for all methods: `guardEvent()`, `leaveEvent()`, `getState()`, `applyTransition()`
- Getter methods use `get` prefix: `getState()`, `getWorkflowInstance()`, `getMarkingStoreInstance()`
- Action methods use verbs: `applyTransition()`, `canTransition()`, `registerWorkflow()`
- Private methods use underscore prefix naming convention: `getWorkflowClass()`, `getMarkingStoreInstance()`
- Event handler methods use `on` prefix followed by PascalCase event type: `onGuard()`, `onLeave()`, `onEnter()`, `onEntered()`, `onCompleted()`, `onGuardActivate()`, `onLeavePendingActivation()`

**Variables:**
- camelCase for all variables: `$workflowRegistry`, `$dispatcher`, `$config`, `$markingStore`
- Protected/private properties use `$` prefix: `$registry`, `$dispatcher`, `$workflow`, `$context`
- Array variables use plural names: `$config`, `$events`, `$transitions`, `$states`
- Loop variables use singular naming: `foreach ($places as $place)`, `foreach ($transitions as $transitionName => $transition)`

**Types:**
- Interface names use `Interface` suffix: `WorkflowRegistryInterface`, `StateWorkflowInterface`, `WorkflowEventSubscriberInterface`
- Trait names use `Trait` suffix: `HasWorkflowTrait`, `ConfigTrait`, `RefreshDatabase`
- Abstract classes use `Abstract` prefix when needed: `WorkflowSubscriberHandler` (abstract base)
- Exception classes follow Laravel/Symfony conventions from dependencies

**Constants:**
- None explicitly defined in codebase; follows PSR-1 UPPER_SNAKE_CASE pattern implicitly

## Code Style

**Formatting:**
- Style CI enforces PSR-12 compliance via `.styleci.yml`
- 4-space indentation
- No trailing whitespace
- Blank lines between methods and logical sections
- Single blank line after opening PHP tags

**Linting:**
- StyleCI with PSR-12 preset (`preset: psr12` in `.styleci.yml`)
- Version 8 enforcement
- Enabled rules include:
  - `alpha_ordered_imports` - Imports alphabetically ordered
  - `phpdoc_*` - Comprehensive PHPDoc rules (phpdoc_add_missing_param_annotation, phpdoc_order, phpdoc_separation, phpdoc_types, etc.)
  - `no_*` - Strict unused/empty code removal (no_unused_imports, no_empty_phpdoc, no_empty_statement, etc.)
  - `trailing_comma_in_multiline_array` - Multiline arrays have trailing commas
  - `short_array_syntax` - Use `[]` instead of `array()`
  - `object_operator_without_whitespace` - No spaces around `->`

**Line Length:** No explicit limit enforced; typical lines stay under 100 characters

## Import Organization

**Order:**
1. `use` statements for built-in PHP classes (Exception, ReflectionClass)
2. Laravel framework imports (Illuminate\*)
3. Package vendor imports (Symfony\Component\*, Orchestra\*)
4. Local package imports (Ringierimu\StateWorkflow\*)

**Path Aliases:**
- No path aliases detected; uses fully qualified namespaces throughout

**Example from `WorkflowRegistry.php`:**
```php
<?php
namespace Ringierimu\StateWorkflow;

use Exception;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Ringierimu\StateWorkflow\Interfaces\WorkflowEventSubscriberInterface;
use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Ringierimu\StateWorkflow\Subscribers\WorkflowSubscriber;
use Ringierimu\StateWorkflow\Workflow\MethodMarkingStore;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
```

## Error Handling

**Patterns:**
- Exceptions explicitly thrown with descriptive messages: `throw new Exception("$class must implements " . WorkflowEventSubscriberInterface::class);`
- Conditional exception handling for backward compatibility with Symfony versions:
  ```php
  $expectedExceptionClass = class_exists(NotEnabledTransitionException::class)
      ? NotEnabledTransitionException::class
      : LogicException::class;
  $this->expectException($expectedExceptionClass);
  ```
- Try-catch used implicitly through method propagation; no explicit try blocks in source code
- ReflectionException documented in PHPDoc: `@throws \ReflectionException`

**Error Types Used:**
- `Exception` - Generic exceptions with custom messages
- `ReflectionException` - From PHP reflection API
- Symfony exceptions (NotEnabledTransitionException, LogicException) - From workflow component

## Logging

**Framework:** No dedicated logging in source code; test fixtures use `Illuminate\Support\Facades\Log`

**Patterns from tests:**
- `Log::info(__METHOD__)` - Logs method entry with current method name
- `Log::info('descriptive message: ' . $value)` - Logs with string concatenation
- Used in event subscriber handlers: `UserEventSubscriber::onGuard()`, `onLeave()`, `onEntered()`

**Convention:**
- Use `__METHOD__` magic constant for method identification
- Concatenate values with descriptive labels
- Log at `info` level for event tracking

## Comments

**When to Comment:**
- Class-level DocBlock required: All classes have `/** Class ClassName. */` format
- Method-level DocBlock required for public methods: Include `@param`, `@return`, `@throws` tags
- Inline comments for complex logic or non-obvious behavior
- Comments explain "why" not "what" (code shows what)

**JSDoc/TSDoc:**
- Uses PHPDoc standard (PHP equivalent of JSDoc)
- Format: `/** ... */` blocks
- Parameters documented: `@param Type $name Description`
- Returns documented: `@return Type Description`
- Exceptions documented: `@throws ExceptionType`
- Properties documented with `@var Type` in class docblocks

**Example from `WorkflowRegistry.php`:**
```php
/**
 * Add a workflow to the registry from array.
 *
 * @param       $name
 * @param array $workflowData
 *
 * @throws \ReflectionException
 */
public function addWorkflows($name, array $workflowData)
```

**Example from `WorkflowSubscriberHandler.php`:**
```php
/**
 * Generate event key from Subscriber method to match workflow event dispatcher names.
 *
 * Format on how to register method to listen to specific workflow events.
 *
 * eg.
 * 1. on[Event] - onGuard
 * 2. on[Event][Transition/State name] - onGuardActivate
 *
 * NB:
 * - Guard and Transition event uses of transition name
 * - Leave, Enter and Entered event uses state name
 *
 * ******* Fired Events *********
 * - Guard Event
 * workflow.guard
 * workflow.[workflow name].guard
 * workflow.[workflow name].guard.[transition name]
 * ...
 *
 * @param $name
 *
 * @return string
 */
protected function key($name)
```

## Function Design

**Size:** Methods range from 3-50 lines; most under 25 lines

**Parameters:**
- Use type hints for all parameters: `string $name`, `array $config`, `MarkingStoreInterface $markingStore`
- Maximum 4-5 parameters; use arrays for configuration
- Optional parameters have defaults: `string $name = 'unnamed'`, `$override = true`

**Return Values:**
- Explicitly type-hinted in modern code: `public function getState($object)`, `protected function getMarkingStoreInstance(array $workflowData)`
- Return objects for chaining: `return $this->workflow()->getState($this);`
- Return arrays for multiple values: `protected function getMarkingStoreInstance(array $workflowData)`
- Void returns for side effects: Event handlers and subscribers

**Example from `StateWorkflow.php`:**
```php
/**
 * Returns the current state.
 *
 * @param $object
 *
 * @return mixed
 */
public function getState($object)
{
    $accessor = new PropertyAccessor();
    $propertyPath = isset($this->config['property_path']) ? $this->config['property_path'] : 'current_state';

    return $accessor->getValue($object, $propertyPath);
}
```

## Module Design

**Exports:**
- No explicit module exports; uses Laravel service provider pattern
- `StateWorkflowServiceProvider` registers singleton: `'stateWorkflow'` aliased to `WorkflowRegistryInterface`
- Classes exported via namespace: `namespace Ringierimu\StateWorkflow\*;`

**Barrel Files:**
- No barrel files detected; each class in separate file following PSR-4 namespace structure

**Service Provider Pattern (from `StateWorkflowServiceProvider`):**
```php
$this->app->singleton('stateWorkflow', function () {
    return new WorkflowRegistry(
        collect($this->app['config']->get('workflow'))
            ->except('setup')
            ->toArray()
    );
});

$this->app->alias('stateWorkflow', WorkflowRegistryInterface::class);
```

---

*Convention analysis: 2026-03-02*
