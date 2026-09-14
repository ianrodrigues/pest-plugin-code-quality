# 05 · Failure reporting

Status: todo
Depends on: 04

## Goal

A failing policy reports every violating method in one run, deterministically, in a message a developer can act on without reading engine docs (PRD §4 "Understand the failure" and F4).

## Behaviour

- Replace the first-failure loop with our own iteration over the resolved layer: collect all violations for the policy, then throw one `QualityExpectationFailed` (extending Pest's `ArchExpectationFailedException` so Collision still renders file:line).
- Sort violations by path, symbol, metric.
- Message format per violation:

  ```
  App\Http\Controllers\CheckoutController::store
  app/Http/Controllers/CheckoutController.php:42

  Method complexity (ccn2 v1): 17
  Allowed: at most 10
  Exceeded by: 7
  ```

  followed by a summary line `3 methods exceed the limit` and, when truncated, `Showing 20 of 37. Run with --quality-inspect for the full list.` Truncation limit is 20 by default and documented.
- The Collision snippet points at the first violating method's declaration line.
- Engine, discovery and parse errors are reported as errors (not failures) with a distinct heading `Quality analysis error` and never as "no violations".
- No advice text telling the developer how to refactor.

## Acceptance criteria

- Feature tests assert the exact message for one, several and truncated violation sets, with stable ordering across shuffled input.
- A syntax-error fixture inside a targeted namespace makes the test error, with the path in the message.
- Existing Pest tests in the same file keep their own results (a mixed file with passing unit tests and a failing policy).
- Output is identical in serial and parallel mode.
