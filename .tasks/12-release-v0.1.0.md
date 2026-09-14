# 12 · Release v0.1.0

Status: todo
Depends on: all previous tasks done

## Goal

The first public version, installable from Packagist, with a changelog and a stated versioning policy.

## Checklist

- Confirm package name and vendor ownership on Packagist; update `composer.json` and README if the working name changes.
- `CHANGELOG.md`: move `Unreleased` to `0.1.0 – <date>` with sections Added, Known limitations (`not`, vendor code, single Pest major, internal-API pin).
- `docs/versioning.md`: semantic versioning policy; metric definitions are versioned independently and a metric version bump is a minor release with a changelog note; support-matrix changes are documented per release.
- Verify the README quick start on a fresh Laravel 13 app and a framework-independent package, both on PHP 8.4 and 8.5.
- Tag `v0.1.0` on `main`, create the GitHub release from the changelog section, submit to Packagist, enable the Packagist GitHub hook.
- Post-release: open `after v0.1.0` task files for Git ratcheting (PRD §8), class coupling, named presets and persistent cache if it was deferred in task 11.

## Acceptance criteria

- `composer require --dev ianrodrigues/pest-plugin-quality` on a clean project installs v0.1.0 and the README example runs.
- GitHub release and Packagist page show the same version and changelog.
- CI on the tag is green across the full matrix.
