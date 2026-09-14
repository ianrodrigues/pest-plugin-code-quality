# 11 · Performance verification

Status: todo
Depends on: 08, 10

## Goal

Replace the PRD's provisional targets with measured numbers on a fixed runner, and confirm nothing in tasks 04–08 regressed the cost seen in research (about 3.8 ms per file end-to-end, 0.7 ms per file engine-only).

## Work

- Generate a deterministic 50,000-line fixture project (script committed under `tests/Performance/`) with realistic namespace spread, traits, enums and closures.
- Benchmark script measuring: engine-only (task 03 on the tree), end-to-end through Pest for three policies on the same namespace, and end-to-end with a baseline configured. Report wall time and peak memory.
- Run on GitHub Actions `ubuntu-latest` (record the runner spec in the output) in a manual workflow; commit results to `docs/performance.md` with the commit SHA.
- Targets from PRD F6: ≤10 s cold, ≤2 s unchanged warm, ≤512 MB. If warm ≤2 s is not met without a persistent cache, decide and record whether to ship an in-process-only cache in v0.1.0 and move the persistent cache to a new `after v0.1.0` task.

## Acceptance criteria

- `docs/performance.md` contains engine-only and end-to-end figures with runner, PHP and plugin versions.
- Any target not met is either fixed or explicitly revised in the PRD with the measurement cited.
- A guard test asserts that measuring the fixture project re-parses no file twice within one process.
