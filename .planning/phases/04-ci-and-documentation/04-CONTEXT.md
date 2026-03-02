# Phase 4: CI and Documentation - Context

**Gathered:** 2026-03-02
**Status:** Ready for planning

<domain>
## Phase Boundary

Rebuild GitHub Actions CI matrix for PHP 8.3+/Laravel 11+, update README with current version requirements, and create a CHANGELOG for the modernization upgrade. This closes out the v1 milestone.

</domain>

<decisions>
## Implementation Decisions

### CHANGELOG format & depth
- Use Keep a Changelog format (keepachangelog.com) with Added/Changed/Removed sections
- This release only — no backfilling past releases
- Use `[Unreleased]` header (version number added at release time, targeting 5.0.0)
- Summary-level bullets aimed at package consumers, not contributor-level detail
- Include brief migration note: "Upgrading from 4.x: requires PHP 8.3+ and Laravel 11+"

### README update scope
- Minimal changes — update version requirements and installation section only
- Add a "Requirements" section listing PHP 8.3+ and Laravel 11+
- Add a version/compatibility matrix table (PHP/Laravel versions per package version, like Spatie packages)
- Keep `composer test` in "Run Unit Test" section as-is (it already runs Pest)
- Do not rewrite or modernize other README content

### CI modernization
- Update `actions/checkout` and `actions/cache` from v3 to v4
- Fix the broken cache step (references non-existent `steps.composer-cache.outputs.dir`)
- Use matrix `include:` to pin testbench ^9.0 for Laravel 11 and ^10.0 for Laravel 12
- No extras (no coverage upload, no static analysis) — those are v2 requirements
- Change test runner from `vendor/bin/phpunit` to `vendor/bin/pest`
- Matrix: PHP [8.3, 8.4] × Laravel [11.*, 12.*] with both prefer-lowest and prefer-stable

### Release versioning
- Major semver bump: 5.0.0 (breaking: dropped PHP 8.1/8.2 and Laravel 10)
- CHANGELOG uses [Unreleased] with 5.0.0 as the target

### Claude's Discretion
- Exact cache configuration approach (setup-php built-in vs manual composer cache step)
- CHANGELOG wording and bullet grouping
- README table formatting and layout
- CI workflow name and job naming

</decisions>

<specifics>
## Specific Ideas

- Version/compatibility table modeled after Spatie package READMEs
- Keep a Changelog format per keepachangelog.com
- Migration context in CHANGELOG for consumers upgrading from 4.x

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `composer.json` scripts already define `"test": "vendor/bin/pest"` — CI just needs to call `vendor/bin/pest`
- Existing CI workflow structure (`.github/workflows/main.yml`) can be edited in-place

### Established Patterns
- CI triggers on push/PR to master/main branches
- Matrix strategy with `fail-fast: false`
- Uses `shivammathur/setup-php@v2` for PHP setup
- Composer require with `laravel/framework:${{ matrix.laravel }}` for matrix testing

### Integration Points
- `.github/workflows/main.yml` — single CI workflow file
- `README.md` — project root
- `CHANGELOG.md` — new file at project root
- `composer.json` — already has correct constraints (source of truth for version table)

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 04-ci-and-documentation*
*Context gathered: 2026-03-02*
