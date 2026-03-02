# Phase 3: Pest Migration and Test Expansion - Context

**Gathered:** 2026-03-02
**Status:** Ready for planning

<domain>
## Phase Boundary

Migrate all PHPUnit tests to Pest 4 syntax and add new test coverage for multiple workflows, subscriber event ordering, and error paths. No new production code features — this phase is about the test suite only.

</domain>

<decisions>
## Implementation Decisions

### Pest test style
- Full closure-based Pest syntax: `test('can apply transition', function() { ... })`
- Flat `test()` calls per file, no `describe()`/`it()` nesting — suite is small (2 existing test files)
- Use Pest's `expect()` API for assertions: `expect($user->state())->toBe('activated')`
- Keep current file layout: `tests/Unit/UserUnitTest.php` and `tests/WorkflowSubscriberTest.php` stay in place

### Event mock strategy
- Replace global `event()` namespace trick with `Event::fake()` / `Event::assertDispatched()`
- Assert on Laravel event classes (GuardEvent::class, LeaveEvent::class, etc.), not string event names
- Event ordering test (TEST-05): verify key lifecycle milestones (guard before leave, leave before transition, transition before enter, enter before entered) — not brittle exact-sequence of all 24 events
- Error handling test (TEST-06): verify exceptions in subscriber handlers propagate up to the caller, transition does not silently succeed

### New test fixtures
- Second workflow reuses existing User model with a new state column (e.g., `subscription_state`)
- Add migration fixture for the new column
- Minimal second workflow: 3 states, 2 transitions — just enough to prove multi-workflow coexistence
- New subscriber fixture for the second workflow (or no subscriber, to also test that path)

### Coverage scope
- Required scenarios only: TEST-04 (multi-workflow), TEST-05 (event ordering), TEST-06 (error handling)
- Clean migration of all existing tests — no skips, no partial migration
- Configure Pest.php for random test ordering support
- `vendor/bin/pest --order=random` must pass consistently

### Claude's Discretion
- Exact Pest.php configuration details
- Whether to split large test files or keep consolidated
- Pest plugin choices (if any beyond core)
- Loading skeleton for TestCase base class adaptation
- How to handle the `ConfigTrait` in Pest context (uses() binding vs beforeEach)

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard Pest migration approaches. Key constraint: the global `event()` namespace hack must be fully removed in favor of Laravel's `Event::fake()`.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `TestCase.php`: Base test class extending Orchestra Testbench — needs Pest `uses()` binding
- `ConfigTrait`: Provides `getWorflowConfig()` — reusable in Pest via `uses(ConfigTrait::class)`
- `tests/Fixtures/Models/User.php`: Fixture model with `HasWorkflowTrait` — stays as-is
- `tests/Fixtures/Subscriber/UserEventSubscriber.php`: Event subscriber fixture — stays as-is
- Event classes in `src/Events/`: GuardEvent, LeaveEvent, TransitionEvent, EnterEvent, EnteredEvent, CompletedEvent — these are what `Event::assertDispatched()` will check

### Established Patterns
- Orchestra Testbench for Laravel integration testing with `RefreshDatabase`
- Fixture-based test data (real Eloquent models, not mocks)
- `ConfigTrait` for workflow configuration injection
- `composer.json` autoload includes `tests/Fixtures/Helpers.php`

### Integration Points
- `phpunit.xml` needs updating or replacing with Pest configuration
- `composer.json` scripts: `composer test` command needs updating to `vendor/bin/pest`
- `composer.json` dev dependencies: add `pestphp/pest` and `pestphp/pest-plugin-laravel`
- `tests/Pest.php`: new file binding TestCase as base

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-pest-migration-and-test-expansion*
*Context gathered: 2026-03-02*
