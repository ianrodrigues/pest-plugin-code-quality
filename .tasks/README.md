# Tasks for v0.1.0

One file per task, executed in file order. A task is done when every acceptance criterion holds and the work is committed. The last task is the release.

Status is tracked in each file's `Status` line: `todo`, `in progress`, `done`.

## Working rules

- Commits are atomic and follow Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `chore:`, `ci:`, `refactor:`, `perf:`). One logical change per commit, with a body when the subject cannot carry the "why".
- Commit messages never mention task numbers, task files, or this folder. They describe the change on its own terms.
- Scope for v0.1.0 is PRD §5 P0. Anything else goes to a new task file marked `after v0.1.0`, not into an existing task.
- Product decisions already made live in `PRD.md` §10 and `docs/research/2026-09-14-feasibility.md` §5. Do not reopen them inside a task; write a new decision record if one must change.

## Order

| # | Task | Depends on |
|---|---|---|
| 01 | Scaffold the package | — |
| 02 | Pin metric definitions with a fixture suite | 01 |
| 03 | Measurement engine over the php-parser AST | 02 |
| 04 | Arch expectations inside `arch()` | 03 |
| 05 | Failure reporting | 04 |
| 06 | Scan completeness and selection errors | 04 |
| 07 | Inspection command and JSON output | 05, 06 |
| 08 | Baselines: generate, check, tighten | 07 |
| 09 | Documentation | 08 |
| 10 | Compatibility matrix and CI | 04 |
| 11 | Performance verification | 08, 10 |
| 12 | Release v0.1.0 | all |
