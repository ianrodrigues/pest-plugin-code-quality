# 03 · Measurement engine over the php-parser AST

Status: todo
Depends on: 02

## Goal

Given a php-parser AST for one file, produce measurements for every eligible method with correct symbol and source mapping, and cache them per file within a process. This is the only place that knows how metrics are computed.

## Design

- `Pest\Quality\Analysis\FileMeasurer::measure(string $path, array $stmts): FileMeasurements` where names are already resolved (the arch plugin runs `NameResolver`; the standalone path must run it too).
- `Measurement` value object: `symbol` (`Fully\Qualified\Class::method`), `path` (project-root relative), `line`, `endLine`, `metric` (`MetricId`), `value`, `eligible`.
- One `NodeVisitor` computing all three metrics in a single traversal; closures/arrow functions accumulate into the enclosing method; anonymous classes are entered for `lines` but their methods are recorded under an anonymous symbol that is never a target.
- `MeasurementCache` keyed by real path plus content hash (`xxh128` or `sha1`), in-memory for v0.1.0; interface allows a file-backed cache later.
- Errors: unparsable input raises `AnalysisError` with path and message. The engine never returns partial results for a file.

## Acceptance criteria

- The task 02 fixture suite passes un-skipped, including boundary and modern syntax fixtures.
- `sebastian/complexity` cross-check passes with the documented deltas.
- Measuring the same path twice with unchanged content parses once (asserted via a counting parser stub); changed content re-measures.
- Symbols for trait methods, enum methods, static methods, constructors, magic methods and promoted-constructor params are all correct in a dedicated test.
- Measuring the `docs/research/spike/src` tree and laravel/framework-sized input (a generated 50k-line fixture is fine) completes without errors; time is recorded in the PR description, not asserted.
- phpstan level max passes for `src/Analysis`.

## Out of scope

Anything about Pest, targets, thresholds or baselines.
