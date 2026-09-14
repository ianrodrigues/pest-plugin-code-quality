# 02 · Pin metric definitions with a fixture suite

Status: todo
Depends on: 01

## Goal

The three P0 metrics have a written, versioned definition and an executable fixture suite that encodes it, before any engine code exists. This is the contract the engine must satisfy.

## Definitions to publish (README section "Metric definitions")

- **`ccn2` v1** (method cyclomatic complexity): starts at 1; +1 for each `if`, `elseif`, `for`, `foreach`, `while`, `do`, `case` (excluding `default`), `catch`, ternary `?:`, short ternary `?:`, `&&`, `||`, `and`, `or`, `xor`, `??`, `??=`, `?->`, and each `match` arm (excluding `default`). Closures and arrow functions inside a method contribute to the enclosing method. `else`, `finally`, `try`, `break`, `continue`, `return`, `throw`, `switch` itself and `match` itself do not count.
- **`lines` v1** (method body lines): physical lines between the method's opening and closing brace that contain at least one PHP token other than whitespace or comments. Lines holding only the outer braces do not count. Several statements on one line count once. Multiline strings and heredocs count each occupied line. Nested closures and anonymous classes count in full.
- **`params` v1** (declared parameters): every declared parameter counted once, whether required, optional, promoted, by-reference or variadic. Interfaces and abstract methods are eligible.
- **Eligibility**: `ccn2` and `lines` apply to methods with a body (class, trait, enum, anonymous-class methods are measured but anonymous classes are not independent targets). Abstract and interface methods are ineligible for body metrics, not zero. Trait methods belong to the trait, never to the using class. Inherited methods are never counted on the child.
- **Metric identity**: every measurement carries `{metric, version}`; changing the definition bumps the version.

## Fixture suite (`tests/Fixtures/Metrics/`)

- One fixture file per construct with the expected value in a sidecar table (`expected.php` returning `[symbol => [ccn2, lines, params]]`).
- Boundary fixtures for each metric at n−1, n, n+1 around 10, 40 and 4 (the README example limits).
- Modern syntax fixture (one file exercising all of the following): enum methods, readonly class, property hooks, asymmetric visibility, `new` in initializer, `match`, nullsafe, first-class callable, pipe operator.
- Cross-check fixture: the same file measured by `sebastian/complexity`, with the documented deltas (`??`, `?->`) asserted explicitly.
- A fixture the engine must refuse: a file with a syntax error, expected to raise an analysis error, never a silent skip.

## Acceptance criteria

- The README "Metric definitions" section exists with the definitions above and one worked example per counted construct.
- The fixture suite loads and is asserted by a test that currently fails only because no engine exists yet (skip-marked until task 03, then un-skipped).
- Each metric has a `Pest\Quality\Metrics\MetricId` (name + version) value object with tests.

## Out of scope

Cognitive complexity, NPath, class-level metrics.
