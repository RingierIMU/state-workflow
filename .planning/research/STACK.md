# Stack Research

**Domain:** Laravel package modernization (PHP/Laravel minimum version upgrade)
**Researched:** 2026-03-02
**Confidence:** MEDIUM-HIGH (core toolchain verified via multiple sources; exact CI YAML syntax from community examples, not official spec)

---

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| PHP | `^8.2` | Minimum runtime | PHP 8.1 reached EOL December 2024. 8.2 is the Laravel 11 minimum and introduces readonly classes, DNF types, and `true`/`false`/`null` as standalone types — all useful for modern package code. Targeting 8.2 keeps the package broadly adoptable while enabling modern syntax. |
| Laravel | `^11.0\|^12.0` | Illuminate dependency constraint | Laravel 10 reached EOL February 2025. Laravel 11 restructured the framework significantly (single config file, slimmed skeleton). Laravel 12 (February 2025) is the current major. Both are actively supported. |
| Orchestra Testbench | `^9.0\|^10.0` | Laravel package testing harness | The de-facto standard for bootstrapping a complete Laravel application in package tests. v9.x targets Laravel 11, v10.x targets Laravel 12. Must be dual-constrained to match the dual-Laravel support. |
| Pest | `^3.7` | Test framework | **Not Pest 4.** Pest 4 (released early 2026) requires PHP 8.3+. Since this package targets PHP 8.2 minimum, Pest 3 is the correct choice — it supports PHP 8.2+ and PHPUnit 11. Pest 3 is actively maintained and the current stable series for PHP 8.2 environments. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `pestphp/pest-plugin-laravel` | `^3.0` | Laravel-specific Pest helpers (`actingAs()`, `get()`, etc.) | Required alongside Pest 3 for any Laravel-specific testing assertions |
| `orchestra/pest-plugin-testbench` | `^3.0` | Bridges Pest with Orchestra Testbench | Use instead of manually wiring the Testbench `TestCase` in `Pest.php`; handles the `uses()` boilerplate. v3.2.1 requires `pestphp/pest ^3.4.1` and `orchestra/testbench ^9.10\|\|^10.0`. |
| `rector/rector` | `^2.0` | Automated code modernization | Apply once during the modernization pass, then remove from `require-dev`. Current stable is 2.3.x (released 2026-02-22). |
| `driftingly/rector-laravel` | `^2.0` | Laravel-specific Rector rule sets | Provides `LaravelLevelSetList` and `LaravelSetList`; last updated 2026-02-22. Apply once alongside `rector/rector`, then remove. |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| `shivammathur/setup-php@v2` | GitHub Actions PHP provisioning | Industry standard action for PHP version matrix; supports PHP 8.2, 8.3, 8.4 with extensions |
| Pest.php | Pest suite configuration | Replaces `phpunit.xml` as the primary test configuration entry point; uses `pest()->extend(TestCase::class)->in('.')` pattern |
| `phpunit.xml` | PHPUnit/Pest XML config | Still needed for test suite definition even when using Pest; define test suites and environment variables here |

---

## Installation

```bash
# Remove old PHPUnit and add Pest 3 stack
composer remove phpunit/phpunit --dev
composer remove mockery/mockery --dev  # evaluate if still needed

# Add Pest 3 + Laravel plugin + Testbench bridge
composer require --dev \
    pestphp/pest:"^3.7" \
    pestphp/pest-plugin-laravel:"^3.0" \
    orchestra/pest-plugin-testbench:"^3.0" \
    orchestra/testbench:"^9.0|^10.0"

# Add Rector (apply-once, then remove)
composer require --dev \
    rector/rector:"^2.0" \
    driftingly/rector-laravel:"^2.0"

# Initialize Pest
./vendor/bin/pest --init
```

---

## composer.json Conventions for PHP 8.2+/Laravel 11+ Packages

Key changes from the current `composer.json`:

