# Changelog

All notable changes to `ringierimu/state-workflow` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Minimum PHP version raised to 8.3 (dropped 8.1, 8.2)
- Minimum Laravel version raised to 11.0 (dropped Laravel 10)
- Symfony dependencies updated to ^7.0 (dropped ^5.1, ^6.0)
- Orchestra Testbench updated to ^9.15|^10.0
- Test suite migrated from PHPUnit to Pest
- CI matrix updated to PHP [8.3, 8.4] x Laravel [11, 12]

### Removed
- PHP 8.1 and 8.2 support
- Laravel 10 support
- Symfony 5.x and 6.x support
- `InstanceOfSupportStrategy` dual-import shim in WorkflowRegistry (Symfony 7 uses single import)

> **Upgrading from 4.x:** Requires PHP 8.3+ and Laravel 11+. No public API changes — update your PHP and Laravel versions, then run `composer update`.
