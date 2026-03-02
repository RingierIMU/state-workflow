# Roadmap: State Workflow Modernization

## Overview

A sequential transformation pipeline that brings `ringierimu/state-workflow` from PHP 8.1/PHPUnit to PHP 8.3+/Laravel 11+/Pest 4. Each phase produces a verified artifact consumed by the next — dependency constraints land first, then Rector modernizes source against the correct installed APIs, then the test suite migrates and expands on a clean foundation, then CI and docs close out the upgrade. Phases cannot be reordered.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Dependency Update** - Update composer.json constraints and remove Symfony compatibility shim
- [ ] **Phase 2: Rector Pass** - Apply driftingly/rector-laravel to src/ only, then remove Rector from dev dependencies
- [ ] **Phase 3: Pest Migration and Test Expansion** - Migrate all PHPUnit tests to Pest 4 and add new coverage
- [ ] **Phase 4: CI and Documentation** - Rebuild GitHub Actions matrix and update README/CHANGELOG

## Phase Details

### Phase 1: Dependency Update
**Goal**: composer.json declares PHP 8.3+, Laravel 11+, Symfony 7 constraints and installs cleanly with no autoloader crashes
**Depends on**: Nothing (first phase)
**Requirements**: DEPS-01, DEPS-02, DEPS-03, DEPS-04, DEPS-05, DEPS-06, DEPS-07, DEPS-08
**Success Criteria** (what must be TRUE):
  1. `composer update --prefer-lowest --prefer-stable` exits 0 with no unresolvable constraint errors
  2. `php artisan` (or package bootstrap) does not throw a ClassNotFoundException on boot — the InstanceOfSupportStrategy shim is gone and ClassInstanceSupportStrategy is used unconditionally
  3. The existing PHPUnit test suite passes against the newly-installed vendor tree (confirms no API breakage from Symfony 7 upgrade)
  4. `composer.json` requires PHP `^8.3`, `illuminate/*` `^11.0|^12.0`, `symfony/workflow ^7.0`, and `orchestra/testbench ^9.0|^10.0`
**Plans:** 1 plan
- [ ] 01-01-PLAN.md — Update composer.json constraints, remove all backward-compatibility shims, validate clean install and test suite

### Phase 2: Rector Pass
**Goal**: Source files in src/ are modernized to PHP 8.3 idioms; Rector and its config are removed from the repository
**Depends on**: Phase 1
**Requirements**: RECT-01, RECT-02, RECT-03, RECT-04
**Success Criteria** (what must be TRUE):
  1. Rector dry-run produces a diff that touches only src/ — no test files modified
  2. No public API signatures change in HasWorkflowTrait, StateWorkflow, or WorkflowRegistry (verified by diff review before apply)
  3. The full PHPUnit test suite passes after Rector is applied — changes are safe
  4. `composer.json` dev dependencies no longer include rector/rector or driftingly/rector-laravel; rector.php is deleted
**Plans**: TBD

### Phase 3: Pest Migration and Test Expansion
**Goal**: All tests run under Pest 4; new tests cover multiple workflows, subscriber event ordering, and error paths
**Depends on**: Phase 2
**Requirements**: TEST-01, TEST-02, TEST-03, TEST-04, TEST-05, TEST-06, TEST-07
**Success Criteria** (what must be TRUE):
  1. `vendor/bin/pest` reports all migrated tests passing — zero PHPUnit-only syntax remaining
  2. `tests/Pest.php` uses `Ringierimu\StateWorkflow\Tests\TestCase::class` as the base — service provider and config injection work correctly
  3. WorkflowSubscriberTest uses Event::fake() / Event::assertDispatched() — global event() namespace trick is gone
  4. `vendor/bin/pest --order=random` passes consistently (no ordering-dependent test failures)
  5. New tests exist and pass: multiple workflows on same model, subscriber event lifecycle ordering, subscriber error handling when listener throws
**Plans**: TBD

### Phase 4: CI and Documentation
**Goal**: GitHub Actions validates the full matrix; README reflects current minimum versions
**Depends on**: Phase 3
**Requirements**: CICD-01, CICD-02, CICD-03, CICD-04, DOCS-01, DOCS-02, DOCS-03
**Success Criteria** (what must be TRUE):
  1. GitHub Actions matrix runs PHP [8.3, 8.4] x Laravel [11.*, 12.*] — PHP 8.1/8.2 and Laravel 10 rows are gone
  2. Matrix include: pins testbench ^9.0 for Laravel 11 and testbench ^10.0 for Laravel 12 — no version mismatch failures
  3. CI runs `vendor/bin/pest` (not vendor/bin/phpunit) and both prefer-lowest and prefer-stable strategies are tested
  4. README installation block shows PHP 8.3+, Laravel 11+ requirements and correct composer require syntax
  5. A CHANGELOG entry or release note exists describing what changed in this upgrade

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Dependency Update | 0/1 | Planned | - |
| 2. Rector Pass | 0/TBD | Not started | - |
| 3. Pest Migration and Test Expansion | 0/TBD | Not started | - |
| 4. CI and Documentation | 0/TBD | Not started | - |
