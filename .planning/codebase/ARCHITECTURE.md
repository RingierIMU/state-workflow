# Architecture

**Analysis Date:** 2026-03-02

## Pattern Overview

**Overall:** State Machine with Event-Driven Architecture

**Key Characteristics:**
- Laravel service provider integration for dependency injection
- Symfony Workflow component as the foundation for state machine logic
- Event-driven architecture with Laravel's event dispatcher
- Trait-based mixin for model integration
- Configuration-driven workflow definitions
- Audit trail via database history tracking

## Layers

**Configuration Layer:**
- Purpose: Define workflow structure, states, transitions, and subscribers
- Location: `config/workflow.php`
- Contains: Workflow definitions for each domain entity
- Depends on: None
- Used by: Service provider, WorkflowRegistry

**Service Provider / Bootstrap Layer:**
- Purpose: Register and initialize the workflow system into Laravel container
- Location: `src/StateWorkflowServiceProvider.php`
- Contains: Service registration, config publishing, command registration
- Depends on: WorkflowRegistry, configuration
- Used by: Laravel bootstrap process

**Registry Layer:**
- Purpose: Build and manage workflow instances from configuration
- Location: `src/WorkflowRegistry.php`
- Contains: Workflow instantiation, definition building, subscriber registration
- Depends on: Symfony Workflow components, event dispatcher
- Used by: Traits, application code via service container

**Workflow Layer:**
- Purpose: Execute state transitions and manage workflow state
- Location: `src/Workflow/StateWorkflow.php`, `src/Workflow/MethodMarkingStore.php`
- Contains: StateWorkflow (extends Symfony\Component\Workflow\Workflow), MethodMarkingStore (implements MarkingStoreInterface)
- Depends on: Symfony Workflow Definition, EventDispatcher
- Used by: HasWorkflowTrait, application code

**Event/Subscriber Layer:**
- Purpose: Handle workflow lifecycle events and trigger business logic
- Location: `src/Events/`, `src/Subscribers/`
- Contains: Event wrappers (GuardEvent, LeaveEvent, TransitionEvent, EnterEvent, EnteredEvent, CompletedEvent), WorkflowSubscriber, WorkflowSubscriberHandler
- Depends on: Symfony EventDispatcher, Laravel Event system
- Used by: WorkflowRegistry, custom subscriber implementations

**Model Integration Layer:**
- Purpose: Mix workflow capabilities into domain models
- Location: `src/Traits/HasWorkflowTrait.php`
- Contains: Workflow accessor methods, transition application, history tracking
- Depends on: WorkflowRegistry, StateWorkflowHistory model
- Used by: Domain entities (models)

**Persistence Layer:**
- Purpose: Record state transitions for audit trails
- Location: `src/Models/StateWorkflowHistory.php`, `database/migrations/create_state_workflow_histories_table.php`
- Contains: StateWorkflowHistory model, migration definition
- Depends on: Laravel Eloquent ORM
- Used by: HasWorkflowTrait, event subscribers

**Command/Console Layer:**
- Purpose: Provide developer tools for workflow visualization and debugging
- Location: `src/Console/Commands/StateWorkflowDumpCommand.php`
- Contains: Artisan command for dumping workflow graphs
- Depends on: Symfony GraphvizDumper, Artisan command base
- Used by: Developers via CLI

## Data Flow

**Workflow Initialization:**

1. Laravel bootstrap loads StateWorkflowServiceProvider
2. Provider merges config/workflow.php and registers WorkflowRegistry singleton
3. WorkflowRegistry instantiates Symfony DefinitionBuilder from config
4. Creates Transition objects for each configured state transition
5. Instantiates StateWorkflow wrapping Definition
6. Registers MethodMarkingStore for state persistence
7. Creates and registers EventDispatcher with WorkflowSubscriber

**Transition Execution:**

1. Domain model with HasWorkflowTrait receives `applyTransition('transition_name')` call
2. Trait's applyTransition stores context and delegates to workflow()->apply()
3. StateWorkflow validates transition via MethodMarkingStore.getMarking()
4. EventDispatcher fires 'workflow.guard' events for validation
5. EventDispatcher fires 'workflow.leave' events (model leaving current state)
6. EventDispatcher fires 'workflow.transition' events (executing transition)
7. EventDispatcher fires 'workflow.enter' events (entering new state, before persist)
8. MethodMarkingStore.setMarking updates model's state property
9. EventDispatcher fires 'workflow.entered' events (after state update)
10. WorkflowSubscriber.enteredEvent saves model and calls saveStateHistory()
11. StateWorkflowHistory record created with transition details and user_id
12. EventDispatcher fires 'workflow.completed' events (final cleanup)

