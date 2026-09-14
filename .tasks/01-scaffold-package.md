# 01 · Scaffold the package

Status: done
Depends on: —

## Goal

A publishable Composer package that Pest 5 discovers as a plugin, with the quality tooling and test harness the remaining tasks will build on. No product code yet.

## Scope

- `composer.json`: name `ianrodrigues/pest-plugin-quality` (working name; confirm ownership before task 12), type `library`, PHP `^8.4`, `pestphp/pest ^5.0` as `require-dev`, `pestphp/pest-plugin ^5.0`, `pestphp/pest-plugin-arch ~5.0.0` (tight pin, see research risk R1), `nikic/php-parser ^5.6`. Register the plugin via `extra.pest.plugins` and `autoload.files` for `src/Autoload.php`.
- PSR-4 namespace `Pest\Quality\` under `src/`, tests under `tests/` with `tests/Pest.php`, `tests/Unit`, `tests/Feature`, `tests/Fixtures`.
- Dev tooling: `laravel/pint` (PSR-12 preset), `phpstan/phpstan` at level max with `pestphp/pest-plugin-phpstan` or a plain `phpstan.neon`, `rector/rector` optional. Composer scripts: `test`, `lint`, `analyse`, `check` (all three).
- `README.md` with a one-paragraph description and "work in progress" note; `LICENSE` (MIT); `CHANGELOG.md` with an empty `Unreleased` section; `.gitignore`, `.editorconfig`, `.gitattributes` (export-ignore for tests, .tasks, hooks, tooling config).

## Acceptance criteria

- `composer install` succeeds on PHP 8.4 and 8.5.
- `vendor/bin/pest` runs a placeholder test and passes; `vendor/bin/pest --parallel` also passes.
- `composer check` passes (pint, phpstan, pest).
- `vendor/bin/pest --help` lists no new options yet, but the plugin's `Autoload.php` is loaded (verified by a test asserting a marker function exists).
- No product expectations exist yet.

## Out of scope

Any metric, expectation or CLI behaviour.
