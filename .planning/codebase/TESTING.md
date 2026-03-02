# Testing Patterns

**Analysis Date:** 2026-03-02

## Test Framework

**Runner:**
- PHPUnit 10.0 or 11.0 (specified in `composer.json`: `"phpunit/phpunit": "^10.0|^11.0"`)
- Config: `phpunit.xml`

**Assertion Library:**
- PHPUnit's built-in assertions (assertEquals, assertTrue, assertFalse, etc.)
- Mockery 1.3+ for mocking (specified in `composer.json`: `"mockery/mockery": "^1.3|^1.4.2"`)

**Test Base Class:**
- Orchestra Testbench 8.0+ for Laravel integration testing
- Base test class: `Ringierimu\StateWorkflow\Tests\TestCase` (located in `tests/TestCase.php`)

**Run Commands:**
```bash
composer test              # Run all tests (defined in composer.json scripts)
./vendor/bin/phpunit       # Direct PHPUnit execution
./vendor/bin/phpunit --filter TestName  # Run specific test
./vendor/bin/phpunit --coverage-html    # Generate coverage report
```

## Test File Organization

**Location:**
- Co-located pattern: Tests in `tests/` directory mirroring logical grouping
- `tests/Unit/` - Unit tests
- `tests/Fixtures/` - Test fixtures, models, subscribers, migrations, helpers, traits
- Test files in root of `tests/` for integration tests

**Naming:**
- Test files: `*Test.php` suffix (e.g., `UserUnitTest.php`, `WorkflowSubscriberTest.php`)
- Fixture files: Same naming as source (e.g., `User.php` for test model, `UserEventSubscriber.php` for test subscriber)

**Structure:**
```
tests/
├── Unit/
│   └── UserUnitTest.php
├── Fixtures/
│   ├── database/
│   │   └── migrations/
│   │       └── add_new_column_to_users_table.php
│   ├── Models/
│   │   └── User.php
│   ├── Subscriber/
│   │   └── UserEventSubscriber.php
│   ├── Traits/
│   │   └── ConfigTrait.php
│   └── Helpers.php
├── TestCase.php
└── WorkflowSubscriberTest.php
```

## Test Structure

**Suite Organization:**

From `phpunit.xml`:
```xml
<testsuites>
  <testsuite name="Test Suite">
    <directory suffix="Test.php">./tests</directory>
  </testsuite>
</testsuites>
```

All tests discovered automatically by finding files matching `*Test.php` pattern.

**Patterns:**

**Setup Pattern** - From `TestCase.php`:
```php
abstract class TestCase extends OrchestraTestCase
{
    use ConfigTrait;
    use RefreshDatabase;
    use WithFaker;

    /** @var User */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::create($this->userFactory());

        auth()->login(User::create($this->userFactory()));
    }

    protected function getPackageProviders($app)
    {
        return [StateWorkflowServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('workflow', $this->getWorflowConfig());
        parent::getEnvironmentSetUp($app);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(base_path('migrations'));
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/database/migrations/');
    }

    private function userFactory(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
            'remember_token' => Str::random(10),
            'user_state' => 'new',
        ];
    }
}
```

**Teardown Pattern:**
- Automatic via `RefreshDatabase` trait - Refreshes database after each test

**Assertion Pattern** - From `UserUnitTest.php`:
```php
public function test_it_return_workflow_instance()
{
    $this->assertInstanceOf(StateWorkflow::class, $this->user->workflow());
}

public function test_is_current_state_new()
{
    $this->assertEquals('new', $this->user->state());
}

public function test_can_apply_transition()
{
    $this->assertTrue($this->user->canTransition('create'));
    $this->assertFalse($this->user->canTransition('block'));
}

public function test_apply_transitions()
{
    $this->user->applyTransition('create');
    $this->user = $this->user->refresh();
    $this->assertEquals('pending_activation', $this->user->state());
    $this->assertEquals(1, $this->user->stateHistory()->count());
}
```

**Test Method Naming:**
- `test_*` snake_case prefix: `test_if_workflow_subscriber_emit_events()`, `test_can_apply_transition()`
- Descriptive names: `test_invalid_transition_throws_exception()`, `test_apply_transitions()`

## Mocking

**Framework:** Mockery 1.3+ for object mocking

**Patterns from test infrastructure:**

Global mock function pattern in `WorkflowSubscriberTest.php`:
```php
namespace {
    $events = null;

    if (!function_exists('event')) {
        function event($ev)
        {
            global $events;
            $events[] = $ev;
        }
    }
}
```

This mocks Laravel's `event()` helper to capture fired events for assertion.

**Event Capture Pattern:**
```php
public function test_if_workflow_subscriber_emit_events()
{
    global $events;
    $events = [];

    $workflowRegistry = new WorkflowRegistry($this->getWorflowConfig());
    $workflow = $workflowRegistry->get($this->user);

    $workflow->apply($this->user, 'create');
    $this->assertCount(24, $events);

    $this->assertEquals('workflow.guard', $events[0]);
    $this->assertEquals('workflow.user.guard', $events[1]);
    // ... more assertions
}
```

**What to Mock:**
- Global functions (like `event()` helper) via namespace-level mocks
- External event dispatcher when testing event flow
- Model relationships via RefreshDatabase and factory fixtures

**What NOT to Mock:**
- Service dependencies that are being tested (use real instances)
- Database operations (use RefreshDatabase + test database)
- Eloquent models (use actual fixture models with test database)
- Configuration (set via `getEnvironmentSetUp()` in test base class)

