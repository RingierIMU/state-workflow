# Phase 2: Rector Pass - Context

**Gathered:** 2026-03-02
**Status:** Ready for planning

<domain>
## Phase Boundary

Modernize all PHP source files in src/ to PHP 8.3 idioms using Rector, then remove Rector and its configuration from the repository. Tests must pass after application. No new features, no test changes — source modernization only.

</domain>

<decisions>
## Implementation Decisions

### Rector Rule Sets
- Claude selects the optimal rule sets based on codebase needs (likely PHP 8.0–8.3 upgrade sets + driftingly/rector-laravel rules)
- Dead code removal rules included (unused imports, unreachable code, unused private methods)
- Add missing return type declarations where inferrable
- Add missing parameter type declarations where inferrable
- Apply only to src/ directory — tests/ excluded per RECT-01

### Public API Protection
- Type additions (return types, param types) are allowed on all classes — additive changes are not considered breaking
- No behavioral signature changes (renaming, removing, reordering, changing semantics) on protected classes:
  - `HasWorkflowTrait` (extended by consumer models)
  - `StateWorkflow` (core workflow class)
  - `WorkflowRegistry` (workflow retrieval)
  - `WorkflowSubscriberHandler` (extended by consumer subscriber implementations)
- Interfaces: Claude's discretion — likely protect since they're implemented externally
- Other concrete classes (Events, Models, ServiceProvider, Commands): more aggressive modernization allowed (final, readonly, promoted constructor properties)

### Dry-Run Workflow
- Auto-apply approach: run Rector dry-run, review diff for API signature violations, apply, run full test suite
- If tests pass, commit — no human pause needed
- Failure handling: Claude's discretion per failure (some are test issues, some are Rector bugs — fix or exclude accordingly)
- Dry-run diff is ephemeral — not saved as an artifact

### Cleanup
- Remove rector/rector and driftingly/rector-laravel from composer.json require-dev
- Delete rector.php config file
- Run composer update to clean lock file
- No need to hunt for additional Rector artifacts
- Style conformance after Rector: Claude's discretion (check if output is clean, apply style fix if needed)

### Commit Strategy
- Separate commits: first commit applies Rector modernizations, second commit removes Rector from dependencies
- Easier to bisect if issues arise later

### Claude's Discretion
- Exact Rector rule set composition
- Whether to protect interfaces (likely yes for externally-implemented ones)
- Failure resolution strategy per test failure
- Whether to run a style fixer after Rector transforms
- Exact PHP 8.3 idiom choices where multiple valid options exist

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches. The requirements document (RECT-01 through RECT-04) is clear and prescriptive.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- No rector.php exists yet — needs to be created from scratch
- No Rector packages in composer.json — needs fresh install

### Established Patterns
- StyleCI with PSR-12 preset enforces code style — Rector output should conform
- PSR-4 autoloading for `Ringierimu\StateWorkflow\` namespace
- PHPDoc conventions: all classes have docblocks, methods have @param/@return/@throws

### Integration Points
- 19 PHP files in src/ — small, bounded surface area
- Visible modernization targets: `isset()` ternaries → null coalescing (`??`), missing return/param type declarations, no `readonly` usage, no `match` expressions
- Public API surface: HasWorkflowTrait (9 public methods), WorkflowRegistry (5 public methods), StateWorkflow (1 public method + inherited), WorkflowSubscriberHandler (2 public methods)
- Existing PHPUnit test suite must remain green throughout

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-rector-pass*
*Context gathered: 2026-03-02*
