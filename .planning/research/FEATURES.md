# Feature Research

**Domain:** Laravel package modernization (PHP/Laravel version upgrade)
**Researched:** 2026-03-02
**Confidence:** HIGH — findings verified across official docs, Pest official site, Spatie skeleton, and multiple corroborating sources.

---

## Feature Landscape

### Table Stakes (Users Expect These)

Features users assume exist in a maintained package in 2025/2026. Missing these = package feels abandoned or unsafe to adopt.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| PHP 8.2 minimum, drop 8.1 | PHP 8.1 EOL Dec 2024; packages still targeting it signal abandonment | LOW | Constraint change in `composer.json`; Rector handles code surface |
| Laravel 11 minimum, drop 10 | Laravel 10 EOL Feb 2025; maintaining dead versions blocks modernization | LOW | Constraint change in `composer.json`; testbench version follows |
| Full return type declarations | PHP 8.0+ convention; missing types = static analysis failures, IDE darkness | MEDIUM | Rector `AddReturnTypeDeclaration` handles most; some manual fixes needed |
| Full parameter type declarations | Same as return types — typed signatures are expected in modern PHP | MEDIUM | Rector covers majority; edge cases need manual review |
| Typed class properties | Untyped properties are PHP 7 era; typed properties enable static analysis | MEDIUM | Rector `TypedPropertyRector` applies automatically |
| Pest 3.x as test framework | PHPUnit for packages feels dated; Pest is the de-facto Laravel testing standard | MEDIUM | Rewrite 6 PHPUnit tests; gains dataset, describe, architecture testing |
| Updated CI matrix (PHP 8.2/8.3/8.4, Laravel 11/12) | Current matrix includes EOL PHP 8.1 and EOL Laravel 10 | LOW | Drop excluded 8.1 rows; add 8.4; update testbench mappings |
| Up-to-date composer.json constraints | Stale ranges signal unmaintained package; users run `composer why-not` and leave | LOW | Update `require` and `require-dev` version strings |
| README reflects current versions | Docs showing outdated badges/installation = users assume package is dead | LOW | Update supported version table, badges, installation instructions |

### Differentiators (Competitive Advantage)

Features that go beyond the minimum. Not assumed, but noticed and appreciated by sophisticated Laravel package consumers.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Pest architecture tests | Enforces package structure rules automatically; catches regressions nobody would write a test for | LOW | `arch()->preset()->php()` + custom expectations; ~10 lines of test code |
| Pest type coverage check | Quantifies how fully-typed the codebase is; CI fails if type coverage drops below threshold | LOW | Built into Pest 3: `--type-coverage --min=100`; zero-config once Pest is in |
| `readonly` value objects / DTOs where appropriate | Signals modern PHP idiom; makes intent clear for immutable config or event data | MEDIUM | Review which classes are pure data carriers (e.g., event wrappers); apply selectively — not everything qualifies |
| Laravel Pint replacing StyleCI | First-party Laravel tool, runs locally and in CI without a paid service; zero config for PSR-12 + Laravel conventions | LOW | Add `laravel/pint` dev-dependency; remove `.styleci.yml`; add `composer pint` script |
| PHPStan / Larastan integration | Static analysis catches bugs before tests; level 5+ is table stakes at Spatie, Livewire, and other reference packages | MEDIUM | Add `nunomaduro/larastan` dev-dependency; `phpstan.neon`; CI step; establish baseline |
| Dependabot for composer | Automated dependency PRs keep the package from silently drifting behind | LOW | `.github/dependabot.yml` with `package-ecosystem: composer`, weekly schedule |
| Expanded test coverage for edge cases | Current suite: 6 tests / 38 assertions — clearly incomplete; multiple workflows on same model, subscriber error paths untested | MEDIUM | Identified explicitly in PROJECT.md as required additions |
| `never` return type on terminal methods | Signals intent precisely (throws, exits); PHPStan understands it | LOW | Apply to any method that only throws exceptions |
| Named arguments in internal calls | Improves readability of long constructor/method calls; PHP 8.0+ | LOW | Cosmetic; apply during Rector pass |
| PHP 8.4 CI inclusion | Forward-compatibility signal; users on bleeding-edge PHP see the package works | LOW | Add `8.4` to CI matrix; mark as optional non-failing if needed |

### Anti-Features (Commonly Requested, Often Problematic)

