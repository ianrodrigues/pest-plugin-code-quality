# 09 · Documentation

Status: todo
Depends on: 08

## Goal

A developer who already uses Pest reaches a first meaningful policy result within ten minutes using only the README, and can explain a failure without reading engine docs (PRD §9 success criteria).

## Deliverables

- `README.md`: what it is in two sentences; install; the two-policy example from PRD §4; a real failure output; adopting on an existing codebase in five steps with the baseline commands; inspection; configuration reference; limitations (`not` unsupported, vendor code not analysable, Pest 5 / PHP 8.4+ only, internal-API pin and what that means for upgrades).
- `docs/metrics.md` (from task 02) finalised with worked examples for every counted construct and the deltas versus `sebastian/complexity`, PDepend and PHP_CodeSniffer, so users understand why numbers differ between tools.
- `docs/baselines.md`: format, identity rules, the scenario table, `generate` vs `tighten`, what stale means.
- `docs/json-schema.md` (from task 07).
- `CHANGELOG.md` `Unreleased` section filled with user-facing entries.
- `CONTRIBUTING.md`: running the suite, the fixture conventions, the commit convention (atomic, Conventional Commits), how to add a metric (must add a version and fixtures).

## Acceptance criteria

- Every code sample in the README is executed by a test (doc-test fixture project) so it cannot rot.
- A reviewer unfamiliar with the project follows the README on a fresh Laravel app and reaches a failing policy in under ten minutes; record the timing in the PR.
- No document references PHPMD configuration or engine internals as something the user must learn.