```json
{
    "require": {
        "php": "^8.2",
        "illuminate/events": "^11.0|^12.0",
        "illuminate/support": "^11.0|^12.0",
        "symfony/event-dispatcher": "^7.0",
        "symfony/workflow": "^7.0",
        "symfony/property-access": "^7.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0|^10.0",
        "orchestra/pest-plugin-testbench": "^3.0",
        "pestphp/pest": "^3.7",
        "pestphp/pest-plugin-laravel": "^3.0"
    },
    "minimum-stability": "stable",
    "prefer-stable": true,
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage"
    }
}
```

**Key convention changes from current package:**
- Drop `^10.0` from `illuminate/*` constraints — Laravel 10 EOL Feb 2025
- Drop `^8.1` PHP constraint — PHP 8.1 EOL Dec 2024
- Drop `^6.0` Symfony constraints — not compatible with Laravel 11+'s Symfony 7 requirement
- Remove `funkjedi/composer-include-files` — this was a workaround for loading test helpers; with Pest, helpers load via `Pest.php`
- Change `minimum-stability` from `"dev"` to `"stable"` — `"dev"` was required for older testbench pre-releases; stable is correct for production packages
- Change `"test": "phpunit"` script to `"test": "pest"`

---

## Pest 3 Setup for Laravel Packages

### File Structure

```
tests/
├── Pest.php           # Suite configuration (replaces bootstrap.php wiring)
├── TestCase.php       # Base test case extending Testbench's TestCase
├── Feature/
│   └── ...
└── Unit/
    └── ...
```

### TestCase.php Pattern

```php
<?php

namespace Ringierimu\StateWorkflow\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ringierimu\StateWorkflow\StateWorkflowServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            StateWorkflowServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Run migrations, set config, etc.
    }
}
```

### Pest.php Pattern

```php
<?php

use Ringierimu\StateWorkflow\Tests\TestCase;

pest()->extend(TestCase::class)->in('.');
```

The `orchestra/pest-plugin-testbench` package handles the `uses()` bridge automatically when you declare `pest()->extend(TestCase::class)`. No manual `uses(Orchestra\Testbench\TestCase::class)` calls needed in individual test files.

---

## Rector Configuration

