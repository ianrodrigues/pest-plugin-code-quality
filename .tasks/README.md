# Tasks for v0.1.0

One file per task, executed in file order. A task is done when every acceptance criterion holds and the work is committed. The last task is the release.

Status is tracked in each file's `Status` line: `todo`, `in progress`, `done`.

## Working rules

- Commits are atomic and follow Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `chore:`, `ci:`, `refactor:`, `perf:`). One logical change per commit, with a body when the subject cannot carry the "why".
- Commit messages never mention task numbers, task files, or this folder. They describe the change on its own terms.
- Scope for v0.1.0: three method-level expectations (complexity, body lines, parameter count); namespace and exact-class selection with exclusions; shared measurements across policies; deterministic source-linked failures and JSON output; explicit baseline generation, checking and tightening; inspectable scope and metrics; documentation, editor support and a tested compatibility matrix. Anything else (Git comparison, class coupling, presets, cognitive or NPath complexity, unused code, automatic fixes, dashboards, aggregate grades, PHPUnit-only integration) goes to a new task file marked `after v0.1.0`, not into an existing task.
- Decisions already made: Pest-centred experience; framework-independent package; method-level assertions with explicit inclusive limits; no aggregate grade; no PHPMD configuration in the normal workflow; baselines in v0.1.0; Git comparison later; measurements come from the php-parser AST that Pest's arch plugin already holds on each resolved object, and expectations join the chain through `expect()->extend()` with an `ArchExpectation` return type. Do not reopen them inside a task; write a new decision record if one must change.

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
- User-facing documentation lives in `README.md` only. There is no `docs/` folder; design notes go in task files or code comments, never in shipped docs.
