# Phase 4: CI and Documentation - Research

**Researched:** 2026-03-02
**Domain:** GitHub Actions CI, README documentation, CHANGELOG
**Confidence:** HIGH

## Summary

Phase 4 modernizes the CI matrix, updates README documentation, and creates a CHANGELOG for the v5.0.0 release. The existing `.github/workflows/main.yml` has a well-structured foundation but targets stale PHP/Laravel versions, uses outdated action versions (v3), has a broken cache step, and runs `vendor/bin/phpunit` instead of `vendor/bin/pest`. The README needs minimal version requirement updates and a compatibility matrix. A new CHANGELOG.md follows Keep a Changelog format.

This is a low-risk phase — all changes are configuration/documentation, not application code. The codebase is already fully working on PHP 8.3+/Laravel 11+/Pest from Phases 1-3.

**Primary recommendation:** Two parallel plans — one for CI workflow modernization, one for documentation (README + CHANGELOG). No dependencies between them.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- CHANGELOG: Keep a Changelog format (keepachangelog.com) with Added/Changed/Removed sections
- CHANGELOG: This release only — no backfilling past releases
- CHANGELOG: Use `[Unreleased]` header (version number added at release time, targeting 5.0.0)
- CHANGELOG: Summary-level bullets aimed at package consumers, not contributor-level detail
- CHANGELOG: Include brief migration note: "Upgrading from 4.x: requires PHP 8.3+ and Laravel 11+"
- README: Minimal changes — update version requirements and installation section only
- README: Add a "Requirements" section listing PHP 8.3+ and Laravel 11+
- README: Add a version/compatibility matrix table (PHP/Laravel versions per package version, like Spatie packages)
- README: Keep `composer test` in "Run Unit Test" section as-is (it already runs Pest)
- README: Do not rewrite or modernize other README content
- CI: Update `actions/checkout` and `actions/cache` from v3 to v4
- CI: Fix the broken cache step (references non-existent `steps.composer-cache.outputs.dir`)
- CI: Use matrix `include:` to pin testbench ^9.0 for Laravel 11 and ^10.0 for Laravel 12
- CI: No extras (no coverage upload, no static analysis) — those are v2 requirements
- CI: Change test runner from `vendor/bin/phpunit` to `vendor/bin/pest`
- CI: Matrix: PHP [8.3, 8.4] x Laravel [11.*, 12.*] with both prefer-lowest and prefer-stable
- Release: Major semver bump: 5.0.0 (breaking: dropped PHP 8.1/8.2 and Laravel 10)

### Claude's Discretion
- Exact cache configuration approach (setup-php built-in vs manual composer cache step)
- CHANGELOG wording and bullet grouping
- README table formatting and layout
- CI workflow name and job naming

### Deferred Ideas (OUT OF SCOPE)
- None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| CICD-01 | GitHub Actions matrix updated to PHP [8.3, 8.4] x Laravel [11.*, 12.*] | Current workflow has PHP [8.1, 8.2, 8.3] x Laravel [10.*, 11.*, 12.*] with excludes — replace with clean 2x2 matrix |
| CICD-02 | Matrix `include:` pins testbench ^9.0 for Laravel 11 and ^10.0 for Laravel 12 | Use matrix include strategy to add testbench version variable per Laravel version |
| CICD-03 | prefer-lowest and prefer-stable dependency strategies both tested in CI | Already present in current workflow as `dependency-version` matrix dimension — retain |
| CICD-04 | CI runs vendor/bin/pest instead of vendor/bin/phpunit | Current workflow runs `vendor/bin/phpunit` — change to `vendor/bin/pest` |
| DOCS-01 | README updated with new minimum version requirements | Add Requirements section with PHP 8.3+ and Laravel 11+ |
| DOCS-02 | README installation instructions updated for current dependency versions | Update installation section, add compatibility matrix table |
| DOCS-03 | CHANGELOG or release notes drafted for the upgrade | Create CHANGELOG.md with Keep a Changelog format |
</phase_requirements>

## Standard Stack

### Core
| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| GitHub Actions | v2 workflow syntax | CI/CD pipeline | Already in use, standard for GitHub-hosted packages |
| actions/checkout | v4 | Repository checkout | Current stable, up from v3 |
| actions/cache | v4 | Dependency caching | Current stable, up from v3 |
| shivammathur/setup-php | v2 | PHP version management | De facto standard for PHP CI, already in use |

### Supporting
| Tool | Purpose | When to Use |
|------|---------|-------------|
| Keep a Changelog | CHANGELOG format | Standard format for open source packages |

## Architecture Patterns

### CI Matrix Strategy
The current workflow uses `exclude:` to remove invalid PHP/Laravel combinations. With the simplified matrix (only PHP 8.3/8.4, only Laravel 11/12), no excludes are needed — all combinations are valid.

Use `include:` to add the `testbench` variable per Laravel version:
```yaml
matrix:
  php: [8.3, 8.4]
  laravel: ['11.*', '12.*']
  dependency-version: [prefer-lowest, prefer-stable]
  include:
    - laravel: '11.*'
      testbench: '^9.0'
    - laravel: '12.*'
      testbench: '^10.0'
```

