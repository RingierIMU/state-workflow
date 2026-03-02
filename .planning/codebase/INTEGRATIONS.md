# External Integrations

**Analysis Date:** 2026-03-02

## APIs & External Services

**Workflow Engine:**
- Symfony Workflow Component (v5.1+) - Core state machine and workflow definition system
  - SDK/Client: `symfony/workflow` package
  - Integration point: `src/WorkflowRegistry.php` and `src/Workflow/StateWorkflow.php`

**Event System:**
- Symfony Event Dispatcher (v6.0+/v7.0+) - Event-driven architecture for workflow transitions
  - SDK/Client: `symfony/event-dispatcher` package
  - Integration point: Event subscribers and listeners in `src/Events/` and `src/Subscribers/`
- Laravel Event Dispatcher - Native Laravel event system
  - SDK/Client: `illuminate/events` package
  - Integration point: `src/WorkflowRegistry.php` uses `Illuminate\Support\Facades\Event`

**Property/Reflection Access:**
- Symfony Property Access (v5.1+) - Dynamic object property access
  - SDK/Client: `symfony/property-access` package
  - Integration point: `src/Workflow/MethodMarkingStore.php` for state tracking

## Data Storage

**Primary Database:**
- Type: Any Laravel-supported relational database (MySQL, PostgreSQL, SQLite)
- Connection: Configured via Laravel `.env` (default connection)
- Client: Eloquent ORM (Laravel)
- Configuration: `phpunit.xml` sets `DB_CONNECTION=testing` for test environment

**Data Models:**
- `state_workflow_histories` table - Workflow state transition audit log
  - ORM Model: `src/Models/StateWorkflowHistory.php`
  - Schema: Migration in `database/migrations/create_state_workflow_histories_table.php`
  - Fields: model_name, model_id, transition, from, to, user_id, context (JSON), timestamps

## Authentication & Identity

**Auth Provider:**
- Native Laravel Authentication
  - Implementation: Configuration-based user class via `config/workflow.php` under `setup.user_class`
  - Default: `\App\User::class` (configured in `config/workflow.php`)
  - Integration: User authentication via Laravel's `auth()` facade in `src/Traits/HasWorkflowTrait.php`

**Current User Resolution:**
- Method: `auth()->id()` via Laravel's global auth helper
- Integration point: `HasWorkflowTrait::authenticatedUserId()` in `src/Traits/HasWorkflowTrait.php`
- Usage: Tracks which user triggered state transitions in `state_workflow_histories` table

## Monitoring & Observability

**Error Tracking:**
- Not detected - No external error tracking service configured

**Logs:**
- Approach: Event-based workflow history logging
- Implementation: `StateWorkflowHistory` model automatically records all state transitions
- Location: `state_workflow_histories` table with context data (JSON) and user tracking

**Debug Output:**
- GraphvizDumper support: `symfony/process` for workflow diagram generation (used in `StateWorkflowDumpCommand`)

## CI/CD & Deployment

**Hosting:**
- Package distribution: Packagist (composer package)
- Source: GitHub (`https://github.com/RingierIMU/state-workflow`)

**CI Pipeline:**
- Service: GitHub Actions
- Workflow file: `.github/workflows/main.yml`
- Matrix testing: PHP 8.1-8.3 × Laravel 10/11/12 × prefer-lowest/stable
- Commands: Composer install + PHPUnit

**Package Integration:**
- Service Provider: Auto-registration via `StateWorkflowServiceProvider`
- Configuration Publishing: `php artisan vendor:publish --tag="state-workflow-config"`
- Migration Publishing: `php artisan vendor:publish --tag="state-workflow-migration"`

## Environment Configuration

**Required env vars:**
- None explicitly required by the package
- Inherits from parent Laravel application:
  - `DB_CONNECTION` - Database connection type
  - `APP_ENV` - Application environment

**Configuration File:**
- `config/workflow.php` - Primary package configuration
  - Defines all workflows (domain entities)
  - Specifies states and transitions per workflow
  - Registers subscriber classes for business logic
  - Optional: custom property paths for state attributes
  - Optional: custom marking store implementations

**Secrets location:**
- No secrets are stored in the package
- User model class is configured in `config/workflow.php`
- Parent Laravel application manages all sensitive configuration

## Webhooks & Callbacks

**Incoming:**
- None detected

**Outgoing:**
- Event-based callbacks: Workflow transitions dispatch events that can be subscribed to
- Integration points:
  - `src/Subscribers/WorkflowSubscriber.php` - Default workflow event handler
  - Custom subscribers via `WorkflowEventSubscriberInterface` in `src/Interfaces/WorkflowEventSubscriberInterface.php`
  - Event classes: `src/Events/` directory (EnterEvent, EnteredEvent, LeaveEvent, TransitionEvent, CompletedEvent, GuardEvent)

**Event Flow:**
- Events are dispatched via Symfony EventDispatcher
- Subscribers can listen to specific workflow transitions
- Custom business logic can be registered via subscriber classes in workflow configuration

## Integration Patterns

**Service Container:**
- Binding: `stateWorkflow` singleton service registered in `StateWorkflowServiceProvider::register()`
- Alias: Bound to `WorkflowRegistryInterface` for dependency injection
- Access: `app(WorkflowRegistryInterface::class)` or facade-like access via `app('stateWorkflow')`

**Trait-Based Integration:**
- Pattern: `HasWorkflowTrait` added to any Eloquent model to enable workflow functionality
- Usage: Model gains workflow access via `$model->workflow()`, `$model->applyTransition()`, etc.
- History: Automatically tracked via `morphMany` relationship to `state_workflow_histories`

**Configuration-Driven:**
- Workflows defined declaratively in `config/workflow.php`
- No code changes needed to add new workflows or transitions
- Supports multiple workflows per application

---

*Integration audit: 2026-03-02*