## Fixtures and Factories

**Test Data:**

User fixture model in `tests/Fixtures/Models/User.php`:
```php
class User extends Authenticatable
{
    use HasWorkflowTrait;

    protected $fillable = [
        'name', 'email', 'password', 'user_state',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
```

User data factory in `TestCase.php`:
```php
private function userFactory(): array
{
    return [
        'name' => $this->faker->name,
        'email' => $this->faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
        'remember_token' => Str::random(10),
        'user_state' => 'new',
    ];
}
```

Workflow configuration fixture in `tests/Fixtures/Traits/ConfigTrait.php`:
```php
public function getWorflowConfig()
{
    return [
        'user' => [
            'class' => User::class,
            'subscriber' => UserEventSubscriber::class,
            'property_path' => 'user_state',
            'states' => [
                'new',
                'pending_activation',
                'activated',
                'deleted',
                'blocked',
            ],
            'transitions' => [
                'create' => [
                    'from' => 'new',
                    'to' => 'pending_activation',
                ],
                'activate' => [
                    'from' => 'pending_activation',
                    'to' => 'activated',
                ],
                // ... more transitions
            ],
        ],
    ];
}
```

**Location:**
- Fixture models: `tests/Fixtures/Models/`
- Fixture subscribers: `tests/Fixtures/Subscriber/`
- Fixture traits (config): `tests/Fixtures/Traits/`
- Fixture migrations: `tests/Fixtures/database/migrations/`
- Fixture helpers: `tests/Fixtures/Helpers.php`

**Pattern:**
- Use included helpers via `composer.json` `"include_files"` directive: `"tests/Fixtures/Helpers.php"`
- Traits for shared test configuration: `ConfigTrait` provides `getWorflowConfig()`
- Actual Eloquent models as fixtures (not mock objects)

## Coverage

**Requirements:**
- Not explicitly enforced in `phpunit.xml`
- No `<coverage>` block defined
- Coverage can be generated with `--coverage-html` flag

**View Coverage:**
```bash
./vendor/bin/phpunit --coverage-html ./coverage
```

## Test Types

**Unit Tests:**
- Scope: Individual methods and classes
- Approach: Test workflow state transitions, available transitions, invalid transitions
- Location: `tests/Unit/UserUnitTest.php`
- Example:
  ```php
  public function test_it_return_workflow_instance()
  {
      $this->assertInstanceOf(StateWorkflow::class, $this->user->workflow());
  }

  public function test_invalid_transition_throws_exception()
  {
      $expectedExceptionClass = class_exists(NotEnabledTransitionException::class)
          ? NotEnabledTransitionException::class
          : LogicException::class;
      $this->expectException($expectedExceptionClass);

      $this->user->applyTransition('block');
  }
  ```

**Integration Tests:**
- Scope: Workflow lifecycle, event emission, database persistence
- Approach: Test full transition sequences, event ordering, state history
- Location: `tests/WorkflowSubscriberTest.php`
- Example:
  ```php
  public function test_if_workflow_subscriber_emit_events()
  {
      global $events;
      $events = [];

      $workflowRegistry = new WorkflowRegistry($this->getWorflowConfig());
      $workflow = $workflowRegistry->get($this->user);

      $workflow->apply($this->user, 'create');
      $this->assertCount(24, $events);

      $this->assertEquals('workflow.guard', $events[0]);
      $this->assertEquals('workflow.user.guard', $events[1]);
      // ... more event assertions
  }
  ```

**E2E Tests:**
- Not detected in codebase
- For end-to-end testing, would extend `TestCase` with application-wide workflows

## Common Patterns

**Async Testing:**
Not applicable (PHP is synchronous). Uses `RefreshDatabase` for transaction rollback instead.

**Error Testing:**
```php
public function test_invalid_transition_throws_exception()
{
    // Handle version compatibility
    $expectedExceptionClass = class_exists(NotEnabledTransitionException::class)
        ? NotEnabledTransitionException::class
        : LogicException::class;

    // Expect the exception
    $this->expectException($expectedExceptionClass);

    // Trigger the exception
    $this->user->applyTransition('block');
}
```

**State Assertion Pattern:**
```php
public function test_apply_transitions()
{
    $this->user->applyTransition('create');
    $this->user = $this->user->refresh();
    $this->assertEquals('pending_activation', $this->user->state());
    $this->assertEquals(1, $this->user->stateHistory()->count());

    $this->user->applyTransition('activate');
    $this->user = $this->user->refresh();
    $this->assertEquals('activated', $this->user->state());
    $this->assertEquals(2, $this->user->stateHistory()->count());
}
```

**Database Assertion Pattern:**
- Refresh model after database operations: `$this->user = $this->user->refresh();`
- Count related records: `$this->user->stateHistory()->count()`
- Assert model attributes: `$this->assertEquals('pending_activation', $this->user->state())`

## Environment Configuration

**Test Environment Setup:**

From `phpunit.xml`:
```xml
<php>
  <env name="APP_ENV" value="testing"/>
  <env name="DB_CONNECTION" value="testing"/>
</php>
```

From `TestCase.php` setup:
```php
protected function getEnvironmentSetUp($app)
{
    $app['config']->set('workflow', $this->getWorflowConfig());
    parent::getEnvironmentSetUp($app);
}

protected function defineDatabaseMigrations()
{
    $this->loadMigrationsFrom(base_path('migrations'));
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    $this->loadMigrationsFrom(__DIR__ . '/Fixtures/database/migrations/');
}
```

---

*Testing analysis: 2026-03-02*
