# 04 · Arch expectations inside `arch()`

Status: todo
Depends on: 03

## Goal

The three P0 expectations work as native-feeling members of Pest's architecture chain, using the mechanism proven in the research spike.

## Expectations

- `toHaveMethodComplexityAtMost(int $max)` → `ccn2`
- `toHaveMethodLinesAtMost(int $max)` → `lines`
- `toHaveMethodParametersAtMost(int $max)` → `params`

All inclusive (`AtMost(10)` passes 10). All registered from `src/Autoload.php` through `expect()->extend()` with an `ArchExpectation` return type, building on `Blueprint`, `Targets`, `SingleArchExpectation` and `LayerOptions` from `pest-plugin-arch`.

## Behaviour

- Works after `expect('Namespace')`, `expect('Exact\Class')`, `expect([...])`, and after `->classes()`, `->enums()`, `->traits()` and the other pending-arch filters.
- `->ignoring(string|array)` after the expectation is honoured, including `test()->arch()->ignore(...)` and `@pest-arch-ignore-line` on the method's first line.
- Composes with built-in arch expectations in either order (`->toBeFinal()->toHaveMethodLinesAtMost(40)` and the reverse).
- Reads the AST from the resolved `ObjectDescription` and feeds it to the engine from task 03; measurements are cached so multiple policies on the same target do not re-measure.
- `->not` is rejected with `Pest\Quality\Exceptions\UnsupportedModifier` explaining that numeric limits have no negation and suggesting a lower limit instead. Because Pest's `OppositeExpectation` cannot be intercepted before it calls the original, implement this by detecting the opposite path in the extension (inspect the calling `Expectation` state) and failing fast with the friendly error.
- Invalid limits (negative, non-int) throw `InvalidLimit` at declaration time.
- Auto-generated test names read naturally, e.g. `expect 'App\Http\Controllers' → classes → toHaveMethodComplexityAtMost 10`.

## Editor support

- `stubs/Expectation.stub.php` (or `@method` docblocks on a mixin) so IDEs and phpstan see the three methods on `Pest\Expectation`, `Pest\Arch\PendingArchExpectation` and `Pest\Arch\Contracts\ArchExpectation`. Verified by a phpstan run over a sample test file.

## Acceptance criteria

- A feature test suite modelled on `docs/research/spike/tests/Architecture/ControllersTest.php` passes: one failing policy per metric, ignoring, composition in both orders, exact class, namespace boundary (`App\Billing` does not select `App\BillingArchive`), enum and trait targets.
- Serial and `--parallel` runs produce identical outcomes.
- A canary test asserts that `Pest\Expectation::__call` still returns the extension result for an `ArchExpectation` return type; if it fails, the test message says the integration mechanism changed upstream.
- `->not` test asserts the friendly error, not `BadMethodCallException`.

## Out of scope

Message formatting beyond a placeholder (task 05), completeness checks (task 06).
