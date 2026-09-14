# 06 · Scan completeness and selection errors

Status: todo
Depends on: 04

## Goal

A policy can never pass because nothing was analysed. The research showed Pest's reflection-based discovery silently skipping 73 of 231 files and returning zero objects for a namespace that maps to a directory holding other namespaces. Make every such case visible.

## Behaviour

- After resolving a target, count: files found under the PSR-4 mapping, objects Pest produced, objects with an AST, eligible methods measured.
- If a target yields zero eligible methods, the test errors with `EmptySelection`: the namespace, the directories searched, how many PHP files were found, and how many objects were loadable. Opt-out per policy via `->allowingEmptySelection()` (documented, explicit).
- If files were found but could not be turned into objects (autoload failure, name/namespace mismatch), the test still runs but the result carries a warning list; with `--quality-strict` (or config `strict: true`) these become errors. Default in v0.1.0: warning printed once per run in the summary.
- Namespace matching respects segment boundaries; add a regression test with sibling namespaces sharing a prefix.
- Paths in all output are project-root relative and normalised with forward slashes (Windows test in task 10).
- Vendor targets: selecting a namespace that resolves under `vendor/` errors with a message that vendor code has no AST in Pest's arch layer.

## Acceptance criteria

- Fixture project with an unloadable class (missing parent) produces the warning and lists the file.
- Fixture with a directory whose classes live in a different namespace produces `EmptySelection`.
- `->allowingEmptySelection()` turns that into a pass with a note.
- Sibling-prefix namespace test passes.
- Counts are exposed on the policy result object for task 07 to print.
