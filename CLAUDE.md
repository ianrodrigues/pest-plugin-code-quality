# pest-plugin-quality

A Pest 5 plugin that adds method-level maintainability limits (complexity, body lines, parameters) to Pest's `arch()` chain, with baselines for gradual adoption. PHP project; the Bun guidance from the parent directory does not apply here.

## Commits

- Atomic: one logical change per commit. Split unrelated changes even when they were made together.
- Conventional Commits: `feat:`, `fix:`, `docs:`, `test:`, `chore:`, `ci:`, `refactor:`, `perf:`. Add a body when the subject cannot carry the "why". Describe the change on its own terms.

## Code style

- Class methods use `camelCase()`; standalone functions use `snake_case()`. A test over `src/` enforces this.
- Comments are rare and concise, and explain why, never what. Code must speak for itself: no prose, no narration of the next lines, no docblocks that restate a signature.

## Running things

- `composer install` (also enables the git hooks), then `composer check` runs Pint, PHPStan (level max) and Pest.
- Individual steps: `composer lint` (fix), `composer lint:check`, `composer analyse`, `composer test`, `composer test:parallel`.

## Guardrails that run without you

- Git hooks live in `.githooks/` and are enabled with `git config core.hooksPath .githooks` (`composer install` does this for you). `commit-msg` validates the message format; `pre-commit` lints staged PHP with Pint.
- `composer check` (Pint, PHPStan level max, Pest) must pass before a commit is considered done. CI runs the same command on every push.
