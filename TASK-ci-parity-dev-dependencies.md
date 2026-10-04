# Task: CI parity for *-dev dependencies (autoload-dev and require-dev)

## Acceptance criterion (the thing that must be true when this is done)

> Ensure any test that works with this [package] also works with the conventional way, a.k.a., the CI pipeline.

Operationalized, this means both directions must hold:

1. **Local → CI**: a test that passes locally via `bin/run-phpunit-tests.sh` must also pass under drupal.org's real GitLab CI template for the same module, for the same reason (not by accident).
2. **CI → Local**: a test that passes under drupal.org's real GitLab CI template must also pass locally via `bin/run-phpunit-tests.sh`, with no extra manual setup beyond what the module's own `composer.json` already declares.

Today, direction 2 is broken for any module that declares a third-party test-only dependency in its own `composer.json`'s `require-dev`. This document is the evidence trail and the fix target for that gap. It does not cover direction-1 breakage, which hasn't been found — only direction 2 is confirmed broken, below.

## What's already fixed (context, not part of this task)

A related but separate gap — this package's own "Unit" tests were documented and generated at `tests/Unit/` instead of `tests/src/Unit/`, making them invisible to Drupal core's own test discovery (`TestSuiteBase::addTestsBySuiteNamespace()` hardcodes `tests/src/$suite_namespace`) — has already been fixed in the docs (`knowledge/pages/general/README.md`, `conventional-way.md`) and recorded in `CHANGELOG.md` under `[Unreleased]` as a breaking change. That fix is about *discovery* (can the test file be found at all). This task is about *dependency resolution* (once found, can its third-party classes actually be loaded) — a different mechanism, confirmed below.

## The two keys, and what this package actually does with each (verified)

- **`autoload-dev`** — a namespace-to-path map ("class `Drupal\Tests\my_module\Traits\FooTrait` lives at this file path"). No package installation involved; the PHP file already exists in the module's own source tree. `AutoloadDev` (`src/Runner/AutoloadDev.php`) reads this key from the tested module's `composer.json` and merges the mapping into the cached autoloader (`BootstrapCache`). **Confirmed working.**
- **`require-dev`** — a dependency declaration ("install `mockery/mockery` into some `vendor/`"). This is what actually downloads a third-party package's code onto disk. **Confirmed: nothing in this package's `src/` or `bin/` reads this key from a tested module's `composer.json`, and nothing runs `composer require`/`install` against it.** (`grep -rn "require-dev" src/ bin/` returns zero matches; the only `composer require`/`install` calls in the codebase install the package itself into `tests_phpunit/`, or `drupal/core-dev` into the site root.)

These are independent mechanisms. A module can have `AutoloadDev` correctly find its own shared test trait while simultaneously having `\Mockery::mock()` fail with "class not found," because autoload-dev can only map you to a file that exists — it can't make a never-downloaded package appear.

## Why this specifically breaks local/CI parity

**drupal.org's real GitLab CI already solves the `require-dev` half of this problem — this package does not.**

Verified by fetching the live script twice independently: `scripts/expand_composer_json.php` in `git.drupalcode.org/project/gitlab_templates` builds the scaffold site a module is tested against by deep-merging the *tested module's own* `composer.json` — `require`, `require-dev`, every key — into the generated manifest, before running `composer install` against the result:

```php
$json_rich = merge_deep($json_project, $json_default, $json_project);
// ... later ...
file_put_contents($output_file, json_encode($json_rich, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
```

The only special-casing found strips `drupal/core`/`drupal/core-recommended` entries, and only when `IGNORE_PROJECT_DRUPAL_CORE_VERSION` is set — `require-dev` itself is never stripped or renamed. So: a module author who adds `mockery/mockery` to their own module's `require-dev` gets it installed and working under drupal.org's real CI, because the CI template doesn't try to make the module a dependency of some separate root — it merges the module's own manifest into the root CI builds.

This package's local runner has no equivalent step. `bin/run-phpunit-tests.sh` runs `tests_phpunit/vendor/bin/phpunit`, against `tests_phpunit/`'s own, separate Composer project (`VENDOR_PATH="../tests_phpunit/vendor/"` in `init/run-phpunit-tests.sh`). `AutoloadDev` merges the tested module's `autoload-dev` *namespaces* into that project's autoloader, but nothing merges the module's `require-dev` *packages* into `tests_phpunit/composer.json`, and nothing installs them anywhere else this package's bootstrap loads.

**Net effect:** a module-level `require-dev` test dependency is correctly installed and working under drupal.org's real CI, and silently absent under this package's own local runner. That's direction 2 of the acceptance criterion, broken — a maintainer can see green in CI and "class not found" on their own machine for the exact same test, for a reason that has nothing to do with their code.

## Pointers for whoever picks this up

- Read `knowledge/pages/general/conventional-way.md`'s section "A test-only dependency, and the two different 'root's that matter" (and the "A fixed foot-gun" section above it, for the already-resolved directory-discovery issue) — the full research trail and verified evidence live there; this document is the condensed task version of it.
- Reference implementation to mirror: `scripts/expand_composer_json.php` in `git.drupalcode.org/project/gitlab_templates` (fetch the live file; summarized above, not reproduced verbatim here).
- `AutoloadDev` (`src/Runner/AutoloadDev.php`) and `BootstrapCache` (`src/Runner/BootstrapCache.php`) are the existing analogous mechanism for `autoload-dev` — study how they read a tested module's `composer.json` and hook into the bootstrap, since the `require-dev` fix needs the same entry points, just installing packages instead of mapping namespaces.
- `init/run-phpunit-tests.sh` and `bootstrap.php` are where `tests_phpunit/`'s own Composer project gets invoked — the fix likely needs either (a) a step that merges a tested module's `require-dev` into `tests_phpunit/composer.json` and runs `composer update` there (mirroring `expand_composer_json.php`'s approach), or (b) some other mechanism that achieves the same observable result: a module's own `require-dev` package is loadable when running via `bin/run-phpunit-tests.sh`, matching what drupal.org's CI already does.
- Whatever the mechanism, re-verify the acceptance criterion in both directions once done — a test case that declares a `require-dev` dependency and asserts it's loadable, run through both `bin/run-phpunit-tests.sh` and (if feasible) a drupal.org CI dry run or an equivalent manual `expand_composer_json.php`-style merge, is the real proof — not just that the code looks like it should work.
