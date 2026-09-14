# The project

`ianrodrigues/pest-plugin-code-quality` adds method-level maintainability limits to Pest's `arch()` chain: `toHaveMethodComplexityAtMost()`, `toHaveMethodLinesAtMost()` and `toHaveMethodParametersAtMost()`, with baselines for gradual adoption. It is a third-party plugin by Ian Rodrigues. It is not part of Pest.

---

## 1. The reader of this file

This file is for a coding agent. No user reads it. Write an instruction that an agent obeys during its work, then stop. Do not write an introduction, a conclusion, or an argument for a decision that the project made. Give a reason only when the reason changes the next decision of the agent.

Start each rule with a verb in the imperative. Put the condition before the instruction. Give the exact path, the exact command and the exact name that the agent must use.

`README.md`, `CHANGELOG.md`, `CONTRIBUTING.md` and the `description` in `composer.json` are for a user. Section 3 controls them.

---

## 2. How to write for an agent

Write in Simplified Technical English: one instruction in one sentence, the imperative, the active voice, one meaning for one word, no contraction, and no synonym for variety. Keep the exact form of a quotation, an identifier in the code, a path, a command and a name from a different supplier.

Write GitHub flavored Markdown. Put one `#` heading in a file. Write one paragraph on one line. Put a path, a command and a name from the code in `code font`.

Write no new rule for a fault that you imagine. Write a rule only for a fault that happened, and ask the user before you add it. A correction from the user is a rule and needs no question: write it here before you continue the work. Replace an old rule; do not write a second rule near it. Write no sentence for a fact that an agent finds when it reads the code; put the fact in a test instead.

---

## 3. How to write the public text

Say what the project does, then stop. Do not say that the project is fast, simple, powerful or intelligent. Show the command and let the reader form that opinion. Cut each superlative. Cut each sentence that a competitor can also write about itself.

Write for one person and call that person "you". Put the result first and the mechanism second. One command in a terminal is worth one paragraph of adjectives.

Obey three limits. Do not promise a feature that does not exist today. Do not give a number without its source. Do not name a different product to make a comparison, except in the table of metric deltas in `README.md`, where the difference is the fact.

`README.md` is the only user document. Do not create a `docs/` directory. Tag each executable sample in `README.md` with `<!-- readme-test: <name> -->` on the line before its fence, so `tests/Feature/ReadmeTest.php` runs it.

---

## 4. How to write the code

Put the intention in the code. Give each item a name that says what the item does. Give each value a type that makes a wrong value impossible. Split a long function into two functions with two names.

Write a comment only for a reason that the code cannot hold: a constraint from Pest's internals, a quirk of an upstream library, a decision that the next reader will want to reverse. Keep it to three lines. Delete each comment that says what the next lines do, and each docblock that repeats a signature. Keep a docblock that PHPStan needs, such as `@param list<string>` or `@return array{...}`.

Name a class method in `camelCase()`. Name a standalone function in `snake_case()`.

Declare `strict_types=1` in each file. Make each class `final` unless a test extends it. Make a value object `readonly`. Write a `match` over `IanRodrigues\CodeQuality\Metrics\Metric` without a `default` arm, so PHPStan reports a missing case.

Put a new metric in `src/Metrics/Metric.php` as a case, give it a row in each `match` of that enum, and write its fixtures in `tests/Fixtures/Metrics/<topic>/` with hand-derived values before you write the visitor. `CONTRIBUTING.md` holds the full procedure.

Do not read `vendor/pestphp/pest-plugin-arch` classes through inheritance; they are `final` and `@internal`. Reach them through their public static factories, as `src/Expectations/PolicyExpectation.php` does, and keep `pestphp/pest-plugin-arch` pinned to `~5.0.0` in `composer.json`.

---

## 5. How to verify the work

Run `composer install` once per clone; it enables the git hooks in `.githooks/`.

Run `composer check` before you call a change done. It runs Pint (`composer lint:check`), PHPStan at level max (`composer analyse`), Rector in dry-run mode (`composer rector:check`) and Pest (`composer test`). Run `composer test:parallel` as well when you change anything under `src/Selection/Plugins/`, `src/Reporting/` or `src/Baseline/`, because those merge worker output.

Run `composer lint` to fix formatting and `composer rector` to apply Rector. Do not add a `@phpstan-ignore` comment, a PHPStan baseline, or a Rector skip to make a check pass; fix the code.

Set `XDEBUG_MODE=off` when you run PHP on a machine that loads Xdebug; timings and output are wrong with it on.

Run `composer perf` to reproduce the numbers in the `Performance` section of `README.md`.

---

## 6. How to commit

Make one commit for one logical change. Split unrelated changes even when you made them together.

Write the subject as Conventional Commits: `feat:`, `fix:`, `docs:`, `test:`, `chore:`, `ci:`, `refactor:`, `perf:`. Start the description in lower case, use the imperative, put no period at the end, and keep it under 72 characters. Leave the second line blank. Write a body only when the subject cannot carry the reason.

Describe the change on its own terms. Do not mention a task, a plan, a requirements document, a spike, research, or a tool that wrote the code. Do not add a signature or a trailer.

`.githooks/commit-msg` rejects a subject that breaks these rules. `.githooks/pre-commit` runs Pint on the staged PHP files.

---

## 7. How to write a message to the user

Give the result first. Give the exact path, the exact command and the exact name. Write no sentence that says the work again, and no adjective that gives the reader no new fact.