### rector.php for Modernization Pass

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
    ])
    ->withSets([
        // Apply all rules up through Laravel 11 (cumulative — includes 9, 10 rules too)
        LaravelLevelSetList::UP_TO_LARAVEL_110,
        // Code quality improvements (arrow functions, named args, etc.)
        LaravelSetList::LARAVEL_CODE_QUALITY,
        // Type declaration improvements
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_82);
```

**Available `LaravelLevelSetList` constants (cumulative):**
- `UP_TO_LARAVEL_100` — rules for Laravel 10
- `UP_TO_LARAVEL_110` — rules for Laravel 10 + 11 (use this for the modernization target)
- `UP_TO_LARAVEL_120` — rules for Laravel 10 + 11 + 12 (only if comfortable applying all)

**Available `LaravelSetList` constants (additive, non-cumulative):**
- `LARAVEL_CODE_QUALITY` — general code quality improvements
- `LARAVEL_TYPE_DECLARATIONS` — add type hints and return types
- `LARAVEL_COLLECTION` — collection method modernization
- `LARAVEL_IF_HELPERS` — converts `abort()` inside conditions to `abort_if()`
- `LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL` — `Str::*` / `Arr::*` facade calls

**Recommended for this package:** Use `UP_TO_LARAVEL_110` + `LARAVEL_CODE_QUALITY` + `LARAVEL_TYPE_DECLARATIONS`. Avoid `LARAVEL_STATIC_TO_INJECTION` — it changes public API surface by pushing toward constructor injection, which is a design change, not modernization.

### Workflow (apply-once approach)

```bash
# Dry-run first — review proposed changes
./vendor/bin/rector process --dry-run

# Apply
./vendor/bin/rector process

# Remove rector from dev dependencies
composer remove rector/rector driftingly/rector-laravel --dev
rm rector.php
```

---

## GitHub Actions CI Matrix

### Standard Pattern for PHP 8.2+/Laravel 11+ Packages

```yaml
name: run-tests

on:
  push:
    branches: [master, main, upgrade]
  pull_request:
    branches: [master, main]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      fail-fast: true
      matrix:
        php: [8.2, 8.3, 8.4]
        laravel: ['11.*', '12.*']
        stability: [prefer-stable]
        include:
          - laravel: '11.*'
            testbench: '^9.0'
          - laravel: '12.*'
            testbench: '^10.0'

    name: PHP ${{ matrix.php }} - Laravel ${{ matrix.laravel }} - ${{ matrix.stability }}

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pdo, sqlite, pdo_sqlite
          coverage: none

      - name: Setup problem matchers
        run: |
          echo "::add-matcher::${{ runner.tool_cache }}/php.json"
          echo "::add-matcher::${{ runner.tool_cache }}/phpunit.json"

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" \
            "orchestra/testbench:${{ matrix.testbench }}" \
            --no-interaction --no-update
          composer update --${{ matrix.stability }} --prefer-dist --no-interaction

      - name: List installed packages
        run: composer show -D

      - name: Execute tests
        run: ./vendor/bin/pest
```

**Key design decisions:**
- PHP 8.2, 8.3, 8.4 — covers current supported PHP versions with Laravel 11+
- Laravel 11.* / 12.* — both active LTS targets; Laravel 10 dropped (EOL Feb 2025)
- `prefer-lowest` matrix variant omitted intentionally — adds matrix size with diminishing value for a package this size; can be added later if regressions appear
- `actions/checkout@v4` — current version (v3 deprecated)
- `fail-fast: true` — stop on first failure to preserve Actions minutes
- `coverage: none` in setup-php — Xdebug/PCOV adds significant overhead; enable only in a dedicated coverage job if needed

---

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| Pest 3 | Pest 4 | If you raise PHP minimum to 8.3+; Pest 4 adds browser testing (not needed for this package) |
| Pest 3 | PHPUnit 11 directly | If team is unfamiliar with Pest DSL; PHPUnit is more verbose but equally valid |
| `orchestra/pest-plugin-testbench` | Manual `uses()` in each test file | If the plugin adds unwanted magic; manual `uses()` in `Pest.php` works equally well |
| `driftingly/rector-laravel` | Manual code updates | For a 6-file package you could manually update everything; Rector ensures completeness and catches non-obvious patterns |
| `actions/checkout@v4` | `actions/checkout@v3` | v3 still works but is deprecated by GitHub |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Pest 4 | Requires PHP 8.3+; breaks PHP 8.2 support commitment | Pest 3 (`^3.7`) |
| PHPUnit `^10.0` alone | Pest 3 bundles PHPUnit 11; mixing versions causes conflicts | Let Pest manage PHPUnit as a transitive dependency |
| `funkjedi/composer-include-files` | Was needed to autoload test helpers in PHPUnit; Pest.php handles this natively | Remove entirely; load helpers via `Pest.php` or `bootstrap` |
| `mockery/mockery` | Evaluate need — if only used for PHPUnit mocking patterns, Pest's built-in `mock()` may suffice | Pest 3 `mock()` / `spy()` helpers, or keep Mockery if complex mock expectations needed |
| `LaravelLevelSetList::UP_TO_LARAVEL_120` | Includes Laravel 12-specific rules that may alter behavior beyond modernization; test surface is larger | `UP_TO_LARAVEL_110` — targets the 10→11 migration, which is the stated goal |
| `LARAVEL_STATIC_TO_INJECTION` set | Refactors static calls to injected dependencies — changes public API surface and design patterns, not just syntax | Skip this set entirely for a modernization-only pass |
| `minimum-stability: dev` | Current composer.json has this; it allows accidental dev-quality packages into the dependency graph | `minimum-stability: stable` — the correct setting for a released library |

---

## Version Compatibility Matrix

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| `pestphp/pest:^3.7` | PHP ^8.2, PHPUnit 11 | Pest 3 last release was 3.x; Pest 4 requires PHP 8.3+ |
| `orchestra/testbench:^9.0` | Laravel ^11.0, PHP ^8.2 | Use for Laravel 11 testing |
| `orchestra/testbench:^10.0` | Laravel ^12.0, PHP ^8.2 | Use for Laravel 12 testing |
| `orchestra/pest-plugin-testbench:^3.0` | `pestphp/pest:^3.4.1`, `orchestra/testbench:^9.10\|\|^10.0` | Bridges Pest 3 with Testbench |
| `pestphp/pest-plugin-laravel:^3.0` | `pestphp/pest:^3.x` | Laravel-specific test helpers for Pest 3 |
| `rector/rector:^2.0` | PHP ^7.4\|^8.0 (runs on), targets PHP 8.2 | Rector itself runs on older PHP but applies 8.2 modernization rules |
| `driftingly/rector-laravel:^2.0` | `rector/rector:^2.0` | Last updated 2026-02-22; current |
| `symfony/workflow:^7.0` | Symfony 7.x (required by Laravel 11+) | Laravel 11 requires Symfony 7; current package supports `^5.1` which must be updated |
| `symfony/event-dispatcher:^7.0` | Symfony 7.x | Same constraint update needed as above |

---

## Stack Patterns by Variant

**If you later raise PHP minimum to 8.3+:**
- Migrate from Pest 3 to Pest 4 (`pestphp/pest:^4.0`, `pestphp/pest-plugin-laravel:^4.0`)
- Pest 4 is built on PHPUnit 12; update CI matrix accordingly
- `orchestra/pest-plugin-testbench` will need a v4.x release (in development as of March 2026)

**If you need code coverage reports:**
- Add a separate CI job with `coverage: xdebug` in `setup-php`
- Run `pest --coverage --min=80` (or appropriate threshold)
- Do not add coverage to the main matrix — it doubles CI time

**If you want `prefer-lowest` stability testing:**
- Add `stability: [prefer-lowest, prefer-stable]` to the matrix
- Helps catch minimum-version incompatibilities
- Increases matrix from 6 to 12 jobs — only worth it for complex dependency packages

---

## Sources

- [pestphp/pest Packagist](https://packagist.org/packages/pestphp/pest) — version 4.4.1 latest (PHP 8.3+), v3.x for PHP 8.2; MEDIUM confidence (packagist listing)
- [orchestra/pest-plugin-testbench Packagist](https://packagist.org/packages/orchestra/pest-plugin-testbench) — v3.2.1 requires pest ^3.4.1 and testbench ^9.10||^10.0; MEDIUM confidence
- [Pest v4 Is Here announcement](https://pestphp.com/docs/pest-v4-is-here-now-with-browser-testing) — confirms PHP 8.3 minimum for Pest 4; HIGH confidence (official docs)
- [driftingly/rector-laravel GitHub](https://github.com/driftingly/rector-laravel) — LaravelLevelSetList and LaravelSetList constants verified; MEDIUM confidence (multiple search results agree)
- [rector/rector Packagist](https://packagist.org/packages/rector/rector) — version 2.3.8 released 2026-02-22; MEDIUM confidence
- [Spatie laravel-permission run-tests.yml](https://github.com/spatie/laravel-permission/blob/main/.github/workflows/run-tests.yml) — reference for matrix include/exclude pattern with testbench version mapping; MEDIUM confidence (community reference, not official spec)
- [orchestra/testbench Packagist](https://packagist.org/packages/orchestra/testbench) — v9.x for Laravel 11, v10.x for Laravel 12, both require PHP ^8.2; HIGH confidence (multiple sources agree)
- WebSearch: "Pest 4 requires PHP 8.3" confirmed by multiple independent sources (pestphp.com, benjamincrozat.com, Medium) — HIGH confidence

---

*Stack research for: ringierimu/state-workflow Laravel package modernization*
*Researched: 2026-03-02*
