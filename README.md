# pest-plugin-quality

[![Tests](https://github.com/ianrodrigues/pest-plugin-quality/actions/workflows/tests.yml/badge.svg)](https://github.com/ianrodrigues/pest-plugin-quality/actions/workflows/tests.yml)

A [Pest](https://pestphp.com) plugin that adds method-level maintainability limits — cyclomatic complexity, body line count, and parameter count — to Pest's `arch()` chain, with baselines for gradual adoption on existing codebases.

> **Work in progress.** Baselines are not implemented yet; nothing here should be depended on until v0.1.0 ships.

## Requirements

- PHP ^8.4
- Pest ^5.0

## Development

```sh
composer install
composer check   # Pint (check), PHPStan (level max), Pest
```

Individual steps: `composer lint` (fix formatting), `composer lint:check` (verify only), `composer analyse` (PHPStan), `composer test` (Pest), `composer test:parallel` (Pest, parallel).

## Usage

Three expectations join Pest's `arch()` chain. Each takes an inclusive limit, so `AtMost(10)` passes a method that measures exactly 10.

| Expectation | Metric |
|---|---|
| `toHaveMethodComplexityAtMost(int $max)` | `ccn2` |
| `toHaveMethodLinesAtMost(int $max)` | `lines` |
| `toHaveMethodParametersAtMost(int $max)` | `params` |

```php
arch('controllers stay easy to change')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveMethodComplexityAtMost(10)
    ->toHaveMethodLinesAtMost(40)
    ->toHaveMethodParametersAtMost(4)
    ->ignoring('App\Http\Controllers\Generated');
```

They target whatever the rest of the chain targets: a namespace, an exact class, an array of either, and the `classes()`, `enums()`, `traits()` and other filters. `ignoring()`, `test()->arch()->ignore(...)` and `@pest-arch-ignore-line` on a method's declaration are honoured, and they compose with the built-in expectations in either order.

A failure lists every offending method at once, not just the first:

```
App\Http\Controllers\CheckoutController::store
app/Http/Controllers/CheckoutController.php:42

Method complexity (ccn2 v1): 17
Allowed: at most 10
Exceeded by: 7
```

### Limitations

- `not` is rejected: a numeric limit has no negation, so `->not->toHaveMethodComplexityAtMost(10)` throws `Rdgs\PestCodeQuality\Exceptions\UnsupportedModifier`. Lower the limit instead.
- A negative limit throws `Rdgs\PestCodeQuality\Exceptions\InvalidLimit` where the expectation is declared.
- Targets are resolved by Pest's architecture plugin, which loads each class through reflection. A class that cannot be autoloaded is skipped rather than measured.

### Editor support

PHPStan picks the three expectations up from `extension.neon`, automatically with [phpstan/extension-installer](https://github.com/phpstan/extension-installer), or by adding it to `includes` by hand. Editors can be pointed at `stubs/expectations.stub.php`, which declares the same methods as `@method` annotations.

## Metric definitions

Every measurement is tagged with a `{metric, version}` identity (for example `ccn2@1`, exposed as `Rdgs\PestCodeQuality\Metrics\MetricId`). Changing a definition below bumps the version rather than silently reinterpreting existing baselines.

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
| ternary `?:` | `$x ? 'a' : 'b'` | +1 |
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

### `lines` v1 — method body lines

The count of physical lines strictly between the method's opening and closing brace that contain at least one PHP token other than whitespace or a comment. A physical line whose only non-whitespace, non-comment content is one or more `{`/`}` characters does not count — this includes the method's own brace lines and any standalone brace line closing a nested block. Several statements on one physical line count once. Every physical line occupied by a multiline string or heredoc counts, including its opening and closing lines.

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

```php
public function __construct(
    private readonly string $name, // promoted: +1
    int $count = 0,                // optional: +1
    &$byRef = null,                // by-reference: +1
    ...$rest                       // variadic: +1
) {}
// params: 4
```

### Eligibility

- `ccn2` and `lines` apply only to methods that have a body: methods declared on a class, trait, enum, or anonymous class. They are `null` (ineligible), never `0`, for abstract methods and interface methods.
- `params` applies to every declared method, including abstract and interface methods.
- A trait method belongs to the trait, never to any class that uses it. An anonymous class's own methods are measured (their control flow never contributes to the enclosing method's `ccn2`) but are never an assertable target; the physical lines of an anonymous class declaration do count towards the `lines` of the method that declares it, since they sit inside that method's braces.
- Inherited methods are never counted on the child class — only where they are declared.

## License

MIT. See `LICENSE`.
