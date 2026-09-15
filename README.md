# Code Quality for Pest

[![Tests](https://github.com/ianrodrigues/pest-plugin-code-quality/actions/workflows/tests.yml/badge.svg)](https://github.com/ianrodrigues/pest-plugin-code-quality/actions/workflows/tests.yml)

A third-party [Pest](https://pestphp.com) plugin, that adds maintainability limits — per method, cyclomatic complexity, body line count and parameter count; per class, declared methods, declared properties, inheritance depth and body line count — to Pest's `arch()` chain. It is not part of Pest itself, and it ships baselines so a limit can be adopted on a codebase that does not meet it yet.

## Requirements

- PHP ^8.4
- Pest ^5.0

## Installation

```sh
composer require --dev ianrodrigues/pest-plugin-code-quality
```

Nothing else to wire up: the plugin registers its `arch()` expectations and its CLI options as soon as `vendor/bin/pest` boots.

On a Laravel application that still lists `phpunit/phpunit` in `require-dev` (the default skeleton pins PHPUnit 12), remove that line first so Pest 5 can bring PHPUnit 13: `composer remove --dev phpunit/phpunit`.

## Quick start

Seven expectations join Pest's `arch()` chain, each taking an inclusive limit — `AtMost(10)` passes a symbol that measures exactly 10:

| Expectation | Metric | Measured per |
|---|---|---|
| `toHaveMethodComplexityAtMost(int $max)` | `ccn2` | method |
| `toHaveMethodLinesAtMost(int $max)` | `lines` | method |
| `toHaveMethodParametersAtMost(int $max)` | `params` | method |
| `toHaveMethodsAtMost(int $max)` | `methods` | class |
| `toHavePropertiesAtMost(int $max)` | `properties` | class |
| `toHaveInheritanceDepthAtMost(int $max)` | `inheritance` | class |
| `toHaveClassLinesAtMost(int $max)` | `classLines` | class |

A policy for controllers, and a separate complexity budget for a parsing layer:

<!-- readme-test: quick-start-policies -->
```php
arch('controllers remain easy to change')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveMethodComplexityAtMost(10)
    ->toHaveMethodLinesAtMost(40)
    ->toHaveMethodParametersAtMost(4);

arch('parsers stay within their complexity budget')
    ->expect('App\Parsing')
    ->classes()
    ->toHaveMethodComplexityAtMost(20);
```

Run the suite. A method over budget fails with the offending symbol, its location, and how far past the limit it is — this is the real output of the policy above against a `CheckoutController::store` that has grown too many branches:

<!-- readme-test: quick-start-failure -->
```
App\Http\Controllers\CheckoutController::store
app/Http/Controllers/CheckoutController.php:9

Method complexity (ccn2 v1): 11
Allowed: at most 10
Exceeded by: 1

1 method exceeds the limit
```

The expectations target whatever the rest of the chain targets: a namespace, an exact class, an array of either, combined with `classes()`, `enums()`, `traits()` and the other built-in filters.

## Expectations reference

Every expectation takes:

- `int $max` — inclusive; a symbol measuring exactly `$max` passes.
- `bool $allowEmpty = false` — see [Selection and completeness](#selection-and-completeness).

`toHaveMethodsAtMost()` takes one more, between the two:

- `bool $ignoringAccessors = false` — drop the accessors from the count; see [`methods` v1](#methods-v1--declared-methods).

A class-scoped expectation names the class in its failure, and points at the class declaration:

<!-- readme-test: class-failure -->
```
App\Http\Controllers\CheckoutController
app/Http/Controllers/CheckoutController.php:7

Class lines (classLines v1): 21
Allowed: at most 20
Exceeded by: 1

1 class exceeds the limit
```

They compose with each other and with built-in arch expectations, in either order, on the same chain:

<!-- readme-test: composition-example -->
```php
arch('controllers behave, and stay small')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toBeFinal()
    ->toHaveMethodComplexityAtMost(10)
    ->toHaveMethodLinesAtMost(40);
```

Excluding known offenders keeps a policy honest about what it does not yet cover, instead of quietly loosening the limit:

```php
arch('controllers remain easy to change')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveMethodComplexityAtMost(10)
    ->ignoring('App\Http\Controllers\Generated');
```

`ignoring()` takes a class, a namespace, or an array of either. `test()->arch()->ignore([...])` does the same for every expectation in the test. A single method can opt out at its declaration, the same way it would for a built-in arch expectation:

```php
/** @pest-arch-ignore-line */
public function legacyEntryPoint(array $payload): array
{
    // ...
}
```

`@pest-arch-ignore-next-line` on the line before the declaration works the same way, for a docblock that already carries something else.

### `not` is unsupported

`->not->toHaveMethodComplexityAtMost(10)` throws `IanRodrigues\CodeQuality\Exceptions\UnsupportedModifier` rather than accepting a nonsensical query: a numeric limit has no negation. Lower the limit instead. A negative limit throws `IanRodrigues\CodeQuality\Exceptions\InvalidLimit` where the expectation is declared, before anything runs.

## Adopting on an existing codebase

A limit nobody meets yet is a limit nobody adds. A baseline records what every method already measures, so a policy can go in today, pass on the code as it stands, and fail only when something gets worse.

1. **Add the policy** at the limit you actually want.
2. **Run the suite** to see what stands between the codebase and that limit. With no baseline configured yet, every offending method is listed as an ordinary failure, with how far past the limit it goes.
3. **Point the plugin at a baseline file** — the one thing configured outside the `arch()` chain, since `pest()` returns a final object with no room for the plugin's own accessor — and generate it:

   <!-- readme-test: adopt-config -->
   ```php
   // tests/Pest.php
   \IanRodrigues\CodeQuality\Config::baseline(__DIR__.'/quality-baseline.json');
   ```

   <!-- readme-test: adopt-generate -->
   ```sh
   vendor/bin/pest --quality-baseline-generate
   ```

   Generating tolerates a baseline file that does not exist yet — that run is what creates it. It writes one entry per method above the limit and prints the diff — `added / removed / increased / decreased`. The file is the list of everything being accepted for now; review it like any other change. It needs a complete, error-free run: if any policy errored (an unreadable file, an empty selection, a skipped file under `Config::strict()`), nothing is written, because the missing policy's methods would silently be accepted.
4. **Commit the policy and the baseline together.** Apart, neither means anything: the baseline is the evidence for what the policy is allowed to ignore.
5. **From then on, normal runs fail on regressions only.** A method already in the baseline may stay as bad as it was; one growing worse fails, and so does a new method over the limit that the baseline never saw.

A failure against a raised ceiling spells out what was accepted and how much was added on top — here, `Invoice::total` was baselined at 14 and has grown one branch further:

<!-- readme-test: adopt-regression-failure -->
```
App\Billing\Invoice::total
app/Billing/Invoice.php:9

Method complexity (ccn2 v1): 15
Allowed: at most 10
Accepted: 14
Increase: 1
Exceeded by: 5
```

`--quality-baseline=path/to/baseline.json` configures the file for one run, overriding `Config::baseline()`. A baseline that is configured but missing or malformed is an error, not an empty baseline: silently accepting nothing would turn a typo into a green suite.

### Tightening instead of accepting forever

<!-- readme-test: adopt-tighten -->
```sh
vendor/bin/pest --quality-baseline-tighten
```

Lowers accepted values to what the run actually measured, and drops entries that are back within the limit. It never raises a value and never adds one, so it can only give allowance back — it has the same completeness requirement as `--quality-baseline-generate`. Once `Invoice::total` above is brought back down to 12, still over the limit but under what was accepted, tightening prints:

```
added 0, removed 0, increased 0, decreased 1
  ~ App\Billing\Invoice::total (ccn2 14 -> 12)
```

Both commands work under `--parallel`: every worker hands its measurements back to the process that owns the output, which writes the file once.

### What stale means

An entry stores the metric version and the limit it was measured under. If either changes, the entry no longer describes anything true, so it is marked **stale**: ignored when checking, and listed in the run summary and in `--quality-inspect`.

```
Quality baseline: 1 entry no longer matches its policy and was ignored (run with --quality-baseline-generate to refresh)
  App\Http\Controllers\CheckoutController::store (controllers stay easy to change :: ccn2, ccn2 v1, stored for limit 12)
```

Regenerating clears stale entries. Tightening leaves them alone — there is nothing to lower a value to when the value was measured against a different rule.

### How an entry finds its policy

An entry is filed under a policy's identity, which is:

- the test's description plus the metric name, when the test has one: `arch('controllers stay easy to change')->…->toHaveMethodComplexityAtMost(10)` files entries under `controllers stay easy to change :: ccn2`;
- otherwise the sorted target list plus the expectation name: `[App\Http\Controllers, App\Models] :: toHaveMethodComplexityAtMost`.

Neither the declaring file nor the line takes part, so moving or reordering a test file keeps every entry valid. The limit takes no part either, so changing it marks entries stale rather than orphaning them. Two policies that resolve to the same identity are a configuration error naming both declaration sites — give the tests different descriptions.

Entries are filed per symbol, so a renamed method is a new method: it has no entry and no allowance. A removed method leaves an entry behind that never fails anything and that `--quality-baseline-tighten` prunes.

The file shape is versioned at `schema/quality-baseline.v1.json`: `schemaVersion`, `generatedAt`, `pluginVersion`, and `entries[]`, each with `policy`, `symbol`, `metric` (`name`, `version`), `limit`, `accepted` and a diagnostic `path`. Only values above the limit are stored, sorted by policy then symbol, so two runs over the same code write the same bytes.

## Inspection and JSON report

Two read-only CLI options show what a policy actually selected and measured, even when everything passes — they never change a test's outcome or the run's exit code, and both work under `--parallel`.

<!-- readme-test: inspect-command -->
```sh
vendor/bin/pest --quality-inspect
```

Prints, for every recorded policy, its test name and file:line, targets, directories searched, exclusions applied, completeness counts, skipped files with reason, and a table of every measured method sorted by path then symbol — this is the real output for the two policies above:

<!-- readme-test: inspect-output -->
```
Quality inspection

controllers remain easy to change
tests/ArchTest.php:8

Metric: ccn2 v1, limit 10
Targets: App\Http\Controllers
Directories searched: app/Http/Controllers
Exclusions: none
Files found: 1  Objects: 1  With AST: 1  Methods measured: 1
Measurements:
  App\Http\Controllers\CheckoutController::store (app/Http/Controllers/CheckoutController.php:9) ccn2=11 lines=20 params=3

parsers stay within their complexity budget
tests/ArchTest.php:15

Metric: ccn2 v1, limit 20
Targets: App\Parsing
Directories searched: app/Parsing
Exclusions: none
Files found: 1  Objects: 1  With AST: 1  Methods measured: 1
Measurements:
  App\Parsing\Parser::parse (app/Parsing/Parser.php:9) ccn2=5 lines=8 params=1
```

`--quality-inspect=path/to/report.json` writes the same data as JSON to that path instead of printing it, against the schema at `schema/quality-report.v1.json` (JSON Schema, draft 2020-12).

<!-- readme-test: json-command -->
```sh
vendor/bin/pest --quality-json=path/to/report.json
```

Writes only findings — violations, errors, and completeness counts — without the per-method measurements, for a smaller report. Same schema, `measurements` omitted.

Both JSON documents carry `schemaVersion`, `generatedAt`, `versions` (`php`, `pest`, `plugin`), a `policies[]` list each with `id`, `location`, `targets`, `metric`, `limit`, `coverage`, `violations[]`, `errors[]`, and a top-level `truncated: false`. A policy run with a baseline configured also carries a `baseline` block: its `path`, how many entries `applied`, and the `stale[]` ones.

## Selection and completeness

Pest's architecture layer resolves a namespace to PSR-4 directories, then reflects each file it finds; a file that fails to autoload, or whose class lives in a different namespace than its directory implies, is silently dropped by Pest. A policy can then "pass" having measured nothing. This package tracks that instead of trusting it — exposed on the arch expectation's result as `IanRodrigues\CodeQuality\Selection\Coverage`: PHP files found, objects produced, objects with an AST, and eligible symbols measured.

### Empty selections error by default

If a target selects no classes that can be measured, the test errors with `IanRodrigues\CodeQuality\Selection\EmptySelection`, naming the target, the directories searched, and how many files and objects were found. This is deliberate: an empty selection almost always means a namespace typo or a directory nobody wired up. A selection that holds only classes without method bodies (an abstract base, an interface) passes with nothing measured, which `--quality-inspect` shows as `Methods measured: 0`.

When it is genuinely expected — a namespace still being scaffolded, say — opt out per expectation:

<!-- readme-test: allow-empty -->
```php
arch('a namespace that is allowed to be empty for now')
    ->expect('App\Experimental')
    ->classes()
    ->toHaveMethodComplexityAtMost(10, allowEmpty: true);
```

### Skipped files warn, unless strict

A file found under a target's directories that never became a measurable object is skipped, not silently dropped: the policy still runs, and the result carries the file with a reason (`not loadable`, `namespace mismatch`, `vendor`, or `no ast`). By default these are warnings, printed once per process after the run:

```
Quality: 3 files were found but not analysed (run with --quality-inspect for details)
  app/Billing/Old.php (not loadable)
  app/Billing/Refund.php (namespace mismatch)
```

`IanRodrigues\CodeQuality\Config::strict(true)` turns skipped files into an error (`IanRodrigues\CodeQuality\Selection\SkippedFilesFound`) instead of a warning. It is a static, resettable switch — call `Config::reset()` to return to warning. There is no CLI flag for it yet.

A file that declares no class, interface, trait or enum — a functions file, a config file returning an array — is not a skip either: there is nothing to measure, so it counts in `filesFound` only, shown by `--quality-inspect` as `Files without classes: N`.

### Vendor code cannot be analysed

A target that resolves entirely under `vendor/` errors with `IanRodrigues\CodeQuality\Selection\VendorTarget`: Pest's architecture layer never produces an AST for vendor code, so there is nothing to measure there — `allowEmpty` does not apply, because the problem is not an empty result, it is an impossible one.

## Metric definitions

Every measurement is tagged with a `{name, version}` identity, for example `ccn2@1` — `IanRodrigues\CodeQuality\Metrics\Metric`. Changing a definition below bumps the version rather than silently reinterpreting existing baselines. `ccn2`, `lines` and `params` are measured per method; `methods`, `properties`, `inheritance` and `classLines` are measured per class, interface, trait and enum.

### `ccn2` v1 — method cyclomatic complexity

Starts at 1. The following each add 1, wherever they occur inside the method's body — including inside closures and arrow functions declared within it, since those contribute to the enclosing method rather than being measured on their own:

| Construct | Example | Effect |
|---|---|---|
| `if` | `if ($x) { ... }` | +1 |
| `elseif` | `if ($x) { ... } elseif ($y) { ... }` | +1 for the `elseif` |
| `for` | `for ($i = 0; $i < 10; $i++) { ... }` | +1 |
| `foreach` | `foreach ($items as $item) { ... }` | +1 |
| `while` | `while ($x) { ... }` | +1 |
| `do` | `do { ... } while ($x);` | +1 |
| `case` (not `default`) | `switch ($x) { case 1: ...; }` | +1 per non-`default` case |
| `catch` | `try { ... } catch (Throwable $e) { ... }` | +1 per `catch` block |
| ternary `? :` | `$x ? 'a' : 'b'` | +1 |
| short ternary `?:` | `$x ?: 'default'` | +1 |
| `&&` | `$a && $b` | +1 |
| `\|\|` | `$a \|\| $b` | +1 |
| `and` | `$a and $b` | +1 |
| `or` | `$a or $b` | +1 |
| `xor` | `$a xor $b` | +1 |
| `??` | `$a ?? $b` | +1 |
| `??=` | `$a ??= $b` | +1 |
| `?->` | `$a?->b` or `$a?->b()` | +1 |
| `match` arm (not `default`) | `match ($x) { 1 => 'a', default => 'b' }` | +1 for the `1 =>` arm only |

`else`, `finally`, `try`, `break`, `continue`, `return`, `throw`, the `switch` statement itself, and the `match` expression itself do not add to `ccn2`. A `match` arm with several comma-separated conditions (`1, 2 => 'a'`) still counts once, as one arm.

A worked example, exercising several constructs at once:

<!-- readme-test: ccn2-worked-example -->
```php
public function classify(?int $code, bool $strict): string
{
    if ($code === null) {              // if: +1
        return 'unknown';
    }

    $label = match (true) {
        $code < 0 => 'negative',       // match arm: +1
        $code === 0 => 'zero',         // match arm: +1
        default => 'positive',
    };

    return $strict && $label !== ''    // &&: +1
        ? strtoupper($label)           // ternary: +1
        : $label;
}
// ccn2: 1 + 1 + 1 + 1 + 1 + 1 = 6
```

### `lines` v1 — method body lines

The count of physical lines strictly between the method's opening and closing brace that contain at least one PHP token other than whitespace or a comment. A physical line whose only non-whitespace, non-comment content is one or more `{`/`}` characters does not count — this includes any standalone brace line closing a nested block. Several statements on one physical line count once. Every physical line occupied by a multiline string or heredoc counts, including its opening and closing lines.

<!-- readme-test: lines-worked-example -->
```php
public function example(bool $flag): int
{
    if ($flag) {           // counts
        return 1;           // counts
    }                        // brace-only: does not count

    return 0;                // counts
}
// lines: 3
```

### `params` v1 — declared parameters

Every declared parameter is counted once, regardless of whether it is required, optional (has a default), promoted (constructor property promotion), passed by reference, or variadic.

<!-- readme-test: params-worked-example -->
```php
public function __construct(
    private readonly string $name, // promoted: +1
    int $count = 0,                // optional: +1
    &$byRef = null,                // by-reference: +1
    ...$rest                       // variadic: +1
) {}
// params: 4
```

### `methods` v1 — declared methods

Every method the declaration itself holds, counted once. A constructor counts as one method whatever it promotes. A method reached through `extends` belongs to the class that declares it, and a method reached through `use` belongs to the trait that declares it; neither is counted here.

With `ignoringAccessors: true`, a method is dropped from the count when its body is exactly one `return $this->property;`, or exactly one `$this->property = $value;` followed by nothing or by `return $this;`. The default counts them, so the noise is opt-out and visible in the policy.

<!-- readme-test: methods-worked-example -->
```php
final class MethodsExample
{
    public function __construct(private int $size) {}   // +1

    public function size(): int                         // +1, an accessor
    {
        return $this->size;
    }

    public function grow(int $by): self                 // +1, an accessor
    {
        $this->size = $this->size + $by;

        return $this;
    }

    public function describe(): string                  // +1
    {
        return 'size '.$this->size;
    }
}
// methods: 4, and 2 with ignoringAccessors: true
```

### `properties` v1 — declared properties

Every property the declaration itself holds. One declaration listing several names counts once per name, and a promoted constructor parameter counts as the property it promotes. A constant is not a property, an enum case is not a property, and a property reached through `extends` or `use` belongs to the declaration it comes from.

<!-- readme-test: properties-worked-example -->
```php
final class PropertiesExample
{
    public const string KIND = 'example';       // a constant: +0

    public string $name = '';                   // +1

    public ?int $first = null, $second = null;  // +2

    public function __construct(private readonly int $size) {}  // promoted: +1
}
// properties: 4
```

### `inheritance` v1 — parents up to the root

The number of classes between the class and the root of its hierarchy. A class that extends nothing measures 0, a class that extends one class measures 1, and so on. An implemented interface is not a parent, and neither is a used trait. A parent this package never analyses — one in `vendor/`, or one PHP itself ships — still counts, and so does its own chain, read by reflection.

<!-- readme-test: inheritance-worked-example -->
```php
final class InheritanceExample extends \RuntimeException
{
}
// inheritance: RuntimeException + Exception = 2
```

### `classLines` v1 — class body lines

The count of physical lines strictly between the declaration's opening and closing brace that contain at least one PHP token other than whitespace or a comment, counted exactly as [`lines` v1](#lines-v1--method-body-lines) counts a method body. A nested declaration's lines count too, since they sit inside those braces.

<!-- readme-test: class-lines-worked-example -->
```php
final class ClassLinesExample
{
    public int $size = 0;          // counts

    public function grow(): void   // counts
    {
        $this->size++;             // counts
    }                              // brace-only: does not count
}
// classLines: 3
```

### Eligibility

- `ccn2` and `lines` apply only to methods that have a body: methods declared on a class, trait, enum, or anonymous class. They are `null` (ineligible), never `0`, for abstract methods and interface methods.
- `params` applies to every declared method, including abstract and interface methods.
- A trait method belongs to the trait, never to any class that uses it. An anonymous class's own methods are measured (their control flow never contributes to the enclosing method's `ccn2`) but are never an assertable target; the physical lines of an anonymous class declaration do count towards the `lines` of the method that declares it, since they sit inside that method's braces.
- Inherited methods are never counted on the child class — only where they are declared.
- `methods`, `properties` and `classLines` apply to every named class, interface, trait and enum. An interface and an enum declare no property, so both measure `0` there.
- `inheritance` applies to a class only. It is `null` (ineligible), never `0`, for an interface, a trait and an enum.
- An anonymous class is measured but is never an assertable target, exactly as its methods are not.

### Why the same method reports a different number elsewhere

`ccn2` counts more constructs than most complexity tools, which is why the same method can measure differently here than it does under another tool:

| Tool | What it measures | Constructs it omits that `ccn2` v1 counts |
|---|---|---|
| [`sebastian/complexity`](https://github.com/sebastianbergmann/complexity) (used by PHPUnit's risky-test detection) | Cyclomatic complexity per method/function | `??`, `??=`, `?->`. For a method whose only counted constructs are those three operators, `sebastian_ccn2 = ccn2 - occurrences_of(??, ??=, ?->)`. |
| [PDepend](https://pdepend.org)'s `ccn2` (extended complexity) | Cyclomatic complexity per method/function | `match` arms, `??`, `??=`, `?->` — its grammar predates these PHP 8 constructs. |
| PHP_CodeSniffer's `Generic.Metrics.CyclomaticComplexity` sniff | Cyclomatic complexity per function | Ternary `? :`, short ternary `?:`, `match`, `??`, `??=`, `?->` — the sniff only walks a fixed set of branching keywords and boolean-operator tokens. |

None of this makes one number more "correct" than another; it means a limit tuned against one tool's output is not the same limit under this plugin. Set limits from what `ccn2` reports, not from a number carried over from a different tool.

## Performance

Measured on a GitHub-hosted `ubuntu-latest` runner (AMD EPYC 9V74, PHP 8.5.10) against a generated project of 350 files and 51,140 lines, three policies on one namespace, median of three fresh processes, commit `c4c8468`.

| Scenario | Wall time | Peak memory |
| --- | --- | --- |
| Measurement only, one process | 0.65 s | 64 MB |
| Full Pest run, no baseline | 1.31 s | 185 MB |
| Full Pest run, baseline configured | 1.30 s | 181 MB |
| Full Pest run, `--parallel` | 1.55 s | 180 MB |

There is no persistent cache yet, so a second run costs the same as the first. Reproduce locally with `composer perf`, or on GitHub through the manual `Performance` workflow, which prints the same table in the job summary.

## Limitations

- **`not` is rejected.** A numeric limit has no negation; see [`not` is unsupported](#not-is-unsupported).
- **Vendor code cannot be analysed.** Pest's architecture layer never produces an AST for anything under `vendor/`; see [Vendor code cannot be analysed](#vendor-code-cannot-be-analysed).
- **Pest 5 and PHP 8.4+ only.** Older Pest majors do not expose the architecture internals this plugin builds on.
- **The Pest architecture plugin version is pinned.** `composer.json` requires `pestphp/pest-plugin-arch: ~5.0.0` rather than `^5.0`, because this package reaches into that plugin's internals (`SingleArchExpectation`, `LayerOptions`) to add its own expectations to the chain — those are not part of Pest's public API and can change between minor releases without notice. Every upgrade of that pin is validated by this package's own test suite before it is widened; a Pest arch update that only this plugin's tests catch is a signal the pin needs to move, not a bug to route around.

## Versioning

This package follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). Independently of the package version, each metric definition (`ccn2`, `lines`, `params`, `methods`, `properties`, `inheritance`, `classLines`) carries its own version, stamped on every measurement and stored in every baseline entry — see [Metric definitions](#metric-definitions). Widening or narrowing what a metric counts is a minor release of the package, paired with a `CHANGELOG.md` entry naming the metric and its new version, so a baseline generated before the change is recognisable as stale rather than silently reread under a new meaning. Changes to the support matrix — the PHP and Pest versions in [Requirements](#requirements), and the PHP/OS matrix CI runs against — are documented per release in `CHANGELOG.md` as well.

## Development

```sh
composer install
composer check   # Pint (check), PHPStan (level max), Rector (dry run), Pest
```

Individual steps: `composer lint` (fix formatting), `composer lint:check` (verify only), `composer analyse` (PHPStan), `composer rector` (apply Rector) and `composer rector:check` (dry run), `composer test` (Pest), `composer test:parallel` (Pest, parallel). See `CONTRIBUTING.md` for the fixture and commit conventions, and how to add a metric or a CLI option.

`composer install` also points git at `.githooks/`: `pre-commit` lints staged PHP, `commit-msg` validates the commit message format. CI runs `composer lint:check`, `composer analyse`, `composer rector:check`, `composer test` and `composer test:parallel` on the PHP/OS matrix in `.github/workflows/tests.yml`.

PHPStan (level max) sees all three expectations through `extension.neon`, picked up automatically by [phpstan/extension-installer](https://github.com/phpstan/extension-installer) or added to `includes` by hand. Editors that do not run PHPStan can be pointed at `stubs/expectations.stub.php`, which declares the same methods as `@method` annotations for autocompletion.

## License

MIT. See `LICENSE`.
