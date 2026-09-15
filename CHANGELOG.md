# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Nothing yet.

## [0.2.0] - 2026-09-15

### Added

- `toHaveMethodsAtMost()`, `toHavePropertiesAtMost()`, `toHaveInheritanceDepthAtMost()` and `toHaveClassLinesAtMost()`, measuring `methods` v1, `properties` v1, `inheritance` v1 and `classLines` v1 on every class, interface, trait and enum a target selects. `toHaveMethodsAtMost()` takes `ignoringAccessors: true` to drop plain getters and setters from the count.
- Class symbols (`App\Foo`) in failure messages, `--quality-inspect`, the JSON report and the baseline, alongside the method symbols (`App\Foo::bar`) already carried. `schema/quality-report.v1.json` gains a class measurement row and a `classesMeasured` coverage count, both optional, and neither schema changes version.
- `toHaveClassNamesAtMost()`, `toHaveMethodNamesAtMost()` and `toHaveVariableNamesAtMost()`, measuring `className` v1, `methodName` v1 and `variableName` v1. `methodName` exempts magic methods (`__construct`, `__toString`, …); `variableName` is the longest declared variable identifier in a method — a parameter, a promoted property, a local assignment, a `foreach` key or value, a `catch` variable, or a closure or arrow function's own parameters and `use` variables — and a `variableName` failure names that identifier. `schema/quality-report.v1.json` gains `methodName` and `variableName` on a method measurement row and `className` on a class one, all three optional.
### Fixed

- A file that declares no class, interface, trait or enum (a functions file, a config file returning an array) is no longer reported under "files were found but not analysed"; it counts in `filesFound` and in the new `withoutClasses` coverage count instead.
- `--quality-baseline-generate` and `--quality-baseline-tighten` now record every limit chained on one `arch()` call, not only the first one that fails: the offending expectation no longer throws while a baseline is being written, so the rest of the chain still runs. A generating or tightening run over failing code still exits non-zero; the run right after it is the one that passes.

## [0.1.0] - 2026-09-14

### Added

- `toHaveMethodComplexityAtMost()`, `toHaveMethodLinesAtMost()` and `toHaveMethodParametersAtMost()`, joining Pest's `arch()` chain with an inclusive limit and an `allowEmpty` opt-out, measuring `ccn2` v1, `lines` v1 and `params` v1 respectively.
- Composition with built-in arch expectations and with the other method limits, in either order on the same chain; `ignoring()`, `test()->arch()->ignore([...])`, `@pest-arch-ignore-line` and `@pest-arch-ignore-next-line` are all honoured.
- Baselines for gradual adoption: `Config::baseline()`, `--quality-baseline=path`, `--quality-baseline-generate`, `--quality-baseline-tighten`, stale-entry detection when a metric version or limit changes, and identity rules that keep entries valid across file moves and limit changes.
- `--quality-inspect[=path]` and `--quality-json=path`, printing or writing a schema-versioned report (`schema/quality-report.v1.json`) of every recorded policy's targets, coverage and measurements, without affecting the run's outcome or exit code.
- Selection completeness tracking (`IanRodrigues\CodeQuality\Selection\Coverage`): every policy reports PHP files found, objects produced, objects with an AST and methods measured, so a policy can no longer "pass" having silently measured nothing.
- `EmptySelection` error (opt out per expectation with `allowEmpty: true`) when a target resolves to zero eligible methods; `SkippedFilesFound`/warning output for files that were found but never became measurable objects, toggled by `Config::strict()`; `VendorTarget` error for a target that resolves entirely under `vendor/`.
- `UnsupportedModifier` when `not` is used against any of the three expectations, and `InvalidLimit` for a negative limit, both raised where the expectation is declared.
- PHPStan (level max) support via `extension.neon`, and an editor stub at `stubs/expectations.stub.php` for tooling that does not run PHPStan.

### Known limitations

- `not` cannot be used with any of the three expectations; a numeric limit has no negation, so lower the limit instead.
- Code under `vendor/` cannot be analysed: Pest's architecture layer never produces an AST for it.
- `Config::strict()` has no CLI flag yet.
- Requires PHP ^8.4 and Pest ^5.0; the Pest architecture plugin is pinned to `~5.0.0` rather than `^5.0`, since this package reaches into that plugin's internals to add its own expectations to the chain.

[Unreleased]: https://github.com/ianrodrigues/pest-plugin-code-quality/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/ianrodrigues/pest-plugin-code-quality/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/ianrodrigues/pest-plugin-code-quality/releases/tag/v0.1.0