Features that seem like good ideas but should be explicitly avoided for this modernization milestone.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| New workflow features (async transitions, config validation, versioning) | Would make the package "better" | Out of scope for modernization; introduces risk of breaking changes and API drift | Separate milestone after modernization is stable |
| Rector kept as permanent dev dependency | Useful tool, so why remove it? | Rector is a migration tool; leaving it permanently adds a heavy dev dependency that confuses intent and may conflict with other tools | Apply once on the `upgrade` branch, commit result, remove from `require-dev` |
| Symfony component version upgrades | Newer Symfony = newer features | Brings in breaking API changes; separate risk vector from the PHP/Laravel upgrade | Pin current Symfony constraints; Symfony upgrades are their own milestone |
| Mutation testing in CI (required) | Pest 3 ships mutation testing | Mutation testing is slow (minutes to hours); requiring it in CI blocks fast feedback | Run mutation testing ad-hoc or in a nightly workflow, not in the PR matrix |
| Full monorepo / multi-package restructure | "Professional" package structure | Massive scope expansion; this repo is a single focused package — monorepo tooling adds overhead with no benefit | Keep single-package structure |
| PHPDoc blocks on every method | StyleCI rules encourage this | Modern PHP with full type signatures makes most PHPDoc redundant; `/** @param string $name */` is noise when the signature already says `string $name` | Keep PHPDoc only for `@throws`, complex `@param` descriptions, and public API documentation |
| Switching to `spatie/laravel-package-tools` | Used by many modern packages | Requires refactoring the service provider to extend a third-party base class; introduces a new runtime dependency; existing service provider is simple and works correctly | Keep existing `StateWorkflowServiceProvider`; it needs no structural change |
| Psalm alongside PHPStan | Some teams run both | Two static analyzers with different rule sets create conflicting noise; PHPStan with Larastan is sufficient for a package of this scope | Use PHPStan/Larastan only |

---

## Feature Dependencies

```
[PHP 8.2 minimum (composer.json)]
    └──enables──> [Readonly classes/properties]
    └──enables──> [Full return type declarations (Rector)]
    └──enables──> [Full parameter type declarations (Rector)]
    └──enables──> [Typed class properties (Rector)]

[Laravel 11 minimum (composer.json)]
    └──requires──> [Orchestra Testbench ^9.x in require-dev]
    └──enables──> [Updated CI matrix]

[Pest 3.x migration]
    └──requires──> [PHPUnit tests removed/converted]
    └──enables──> [Architecture tests]
    └──enables──> [Type coverage check]
    └──enables──> [Mutation testing (ad-hoc)]

[Architecture tests]
    └──requires──> [Pest 3.x migration]

[Type coverage check]
    └──requires──> [Pest 3.x migration]
    └──enhanced-by──> [Full return type declarations]
    └──enhanced-by──> [Typed class properties]

[Laravel Pint]
    └──conflicts-with──> [StyleCI (.styleci.yml)]
    (remove StyleCI when adding Pint)

[PHPStan / Larastan]
    └──enhanced-by──> [Full type declarations]
    (add after Rector pass so baseline starts clean)

[Updated CI matrix]
    └──requires──> [PHP 8.2 minimum set]
    └──requires──> [Laravel 11 minimum set]

[Dependabot]
    └──independent──> (no dependencies, add any time)

[Expanded test coverage]
    └──requires──> [Pest 3.x migration]
    (new tests written in Pest, not PHPUnit)
```

### Dependency Notes

- **Rector before PHPStan:** Run Rector first to modernize the code surface, then add PHPStan. Starting PHPStan on pre-Rector code produces a massive baseline that hides real issues.
- **Laravel Pint conflicts with StyleCI:** Both enforce code style. Keeping both creates double-formatting confusion. Remove `.styleci.yml` when Pint is added.
- **Type declarations improve type coverage score:** The Pest type coverage check rewards fully-typed code. Rector's type-adding rules directly raise the coverage metric.
- **Testbench version follows Laravel version:** Dropping Laravel 10 means dropping testbench `^8.0`; Laravel 11 requires testbench `^9.x`.

---

## MVP Definition

This is a modernization milestone, not a greenfield product. "MVP" means the minimum set of changes that makes the package genuinely modern and safe to adopt.

### Launch With (v1 — the upgrade branch)

- [ ] PHP 8.2 minimum + Laravel 11 minimum in `composer.json` — *foundational; everything else depends on this*
- [ ] Rector pass applied: typed properties, return types, parameter types, PHP 8.2 idioms — *the automated modernization payload*
- [ ] PHPUnit tests migrated to Pest 3.x — *the testing modernization payload*
- [ ] Expanded Pest tests for multiple workflows per model, subscriber error paths — *identified gap in PROJECT.md*
- [ ] Updated GitHub Actions CI matrix (PHP 8.2/8.3/8.4, Laravel 11/12) — *proves the package works on what's current*
- [ ] Updated README with correct version table, badges, installation — *trust signal for evaluators*

### Add After Core Is Stable (v1.x)

- [ ] Laravel Pint replacing StyleCI — *once tests are green, code style cleanup is low-risk*
- [ ] PHPStan/Larastan integration with baseline — *easiest to add on top of a clean Rector-processed codebase*
- [ ] Pest architecture tests — *five minutes of test code; catches structural regressions forever*
- [ ] Pest type coverage CI check — *enforces the typing discipline Rector established*
- [ ] Dependabot configuration — *pure addition, no risk, adds ongoing maintenance automation*

