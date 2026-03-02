# Codebase Structure

**Analysis Date:** 2026-03-02

## Directory Layout

```
state-workflow/
├── .github/                         # GitHub workflows and actions
├── .idea/                           # IDE configuration
├── .planning/                       # Planning documents (generated)
├── config/
│   └── workflow.php                 # Workflow configuration definitions
├── database/
│   └── migrations/
│       └── create_state_workflow_histories_table.php
├── src/                             # Main library source code
│   ├── Console/
│   │   └── Commands/
│   │       └── StateWorkflowDumpCommand.php
│   ├── Events/
│   │   ├── BaseEvent.php
│   │   ├── CompletedEvent.php
│   │   ├── EnterEvent.php
│   │   ├── EnteredEvent.php
│   │   ├── GuardEvent.php
│   │   ├── LeaveEvent.php
│   │   └── TransitionEvent.php
│   ├── Interfaces/
│   │   ├── StateWorkflowInterface.php
│   │   ├── WorkflowEventSubscriberInterface.php
│   │   └── WorkflowRegistryInterface.php
│   ├── Models/
│   │   └── StateWorkflowHistory.php
│   ├── Subscribers/
│   │   ├── WorkflowSubscriber.php
│   │   └── WorkflowSubscriberHandler.php
│   ├── Traits/
│   │   └── HasWorkflowTrait.php
│   ├── Workflow/
│   │   ├── MethodMarkingStore.php
│   │   └── StateWorkflow.php
│   ├── StateWorkflowServiceProvider.php
│   └── WorkflowRegistry.php
├── tests/
│   ├── Fixtures/
│   │   ├── Models/
│   │   │   └── User.php
│   │   ├── Subscriber/
│   │   │   └── UserEventSubscriber.php
│   │   ├── Traits/
│   │   │   └── ConfigTrait.php
│   │   ├── database/
│   │   │   └── migrations/
│   │   │       └── add_new_column_to_users_table.php
│   │   └── Helpers.php
│   ├── Unit/
│   │   └── UserUnitTest.php
│   ├── TestCase.php
│   └── WorkflowSubscriberTest.php
├── vendor/                          # Composer dependencies (excluded from git)
├── .editorconfig
├── .gitattributes
├── .gitignore
├── .styleci.yml                     # Code style configuration
├── LICENSE.md
├── README.md
├── composer.json
├── composer.lock
└── phpunit.xml
```

## Directory Purposes

**config/:**
- Purpose: Framework configuration published to consuming Laravel applications
- Contains: Workflow definitions for domain entities, state/transition rules
- Key files: `workflow.php` - Primary configuration file for workflow setup

**database/migrations/:**
- Purpose: Database schema definitions
- Contains: Migration file for audit trail table
- Key files: `create_state_workflow_histories_table.php` - Defines state_workflow_histories table

**src/:**
- Purpose: Main library code (PSR-4 namespace: Ringierimu\StateWorkflow\)
- Contains: All production code for the state workflow library
- Key files: StateWorkflowServiceProvider.php, WorkflowRegistry.php

**src/Console/Commands/:**
- Purpose: Artisan command implementations
- Contains: CLI commands for developers
- Key files: `StateWorkflowDumpCommand.php` - Generates workflow diagrams via Graphviz

**src/Events/:**
- Purpose: Event classes that wrap Symfony workflow events
- Contains: Event wrappers for each workflow lifecycle stage
- Key files: BaseEvent.php (base class), GuardEvent.php, LeaveEvent.php, TransitionEvent.php, EnterEvent.php, EnteredEvent.php, CompletedEvent.php

**src/Interfaces/:**
- Purpose: Contract definitions for extension points
- Contains: Interface definitions for workflow components
- Key files: `StateWorkflowInterface.php` (workflow state access), `WorkflowRegistryInterface.php` (workflow retrieval), `WorkflowEventSubscriberInterface.php` (event subscription)

**src/Models/:**
- Purpose: Eloquent models for persistence
- Contains: Domain models for the library
- Key files: `StateWorkflowHistory.php` - Audit log model (polymorphic)

**src/Subscribers/:**
- Purpose: Event subscription and handling
- Contains: Workflow event dispatching and custom subscriber support
- Key files: `WorkflowSubscriber.php` (core event dispatcher), `WorkflowSubscriberHandler.php` (abstract base for custom subscribers)

**src/Traits/:**
- Purpose: Reusable trait components for model integration
- Contains: Trait definitions to add workflow behavior to models
- Key files: `HasWorkflowTrait.php` - Provides workflow methods to models

**src/Workflow/:**
- Purpose: Core workflow execution logic
- Contains: Workflow and state management implementations
- Key files: `StateWorkflow.php` (Symfony Workflow extension), `MethodMarkingStore.php` (state persistence via method/property access)

**tests/:**
- Purpose: Test suite for the library
- Contains: Unit tests, fixtures, test utilities
- Key files: `TestCase.php` (base test class), `WorkflowSubscriberTest.php` (subscriber tests), `Unit/UserUnitTest.php` (workflow functionality tests)

**tests/Fixtures/:**
- Purpose: Test doubles and example implementations
- Contains: Mock models, example subscribers, test helpers
- Key files: `Models/User.php` (test user model), `Subscriber/UserEventSubscriber.php` (example subscriber implementation)

## Key File Locations

**Entry Points:**
- `src/StateWorkflowServiceProvider.php`: Bootstrap entry point for Laravel
- `src/WorkflowRegistry.php`: Runtime entry point for workflow retrieval
- `src/Traits/HasWorkflowTrait.php`: Integration point for domain models

