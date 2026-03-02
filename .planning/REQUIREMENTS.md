# Requirements: State Workflow Modernization

**Defined:** 2026-03-02
**Core Value:** Bring the package up to modern PHP 8.3+ / Laravel 11+ standards with comprehensive Pest test coverage while preserving all existing functionality

## v1 Requirements

Requirements for this modernization milestone. Each maps to roadmap phases.

### Dependencies

- [x] **DEPS-01**: Package requires PHP `^8.3` minimum
- [x] **DEPS-02**: Package requires `illuminate/*` `^11.0|^12.0` (drop Laravel 10)
- [x] **DEPS-03**: Package requires `symfony/workflow` `^7.0` (drop `^5.1|^6.0`)
- [x] **DEPS-04**: Package requires `symfony/event-dispatcher` `^7.0`
- [x] **DEPS-05**: Package requires `symfony/property-access` `^7.0`
- [x] **DEPS-06**: `InstanceOfSupportStrategy` dual-import shim removed from `WorkflowRegistry.php` — unconditionally use `InstanceOfSupportStrategy`
- [x] **DEPS-07**: `orchestra/testbench` updated to `^9.15|^10.0`
- [x] **DEPS-08**: All dependencies resolve cleanly with `composer update --prefer-lowest --prefer-stable`

### Rector

- [ ] **RECT-01**: `driftingly/rector-laravel` applied to `src/` directory only (not `tests/`)
- [ ] **RECT-02**: Rector dry-run reviewed — no public API signatures changed in `HasWorkflowTrait`, `StateWorkflow`, or `WorkflowRegistry`
- [ ] **RECT-03**: Rector and `driftingly/rector-laravel` removed from dev dependencies after applying
- [ ] **RECT-04**: All existing PHPUnit tests pass after Rector changes

### Testing

- [ ] **TEST-01**: All existing tests migrated from PHPUnit to Pest 4
- [ ] **TEST-02**: `tests/Pest.php` correctly uses custom `TestCase` class (`Ringierimu\StateWorkflow\Tests\TestCase`)
- [ ] **TEST-03**: Global `event()` mock in `WorkflowSubscriberTest` replaced with `Event::fake()` pattern
- [ ] **TEST-04**: New test: multiple workflows registered on the same model
- [ ] **TEST-05**: New test: subscriber event ordering verified across full transition lifecycle
- [ ] **TEST-06**: New test: subscriber error handling when listener throws exception
- [ ] **TEST-07**: `vendor/bin/pest` runs all tests successfully

### CI/CD

- [ ] **CICD-01**: GitHub Actions matrix updated to PHP [8.3, 8.4] x Laravel [11.*, 12.*]
- [ ] **CICD-02**: Matrix `include:` pins `testbench: ^9.0` for Laravel 11 and `testbench: ^10.0` for Laravel 12
- [ ] **CICD-03**: `prefer-lowest` and `prefer-stable` dependency strategies both tested in CI
- [ ] **CICD-04**: CI runs `vendor/bin/pest` instead of `vendor/bin/phpunit`

### Documentation

- [ ] **DOCS-01**: README updated with new minimum version requirements (PHP 8.3+, Laravel 11+)
- [ ] **DOCS-02**: README installation instructions updated for current dependency versions
- [ ] **DOCS-03**: CHANGELOG or release notes drafted for the upgrade

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Code Quality

- **QUAL-01**: Add Laravel Pint for code style enforcement (replace StyleCI)
- **QUAL-02**: Add PHPStan/Larastan for static analysis
- **QUAL-03**: Add Pest architecture tests

### Features

- **FEAT-01**: Configuration validation at boot time
- **FEAT-02**: Typed context objects for transitions
- **FEAT-03**: Async/queued transition support

## Out of Scope

| Feature | Reason |
|---------|--------|
| New workflow features | This is a modernization milestone only |
| Performance optimizations | Separate effort, not related to version upgrades |
| Security hardening | Separate effort |
| Rector as permanent dev dependency | Apply-once approach per project constraint |
| Psalm alongside PHPStan | Redundant static analysis tooling |
| Mutation testing in CI | Excessive for package of this size |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DEPS-01 | Phase 1 | Complete |
| DEPS-02 | Phase 1 | Complete |
| DEPS-03 | Phase 1 | Complete |
| DEPS-04 | Phase 1 | Complete |
| DEPS-05 | Phase 1 | Complete |
| DEPS-06 | Phase 1 | Complete |
| DEPS-07 | Phase 1 | Complete |
| DEPS-08 | Phase 1 | Complete |
| RECT-01 | Phase 2 | Pending |
| RECT-02 | Phase 2 | Pending |
| RECT-03 | Phase 2 | Pending |
| RECT-04 | Phase 2 | Pending |
| TEST-01 | Phase 3 | Pending |
| TEST-02 | Phase 3 | Pending |
| TEST-03 | Phase 3 | Pending |
| TEST-04 | Phase 3 | Pending |
| TEST-05 | Phase 3 | Pending |
| TEST-06 | Phase 3 | Pending |
| TEST-07 | Phase 3 | Pending |
| CICD-01 | Phase 4 | Pending |
| CICD-02 | Phase 4 | Pending |
| CICD-03 | Phase 4 | Pending |
| CICD-04 | Phase 4 | Pending |
| DOCS-01 | Phase 4 | Pending |
| DOCS-02 | Phase 4 | Pending |
| DOCS-03 | Phase 4 | Pending |

**Coverage:**
- v1 requirements: 26 total
- Mapped to phases: 26
- Unmapped: 0

---
*Requirements defined: 2026-03-02*
*Last updated: 2026-03-02 after roadmap creation (DOCS moved from Phase 5 to Phase 4)*
