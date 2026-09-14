# 10 · Compatibility matrix and CI

Status: todo
Depends on: 04

## Goal

Every declared support target is tested on every push, and the declared matrix matches what actually passes (PRD F6 compatibility).

## Matrix

- PHP 8.4 and 8.5 × Ubuntu and Windows × serial and `--parallel`.
- Pest `^5.0` lowest (`--prefer-lowest`) and latest.
- `pest-plugin-arch` at the pinned range only.

## Jobs

- `test`: matrix above, running `composer check`.
- `static`: phpstan level max, pint `--test`.
- `spike`: the research spike still passes (guards against upstream changes to the integration mechanism, alongside the task 04 canary).
- `docs`: README samples executed (task 09 doc-test).
- Dependabot or Renovate for Composer and Actions, weekly.

## Acceptance criteria

- All matrix cells green on `main`.
- Windows job includes the path-normalisation and symlink tests from task 06.
- `composer.json` `php` and `pestphp/pest` constraints equal the tested matrix; a test reads `composer.json` and fails if the constraints widen without a matrix change.
- Badge in README reflects the `test` workflow.