**Configuration:**
- `config/workflow.php`: Workflow definitions (states, transitions, subscribers)
- `phpunit.xml`: Test runner configuration
- `.styleci.yml`: Code style rules

**Core Logic:**
- `src/Workflow/StateWorkflow.php`: Workflow state machine logic (extends Symfony\Component\Workflow\Workflow)
- `src/WorkflowRegistry.php`: Workflow instance creation and management
- `src/Workflow/MethodMarkingStore.php`: State persistence mechanism
- `src/Subscribers/WorkflowSubscriber.php`: Event lifecycle management

**Testing:**
- `tests/TestCase.php`: Base test class with database setup
- `tests/WorkflowSubscriberTest.php`: Test event subscription behavior
- `tests/Unit/UserUnitTest.php`: Test workflow functionality on models
- `phpunit.xml`: PHPUnit configuration

## Naming Conventions

**Files:**
- PHP classes: PascalCase (e.g., `StateWorkflow.php`, `HasWorkflowTrait.php`)
- Migrations: snake_case with timestamp prefix (e.g., `2022_01_01_000000_create_state_workflow_histories_table.php`)
- Config files: snake_case (e.g., `workflow.php`)

**Directories:**
- Namespace-aligned directories: PascalCase (e.g., `Console`, `Events`, `Interfaces`)
- Plural for collections: (e.g., `Events/`, `Subscribers/`, `Models/`)
- Singular for behavior: (e.g., `Traits/`)

**Classes:**
- Concrete implementations: PascalCase noun (e.g., `StateWorkflow`, `WorkflowRegistry`)
- Abstract base classes: Prefixed with Abstract (e.g., none in codebase, but WorkflowSubscriberHandler is abstract)
- Traits: HasXxx or XxxTrait pattern (e.g., `HasWorkflowTrait`)
- Interfaces: XxxInterface pattern (e.g., `StateWorkflowInterface`)
- Events: XxxEvent pattern (e.g., `GuardEvent`, `TransitionEvent`)

**Methods:**
- Public workflow methods: camelCase action verb (e.g., `applyTransition()`, `canTransition()`, `getEnabledTransition()`)
- Event handler methods in subscribers: `on` prefix followed by event name (e.g., `onGuard()`, `onGuardActivate()`, `onEnterActivated()`)
- Internal/protected methods: camelCase with descriptive names (e.g., `getWorkflowInstance()`, `getMarkingStoreInstance()`)

**Properties:**
- Protected/private: camelCase prefixed with $ (e.g., `$workflow`, `$context`, `$config`)
- Constants: UPPER_SNAKE_CASE (none defined in library)

## Where to Add New Code

**New Event Type:**
- Create file: `src/Events/XxxEvent.php`
- Extend: `BaseEvent` class
- Location: Add to appropriate stage in lifecycle (Guard, Leave, Transition, Enter, Entered, Completed)
- Update: `WorkflowSubscriber.php` to handle new event
- Example: See `src/Events/GuardEvent.php` through `src/Events/CompletedEvent.php`

**New Marking Store Implementation:**
- Create file: `src/Workflow/XxxMarkingStore.php`
- Implement: `Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface`
- Location: Alongside `MethodMarkingStore.php` in `src/Workflow/`
- Configuration: Reference in workflow config under `marking_store.class`

**New Command:**
- Create file: `src/Console/Commands/XxxCommand.php`
- Extend: `Symfony\Component\Console\Command\Command` or `Illuminate\Console\Command`
- Register: Add to `StateWorkflowServiceProvider.registerCommands()` method
- Location: Alongside `StateWorkflowDumpCommand.php`

**Custom Event Subscriber:**
- Create file: `app/Listeners/XxxEventSubscriber.php` (or per-domain location)
- Extend: `Ringierimu\StateWorkflow\Subscribers\WorkflowSubscriberHandler`
- Methods: Use `on` prefix for event handlers (e.g., `onGuard()`, `onGuardActivate()`)
- Register: Add to `config/workflow.php` under `subscriber` key for workflow
- Location: Consumer application, not library

**New Workflow:**
- Add configuration to: `config/workflow.php`
- Define: `class`, `states`, `transitions`, optional `subscriber`
- Usage: Add `HasWorkflowTrait` to model, access via `$model->workflow()`

**Test Fixtures:**
- Models: `tests/Fixtures/Models/XxxModel.php`
- Subscribers: `tests/Fixtures/Subscriber/XxxEventSubscriber.php`
- Traits: `tests/Fixtures/Traits/XxxTrait.php`
- Location: Mirror production structure under `tests/Fixtures/`

## Special Directories

**vendor/:**
- Purpose: Composer dependencies
- Generated: Yes (via `composer install`)
- Committed: No (git-ignored)

**tests/Fixtures/:**
- Purpose: Test doubles and example implementations
- Generated: No (manually created)
- Committed: Yes (part of test suite)

**.git/:**
- Purpose: Git version control metadata
- Generated: Yes (automatic)
- Committed: N/A (special directory)

**.idea/:**
- Purpose: JetBrains IDE configuration
- Generated: Yes (by IDE)
- Committed: No (git-ignored)

**.planning/codebase/:**
- Purpose: Generated architecture and analysis documents
- Generated: Yes (by GSD analysis commands)
- Committed: Typically yes (useful for team reference)

---

*Structure analysis: 2026-03-02*
