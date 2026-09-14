# pest-plugin-quality


A [Pest](https://pestphp.com) plugin that adds method-level maintainability limits — cyclomatic complexity, body line count, and parameter count — to Pest's `arch()` chain, with baselines for gradual adoption on existing codebases.

> **Work in progress.** This package has no product behaviour yet; nothing here should be depended on until v0.1.0 ships.

## Requirements

- PHP ^8.4
- Pest ^5.0

## Development

```sh
composer install
composer check   # Pint (check), PHPStan (level max), Pest
```

Individual steps: `composer lint` (fix formatting), `composer lint:check` (verify only), `composer analyse` (PHPStan), `composer test` (Pest), `composer test:parallel` (Pest, parallel).

## License

MIT. See `LICENSE`.
