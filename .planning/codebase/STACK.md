# Technology Stack

**Analysis Date:** 2026-03-02

## Languages

**Primary:**
- PHP 8.1+ - Main language for all source code and tests
- YAML - Configuration files (.yml, .yaml)

## Runtime

**Environment:**
- PHP 8.1, 8.2, 8.3 - Supported versions (tested in CI)

**Package Manager:**
- Composer 2.x - Dependency management
- Lockfile: Present (`composer.lock`)

## Frameworks

**Core:**
- Laravel 10.x, 11.x, 12.x - Web application framework
- Symfony Workflow Component 5.1+ - State machine and workflow engine
- Symfony Event Dispatcher 6.x, 7.x - Event handling and pub/sub
- Symfony Property Access 5.1+ - Object property reflection and manipulation

**Service Provider:**
- Illuminate Events - Laravel event dispatcher integration
- Illuminate Support - Laravel utility functions

## Key Dependencies

**Critical:**
- `symfony/workflow` ^5.1 - Provides workflow definitions, transitions, states, and state machine implementation
- `symfony/event-dispatcher` ^6.0|^7.0 - Powers event-driven architecture for workflow transitions
- `symfony/property-access` ^5.1 - Enables dynamic property access for state tracking

**Laravel Integration:**
- `illuminate/events` ^10.0|^11.0|^12.0 - Laravel event system integration
- `illuminate/support` ^10.0|^11.0|^12.0 - Laravel core utilities and facades

## Development Dependencies

**Testing:**
- `phpunit/phpunit` ^10.0|^11.0 - Unit test framework
- `orchestra/testbench` ^8.0|^9.15|^10 - Laravel testing utilities for package testing
- `mockery/mockery` ^1.3|^1.4.2 - Mocking and stubbing library

**Build & Quality:**
- `funkjedi/composer-include-files` ^1.0 - Include additional files in Composer autoload
- StyleCI (configuration: `.styleci.yml`) - Code style checking and enforcement (PSR-12 preset)

## Configuration

**Environment:**
- Laravel configuration system via `config/workflow.php`
- Environment variables for testing via `phpunit.xml`
  - `APP_ENV=testing`
  - `DB_CONNECTION=testing`

**Build:**
- `phpunit.xml` - PHPUnit configuration with test suite and database setup
- `.styleci.yml` - Code style rules (PSR-12 preset with 57 custom rules)
- `.editorconfig` - Editor configuration for code formatting consistency

**Autoloading:**
- PSR-4 autoloading for `Ringierimu\StateWorkflow\` namespace pointing to `src/`
- PSR-4 test autoloading for `Ringierimu\StateWorkflow\Tests\` namespace pointing to `tests/`
- Auto-inclusion of test fixtures via Composer extra files: `tests/Fixtures/Helpers.php`

## Platform Requirements

**Development:**
- PHP 8.1+ with Composer
- Git (for version control and CI/CD)

**Production:**
- PHP 8.1+ runtime
- Laravel 10.x, 11.x, or 12.x application
- Database with migration support (configured in Laravel)

## CI/CD Pipeline

**GitHub Actions:**
- Workflow: `.github/workflows/main.yml` - Unit Test
- Triggers: Pull requests and pushes to `master` and `main` branches
- Matrix testing:
  - PHP versions: 8.1, 8.2, 8.3
  - Laravel versions: 10.*, 11.*, 12.*
  - Dependency versions: prefer-lowest, prefer-stable
  - Exclusions: PHP 8.1 with Laravel 11.* and 12.*
- Commands:
  - Install dependencies with specific Laravel version
  - Run `vendor/bin/phpunit` for testing

## Database

**ORM:**
- Eloquent (Laravel's ORM) - Used in models
- Migration system for schema management

**Tables:**
- `state_workflow_histories` - Tracks state transitions (created via migration)

**Supported Databases:**
- Any Laravel-supported database (MySQL, PostgreSQL, SQLite, etc.)

---

*Stack analysis: 2026-03-02*
