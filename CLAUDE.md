# pest-plugin-quality

A Pest 5 plugin that adds method-level maintainability limits (complexity, body lines, parameters) to Pest's `arch()` chain, with baselines for gradual adoption. PHP project; the Bun guidance from the parent directory does not apply here.

## Where things are

- `PRD.md` — product requirements and the decisions already made (§10).
- `docs/research/` — feasibility research, benchmark script, syntax fixture, and the runnable spike.
- `.tasks/` — the ordered task list for v0.1.0. Work tasks in order; update the `Status` line as you go.

## Commits

- Atomic: one logical change per commit. Split unrelated changes even when they were made together.
- Conventional Commits: `feat:`, `fix:`, `docs:`, `test:`, `chore:`, `ci:`, `refactor:`, `perf:`. Add a body when the subject cannot carry the "why".
- Never mention task numbers, task files or the `.tasks` folder in commit messages. Describe the change on its own terms.

## Running things

- Spike: `cd docs/research/spike && composer install && vendor/bin/pest` (expects 4 intentional failures, 10 passes).
- Package (once scaffolded): `composer check` runs pint, phpstan and pest.

## Guardrails that run without you

- Git hooks live in `.githooks/` and are enabled with `git config core.hooksPath .githooks` (run once per clone; `composer install` will do it once the package is scaffolded). `commit-msg` rejects non-conventional subjects and any reference to task numbers or files; `pre-commit` lints staged PHP with Pint.
- `composer check` (Pint, PHPStan level max, Pest) must pass before a commit is considered done. CI runs the same command on every push.