### Future Consideration (v2+)

- [ ] PHP 8.3 typed constants (`const string VERSION = '...'`) — *nice-to-have; no urgent user demand*
- [ ] PHP 8.4 property hooks on the `HasWorkflowTrait` getters — *interesting but changes public API surface; post-stability*
- [ ] Mutation testing nightly workflow — *high value but slow; invest after test suite is comprehensive*

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| PHP 8.2 + Laravel 11 minimum | HIGH | LOW | P1 |
| Rector modernization pass | HIGH | LOW | P1 |
| Pest 3.x migration | HIGH | MEDIUM | P1 |
| Expanded test coverage | HIGH | MEDIUM | P1 |
| Updated CI matrix | HIGH | LOW | P1 |
| Updated README | HIGH | LOW | P1 |
| Laravel Pint | MEDIUM | LOW | P2 |
| PHPStan / Larastan | MEDIUM | MEDIUM | P2 |
| Pest architecture tests | MEDIUM | LOW | P2 |
| Pest type coverage CI | MEDIUM | LOW | P2 |
| Dependabot | LOW | LOW | P2 |
| `readonly` classes/DTOs | LOW | MEDIUM | P3 |
| PHP 8.4 property hooks | LOW | HIGH | P3 |
| Mutation testing in CI | LOW | HIGH | P3 |

**Priority key:**
- P1: Required for the upgrade branch to be mergeable
- P2: High value, low risk — add in same milestone after P1 lands
- P3: Nice to have, defer to a follow-on milestone

---

## Ecosystem Context: What Reference Packages Do

The Spatie `package-skeleton-laravel` is the de-facto reference for modern Laravel open-source packages. Its current (2024/2025) pattern:

- **CI matrix:** PHP 8.3 + 8.4, Laravel 11.* + 12.*, `prefer-lowest` + `prefer-stable`, Ubuntu + Windows
- **Testing:** Pest 3 with architecture tests in `tests/ArchTest.php`
- **Code style:** Laravel Pint (no StyleCI)
- **Static analysis:** PHPStan via Larastan
- **Automation:** Dependabot for composer + GitHub Actions updates
- **PHP minimum:** 8.2 (moving toward 8.3 for new packages)

This package is not starting from scratch — it's a modernization. The gap between current state (PHP 8.1 minimum, PHPUnit, StyleCI, 6 tests) and Spatie skeleton baseline (PHP 8.2+, Pest 3, Pint, PHPStan, architecture tests) defines the full scope of work.

---

## Sources

- Pest 3 official announcement: [pestphp.com/docs/pest3-now-available](https://pestphp.com/docs/pest3-now-available)
- Pest architecture testing docs: [pestphp.com/docs/arch-testing](https://pestphp.com/docs/arch-testing)
- Pest type coverage docs: [pestphp.com/docs/type-coverage](https://pestphp.com/docs/type-coverage)
- Pest 3 release coverage: [laravel-news.com/pest-3](https://laravel-news.com/pest-3)
- Pest 3 arch presets guide: [benjamincrozat.com/pest-3-architecture-testing-presets](https://benjamincrozat.com/pest-3-architecture-testing-presets)
- Spatie package skeleton: [github.com/spatie/package-skeleton-laravel](https://github.com/spatie/package-skeleton-laravel)
- Spatie package tools: [github.com/spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools)
- Laravel 11 package development: [laravel.com/docs/11.x/packages](https://laravel.com/docs/11.x/packages)
- PHP 8.2 readonly classes: [php.watch/versions/8.2/readonly-classes](https://php.watch/versions/8.2/readonly-classes)
- PHP 8.4 features: [laravel-news.com/php-8-4-0](https://laravel-news.com/php-8-4-0)
- Laravel Pint: [laravel.com/docs/12.x/pint](https://laravel.com/docs/12.x/pint) — [github.com/laravel/pint](https://github.com/laravel/pint)
- Rector for PHP 8.2 upgrade: [tech.osteel.me/posts/upgrade-your-project-to-the-latest-php-version-with-rector](https://tech.osteel.me/posts/upgrade-your-project-to-the-latest-php-version-with-rector)
- driftingly/rector-laravel: [github.com/driftingly/rector-laravel](https://github.com/driftingly/rector-laravel)
- PHPStan 2.1 with PHP 8.4 property hooks: [phpstan.org/blog/phpstan-2-1-support-for-php-8-4-property-hooks-more](https://phpstan.org/blog/phpstan-2-1-support-for-php-8-4-property-hooks-more)
- Dependabot configuration docs: [docs.github.com](https://docs.github.com/en/code-security/dependabot/dependabot-version-updates/)
- Running PHPStan at max with Laravel: [laravel-news.com/running-phpstan-on-max-with-laravel](https://laravel-news.com/running-phpstan-on-max-with-laravel)

---

*Feature research for: ringierimu/state-workflow — Laravel package modernization*
*Researched: 2026-03-02*
