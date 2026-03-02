# State Workflow Modernization

## What This Is

A Laravel package (`ringierimu/state-workflow`) that wraps Symfony's Workflow component to provide state machine capabilities for Eloquent models — with config-driven workflow definitions, event-driven transitions, and audit trail history. This project modernizes the package to current PHP/Laravel standards.

## Core Value

Bring the package up to modern PHP 8.2+ / Laravel 11+ standards with comprehensive Pest test coverage while preserving all existing functionality.

## Requirements

### Validated

- State machine workflows on Eloquent models via `HasWorkflowTrait` — existing
- Config-driven workflow definitions (states, transitions, subscribers) — existing
- Event-driven transition lifecycle (guard, leave, transition, enter, entered, completed) — existing
- State transition audit trail via `StateWorkflowHistory` — existing
- Artisan command for workflow graph visualization — existing
- Custom event subscriber support via `WorkflowEventSubscriberInterface` — existing
- Context data passed through transitions and stored in history — existing

### Active

- [ ] Drop PHP 8.1 support, set minimum PHP 8.2
- [ ] Drop Laravel 10 support, set minimum Laravel 11
- [ ] Run driftingly/rector-laravel to align codebase with Laravel standards (apply once, then remove)
- [ ] Migrate all PHPUnit tests to Pest
- [ ] Add Pest tests for multiple workflows on same model
- [ ] Add Pest tests for subscriber event handling and error scenarios
- [ ] Update GitHub Actions CI matrix for PHP 8.2/8.3/8.4 + Laravel 11/12
- [ ] Update composer.json dependency constraints
- [ ] Update README and package documentation

### Out of Scope

- New features (async transitions, config validation, workflow versioning) — modernization only
- Symfony component version upgrades beyond what's needed for compatibility — keep current constraints working
- Performance optimizations — separate effort
- Security hardening — separate effort

## Context

- Package lives at `ringierimu/state-workflow` on GitHub
- Current CI tests PHP 8.1/8.2/8.3 against Laravel 10/11/12
- Tests use PHPUnit with Orchestra Testbench — only 6 tests, 38 assertions currently
- StyleCI handles code style (PSR-12 preset)
- Existing test fixtures: User model, UserEventSubscriber, ConfigTrait, Helpers
- The `upgrade` branch is already checked out for this work

## Constraints

- **Backwards compatibility**: Public API (trait methods, events, config format) must not change — consumers should only need to update their composer.json
- **Test framework**: Pest 3.x with Orchestra Testbench
- **Rector**: Use `driftingly/rector-laravel` for one-time modernization pass, then remove from dependencies

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Drop PHP 8.1 + Laravel 10 | PHP 8.1 EOL Dec 2024, Laravel 10 EOL Feb 2025 | — Pending |
| Rector apply-once approach | Keep dev dependencies lean, Rector is a migration tool not ongoing lint | — Pending |
| Pest over PHPUnit | Modern PHP testing standard, better DX, less boilerplate | — Pending |

---
*Last updated: 2026-03-02 after initialization*