The `include:` entries add the `testbench` key to all matrix combinations matching the `laravel` value. This means each of the 8 combinations (2 PHP x 2 Laravel x 2 dependency) will have the correct testbench version.

### Cache Fix
The current workflow references `${{ steps.composer-cache.outputs.dir }}` but there is no step with `id: composer-cache` that outputs the cache directory. Two approaches:

**Recommended (simpler):** Use `shivammathur/setup-php` built-in caching by removing the manual cache step entirely. The setup-php action can handle composer caching natively. However, the current approach has a manual cache step.

**Alternative (fix in place):** Add a step to get composer cache dir:
```yaml
- name: Get Composer cache directory
  id: composer-cache
  run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: ${{ steps.composer-cache.outputs.dir }}
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.json') }}
    restore-keys: ${{ runner.os }}-composer-
```

Going with the "fix in place" approach since it's explicit and maintains the existing pattern.

### Testbench Pinning in Composer Require
The Install step must also require the correct testbench version:
```yaml
- name: Install Composer dependencies
  run: |
    composer require "laravel/framework:${{ matrix.laravel }}" "orchestra/testbench:${{ matrix.testbench }}" --no-interaction --no-update
    composer update --${{ matrix.dependency-version }} --prefer-dist --no-interaction
```

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Composer cache | Custom cache logic | actions/cache@v4 with composer-cache step | Handles cache invalidation, restore keys |
| PHP matrix | Manual version detection | shivammathur/setup-php@v2 | Handles extensions, ini settings, tool installs |

## Common Pitfalls

### Pitfall 1: Matrix Include Merging
**What goes wrong:** `include:` entries with only some keys create NEW matrix combinations instead of augmenting existing ones.
**Why it happens:** GitHub Actions `include` adds new entries unless ALL existing matrix keys match.
**How to avoid:** Include at least one key from the existing matrix dimensions (e.g., `laravel`) so it merges rather than appends.

### Pitfall 2: Testbench Version Mismatch
**What goes wrong:** Tests fail with class-not-found errors because testbench version doesn't match Laravel version.
**Why it happens:** testbench ^9.0 is for Laravel 11, ^10.0 is for Laravel 12. Using wrong combination = fatal.
**How to avoid:** Matrix `include:` pins testbench per Laravel version, and the install step uses the matrix variable.

### Pitfall 3: prefer-lowest with Broad Constraints
**What goes wrong:** `prefer-lowest` resolves to incompatible old versions when constraints are too broad.
**Why it happens:** `^11.0|^12.0` with prefer-lowest picks 11.0.0 which may not have features used.
**How to avoid:** The matrix already separates Laravel versions, so composer require pins the specific major version.

## Code Examples

### Complete CI Workflow Structure
```yaml
name: Tests

on:
  push:
    branches: [master, main]
  pull_request:
    branches: [master, main]

jobs:
  tests:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: [8.3, 8.4]
        laravel: ['11.*', '12.*']
        dependency-version: [prefer-lowest, prefer-stable]
        include:
          - laravel: '11.*'
            testbench: '^9.0'
          - laravel: '12.*'
            testbench: '^10.0'

    name: PHP ${{ matrix.php }} - L${{ matrix.laravel }} - ${{ matrix.dependency-version }}

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.json') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" "orchestra/testbench:${{ matrix.testbench }}" --no-interaction --no-update
          composer update --${{ matrix.dependency-version }} --prefer-dist --no-interaction

      - name: Run tests
        run: vendor/bin/pest
```

### CHANGELOG Structure
```markdown
# Changelog

All notable changes to `ringierimu/state-workflow` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Minimum PHP version raised to 8.3 (dropped 8.1, 8.2)
- Minimum Laravel version raised to 11.0 (dropped Laravel 10)
- Symfony dependencies updated to ^7.0 (dropped ^5.1, ^6.0)
- Test suite migrated from PHPUnit to Pest
- CI matrix updated to PHP [8.3, 8.4] x Laravel [11, 12]

### Removed
- PHP 8.1 and 8.2 support
- Laravel 10 support
- Symfony 5.x and 6.x support
- `InstanceOfSupportStrategy` dual-import shim (Symfony 7 only)

### Migration
> **Upgrading from 4.x:** Requires PHP 8.3+ and Laravel 11+. No API changes — update your PHP and Laravel versions, then run `composer update`.
```

## Sources

### Primary (HIGH confidence)
- `.github/workflows/main.yml` — existing CI workflow (direct file read)
- `composer.json` — current dependency constraints (direct file read)
- `README.md` — current documentation state (direct file read)
- `.planning/phases/04-ci-and-documentation/04-CONTEXT.md` — user decisions

### Secondary (MEDIUM confidence)
- GitHub Actions documentation patterns (from training data, verified against existing workflow)
- Keep a Changelog format (keepachangelog.com standard)

## Metadata

**Confidence breakdown:**
- CI modernization: HIGH - Direct file reads of existing workflow, clear transformation path
- Documentation: HIGH - Clear requirements, standard formats, minimal ambiguity
- Pitfalls: HIGH - Well-known CI matrix patterns, verified against existing setup

**Research date:** 2026-03-02
**Valid until:** 2026-04-02 (stable domain, 30-day validity)
