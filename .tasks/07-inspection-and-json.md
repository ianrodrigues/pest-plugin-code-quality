# 07 · Inspection command and JSON output

Status: todo
Depends on: 05, 06

## Goal

A read-only way to see what a policy selected and measured, even when everything passes, plus a versioned machine-readable report (PRD F1 `--quality-inspect`, F4 JSON).

## Behaviour

- `vendor/bin/pest --quality-inspect`: runs the suite as normal and, after the results, prints for each quality policy: test name and file:line, targets, directories searched, files found / objects / methods measured, exclusions applied, skipped files with reason, and a table of every measured method with all values (sorted by path, symbol). Passing policies are included.
- `--quality-inspect=path/to/report.json` writes the same data as JSON instead of printing it; `--quality-json=path` writes only findings.
- JSON schema v1 (`docs/json-schema.md` plus `schema/quality-report.v1.json`): `schemaVersion`, `generatedAt`, `pest`/`plugin`/`php` versions, `policies[]` with identity, targets, completeness counts, `measurements[]`, `violations[]`, `errors[]`, `truncated: false` always for JSON.
- Implemented as a Pest plugin class implementing `HandlesArguments` and `AddsOutput` (or the Pest 5 equivalents), registered in `extra.pest.plugins`.
- Works under `--parallel`: each worker writes partial data to a temp file, the main process merges and prints once.

## Acceptance criteria

- Running the fixture suite with `--quality-inspect` prints every policy including passing ones, with counts matching task 06's numbers.
- JSON validates against the shipped schema in a test.
- Parallel and serial JSON are equal after sorting.
- The options do not change test outcomes.
