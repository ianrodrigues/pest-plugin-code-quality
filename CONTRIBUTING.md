# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
1. Create a new branch
1. Code, test, commit and push
1. Open a pull request detailing your changes. Make sure to follow the [template](.github/PULL_REQUEST_TEMPLATE.md)

## Guidelines

* Please ensure the coding style running `composer lint`.
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful, and follows [Conventional Commits](https://www.conventionalcommits.org).
* You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
* Please remember that we follow [SemVer](http://semver.org/). A change to what an existing metric counts is a minor release and a metric version bump, never a silent change.

## Setup

Clone your fork, then install the dev dependencies:
```bash
composer install
```

This also enables the git hooks in `.githooks/`.

## Lint

Lint your code:
```bash
composer lint
```

## Static analysis

Check types with PHPStan at level max:
```bash
composer analyse
```

## Refactoring

Apply Rector, or check what it would change:
```bash
composer rector
composer rector:check
```

## Tests

Run all tests:
```bash
composer test
```

Run all tests in parallel:
```bash
composer test:parallel
```

Run everything the CI runs:
```bash
composer check
```

Run the benchmark against a generated 50,000-line project:
```bash
composer perf
```

## Fixtures

Every counted construct has a fixture under `tests/Fixtures/Metrics/<topic>/`: a `Fixture.php` whose methods carry a hand-derived breakdown comment (`// ccn2: 1 + if + && = 3`), and an `expected.php` returning one row per symbol. A key holding `::` is a method row, `'App\Foo::bar' => [ccn2, lines, params]`. Every other key is a class row, `'App\Foo' => ['methods' => 3, 'accessors' => 1, 'properties' => 2, 'inheritance' => 1, 'classLines' => 12]`, and a topic that declares one declares one for every class-like it holds. `tests/Unit/Metrics/FixtureSuiteTest.php` picks up a new topic directory automatically.

`tests/Fixtures/App/` is measured in-process. `tests/Fixtures/Project/`, `Adoption/` and `Readme/` are throwaway Pest projects driven through `vendor/bin/pest` by `tests/Support/FixtureProject.php`; use them only for behaviour that needs a real process, such as a CLI option or `--parallel`.

Every executable sample in `README.md` is tagged with `<!-- readme-test: <name> -->` on the line before its fence and executed by `tests/Feature/ReadmeTest.php`. Change the sample and the fixture under `tests/Fixtures/Readme/` together.

## Adding a metric

1. Write the fixtures first; they are the specification.
1. Add a case to `src/Metrics/Metric.php` and a row in each of its `match` methods, its `scope()` included.
1. Compute the value in `src/Analysis/Support/MetricsVisitor.php`.
1. Add a `Policy` factory in `src/Policies/Policy.php` and register the expectation in `src/Autoload.php`.
1. Add the `@method` line to `stubs/expectations.stub.php` and the name to `src/PHPStan/MethodLimitsExtension.php`.
1. Document the definition, its version and a worked example in `README.md`, and add a `CHANGELOG.md` entry.
