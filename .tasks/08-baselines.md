# 08 · Baselines: generate, check, tighten

Status: todo
Depends on: 07

## Goal

Adopt a policy on an existing codebase without a cleanup: accept current excess per method, fail on any increase, and lock in improvements only when explicitly tightened (every row of the scenario table below).

## Scenarios (configured maximum 10, accepted value 16)

| Scenario | Expected result |
|---|---|
| Existing method remains 16 | Pass; accepted excess is visible in inspection. |
| Existing method becomes 17 | Fail: increase of 1. |
| Existing method becomes 14 | Pass; flag the entry as eligible for tightening. |
| After explicit tightening to 14, method becomes 15 | Fail. |
| New method measures 11 | Fail. |
| Existing method becomes 9 | Pass; its entry can be removed. |
| Method is renamed without explicit migration | Treat as new; do not transfer the allowance. |

## Format (`tests/quality-baseline.json` by default)

- `schemaVersion: 1`, `generatedAt`, `pluginVersion`.
- `entries[]`: `policy` (stable identifier, see below), `symbol`, `metric` `{name, version}`, `accepted` (int), `path` (diagnostic only).
- Only values above the configured limit are stored.

## Policy identity

- Derived from the test's description when present (`arch('controllers remain easy to change')`) plus the metric; when no description exists, from the target list plus the expectation chain. File paths and line numbers are never part of the identity, so moving a test file keeps entries valid.
- Two policies resolving to the same identity in one run is a configuration error naming both locations.

## Commands and behaviour

- Configure via `pest()->quality()->baseline('tests/quality-baseline.json')` in `tests/Pest.php` or `--quality-baseline=path` on the CLI.
- Check (default when a baseline is configured): effective limit per symbol is `max(limit, accepted)`. Missing or malformed file is an error. Entries whose policy, metric version or limit changed are flagged `stale` and ignored until regenerated; stale entries are listed in the run summary and in inspection.
- `--quality-baseline-generate`: requires a complete, error-free scan of every policy that uses the baseline; writes atomically (temp file + rename); prints added / removed / increased / decreased allowances; never runs as part of a normal test run.
- `--quality-baseline-tighten`: lowers accepted values to current measurements and removes entries that now meet the limit; never raises a value; prints the diff.
- Renamed symbols are new symbols (no transfer). Removed symbols leave entries that `tighten` prunes; they never fail unrelated tests.
- Entries outside the selected policies (for example when running with `--filter`) are preserved on generate and tighten.

## Acceptance criteria

- One fixture per row of the scenario table above, each asserted end-to-end through Pest.
- Corrupt file, interrupted write (simulated), duplicate identity, moved test file, changed limit, changed metric version and `--filter` preservation each have a test.
- Generate and tighten refuse to run when any policy errored.
- Inspection (task 07) shows accepted values next to measured ones.
- README section drafted here is finalised in task 09.