**State Management:**

- Current state stored in domain model property (default: `current_state`)
- MethodMarkingStore reads/writes via Symfony PropertyAccessor
- Single-state workflows (single place at time) vs multi-state workflows
- Context data passed through applyTransition() available in events and stored in history

## Key Abstractions

**StateWorkflowInterface:**
- Purpose: Defines contract for state retrieval from workflow objects
- Examples: `src/Workflow/StateWorkflow.php`
- Pattern: Extends Symfony's Workflow interface with custom `getState()` method

**WorkflowRegistryInterface:**
- Purpose: Defines contract for workflow retrieval and subscriber registration
- Examples: `src/WorkflowRegistry.php`
- Pattern: Registry pattern for managed workflow instances

**WorkflowEventSubscriberInterface:**
- Purpose: Defines contract for custom workflow event subscribers
- Examples: `src/Subscribers/WorkflowSubscriberHandler.php`, test fixtures
- Pattern: Event subscriber with dynamic method name mapping

**BaseEvent:**
- Purpose: Wrapper around Symfony workflow events, provides common interface
- Examples: `src/Events/GuardEvent.php`, `src/Events/LeaveEvent.php`, etc.
- Pattern: Decorator pattern wrapping Symfony events with Laravel event compatibility

**HasWorkflowTrait:**
- Purpose: Mix workflow methods into domain models
- Examples: Applied to any Eloquent model needing workflow behavior
- Pattern: Trait for non-hierarchical code reuse, lazy-loads workflow instance

## Entry Points

**Service Provider Boot:**
- Location: `src/StateWorkflowServiceProvider.php` boot() method
- Triggers: Laravel application bootstrap
- Responsibilities: Publish config, publish migrations, register console commands

**Service Provider Register:**
- Location: `src/StateWorkflowServiceProvider.php` register() method
- Triggers: Laravel application bootstrap (before boot)
- Responsibilities: Merge config, instantiate and register WorkflowRegistry singleton as 'stateWorkflow'

**Model Workflow Access:**
- Location: `src/Traits/HasWorkflowTrait.php` workflow() method
- Triggers: First call to workflow-related method on model
- Responsibilities: Retrieve workflow instance from registry, cache it on model

**Transition Application:**
- Location: `src/Traits/HasWorkflowTrait.php` applyTransition() method
- Triggers: Explicit call on model instance
- Responsibilities: Store context, delegate to StateWorkflow.apply(), persist history

**Event Listener Registration:**
- Location: `src/WorkflowRegistry.php` addSubscriber() method
- Triggers: Workflow registry initialization
- Responsibilities: Validate subscriber implements interface, register with Laravel Event dispatcher

**Workflow Definition from Config:**
- Location: `src/WorkflowRegistry.php` addWorkflows() method
- Triggers: Registry initialization for each workflow in config
- Responsibilities: Build Symfony Definition from state and transition arrays, instantiate StateWorkflow

## Error Handling

**Strategy:** Exception-based with validation checks

**Patterns:**
- Subscriber class validation in WorkflowRegistry.addSubscriber() throws Exception if doesn't implement WorkflowEventSubscriberInterface
- Transition validation handled by Symfony Workflow component (throws exceptions for invalid transitions)
- PropertyAccessor errors when state property doesn't exist on model
- ReflectionException possible when determining model config name from class name
- Guard event handlers can block transitions via setBlocked(true) on original event

## Cross-Cutting Concerns

**Logging:**
- Approach: Optional via Laravel Log facade in subscriber event handlers (not enforced by core)
- Test fixtures use Log::info() to demonstrate logging pattern

**Validation:**
- Approach: Guard events provide validation point - event handlers set blocked flag to prevent transition
- Example: `onGuardActivate()` can check user.dob before allowing 'activate' transition

**Authentication:**
- Approach: Lazy-resolved via auth()->id() in HasWorkflowTrait.authenticatedUserId()
- Can be overridden at model level for custom auth mechanisms
- User ID recorded with each state transition in StateWorkflowHistory

**Database Transactions:**
- Approach: Model save() happens in WorkflowSubscriber.enteredEvent() after state property update
- History record created in same event handler
- No explicit transaction management (relies on Laravel's transaction handling if needed)

---

*Architecture analysis: 2026-03-02*
