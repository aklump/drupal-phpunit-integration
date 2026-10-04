<!--
id: conventional_vs_this_way
tags: ''
-->

# The Conventional Way, Then This Package's Way

[Why Do You Need This?](@why) makes the argument in prose. This page makes it in commands: the fully conventional way to run a Drupal module's tests with nothing but core itself, then exactly what this package changes (and doesn't) on top of it.

## The conventional way

This is the entire contract Drupal core itself defines for Kernel and Functional tests. Nothing below is specific to any package, including this one.

### 1. Install `drupal/core-dev`

```shell
composer require --dev drupal/core-dev:^10
```

This is the one Composer dependency the convention needs. It provides `vendor/bin/phpunit` and every Drupal test base class (`KernelTestBase`, `BrowserTestBase`, `UnitTestCase`, …).

### 2. Write tests under the standard directories

```
web/modules/custom/my_module/
└── tests/
    └── src/
        ├── Kernel/
        │   └── FooTest.php        namespace Drupal\Tests\my_module\Kernel
        └── Functional/
            └── BarTest.php        namespace Drupal\Tests\my_module\Functional
```

That's the whole registration step. There is no `phpunit.xml` entry to add, no composer.json autoload-dev block for a test to be discovered. Core finds it because of where it lives and what its namespace says, not because anything was told to look for it.

### 3. Export two environment variables, yourself, before running

```shell
export SIMPLETEST_DB="mysql://user:pass@host:3306/my_database"
export SIMPLETEST_BASE_URL="http://my-site.local/"
```

### 4. Run core's own `phpunit.xml.dist` directly

```shell
vendor/bin/phpunit -c web/core --testsuite=kernel,functional --group my_module
```

`-c web/core` points PHPUnit at `core/phpunit.xml.dist`, which is the actual, canonical configuration — the one drupal.org's own GitLab CI template uses under the hood, and the one every other Drupal developer's machine can run unmodified.

### Why step 2 needs no registration: a look under the hood

`core/phpunit.xml.dist`'s `kernel` testsuite doesn't list directories — it delegates to a small PHP class:

```xml
<testsuite name="kernel">
  <file>./tests/TestSuites/KernelTestSuite.php</file>
</testsuite>
```

```php
// core/tests/TestSuites/KernelTestSuite.php
public static function suite() {
  $root = dirname(__DIR__, 3);
  $suite = new static('kernel');
  $suite->addTestsBySuiteNamespace($root, 'Kernel');
  return $suite;
}
```

`addTestsBySuiteNamespace()` walks the *entire* Drupal root — core, contrib, custom, all of it — looking for any `tests/src/Kernel` directory and adds every test it finds. `FunctionalTestSuite` does the identical thing for `tests/src/Functional`, and `UnitTestSuite` the identical thing for `tests/src/Unit`. This is why a module never registers its own tests anywhere: the convention is the directory and the namespace, nothing else. The exact discovery line, traced into `TestSuiteBase::addTestsBySuiteNamespace()`, is hardcoded:

```php
// core/tests/TestSuites/TestSuiteBase.php
$test_path = "$dir/tests/src/$suite_namespace";
```

There is no alternate path. A test file has to physically live at `<module>/tests/src/<SuiteNamespace>/...` or core's discovery will never see it — not "won't autoload it," won't even attempt to include it in the run. Autoloadable and discoverable are two different mechanisms, and this is the only one that decides what PHPUnit runs.

(This particular discovery class is deprecated as of Drupal 10.3 and removed in 11.0 — PHPUnit 10 does test discovery differently — but the contract it implements, "directory and namespace is the registration," is expected to survive the mechanism change. See https://www.drupal.org/node/3405829.)

### What the conventional way *can't* see: a module's own `autoload-dev`

The directory convention above is the only registration that exists. `core/tests/bootstrap.php` is the actual code that wires up the autoloader for a test run, and it does this per extension, with nothing configurable:

```php
// core/tests/bootstrap.php, drupal_phpunit_get_extension_namespaces()
if (is_dir($dir . '/src')) {
  $namespaces['Drupal\\' . $extension . '\\'][] = $dir . '/src';
}
if (is_dir($dir . '/tests/src')) {
  $namespaces['Drupal\\Tests\\' . $extension . '\\'][] = $dir . '/tests/src';
}
```

That's the entire autoloading contract for every extension, core or contrib: exactly two fixed namespaces, derived purely from two fixed directory names. This function never opens a module's `composer.json` — not its `autoload` key, not its `autoload-dev` key. If a module wants a shared test trait or helper class living somewhere other than `Drupal\Tests\<module>\` under `tests/src/` — say, a trait meant to be reused by *other* modules' tests, declared via its own `autoload-dev` — the conventional path has no mechanism to find it, full stop.

It's not only that bootstrap.php declines to look: Composer itself wouldn't honor that declaration either way. Composer's own schema documentation marks the `autoload-dev` key `[root-only]` — it is only ever processed for the project's own root `composer.json`, and is explicitly ignored in every dependency package, your custom module included. Two independent reasons, same result: a module-level `autoload-dev` is invisible to the conventional path.

This package's `AutoloadDev` class exists specifically to undo that: for each directory your `phpunit.xml` lists in a `<testsuite>`, it walks up to the nearest `composer.json` and merges that package's own `autoload-dev` into the cached PSR-4 map — the one case in this whole comparison where this package does something the conventional way has no equivalent for at all, rather than just automating it. See [Composer Autoload Dev](@composer_autoload_dev).

## Proof this really is the entire contract

The exact test files this package would normally run for `auto_retina` were pointed at core's own `phpunit.xml.dist`, with the two variables above exported by hand and this package nowhere in the invocation or the module's own `composer.json`:

```shell
export SIMPLETEST_DB="mysql://drupal10:drupal10@database:3306/drupal10"
export SIMPLETEST_BASE_URL="http://drupal10.local.loft/"
vendor/bin/phpunit -c web/core --testsuite=kernel,functional --group auto_retina --testdox
```

```
OK (18 tests, 39 assertions)
```

No module-specific configuration beyond those two lines. That's the baseline this package has to earn its place against.

## Where the conventional way costs you, every day

1. **Discovery re-scans the whole codebase on every invocation.** There is no cache; core can't assume what changed since last time, so it doesn't try.
2. **You personally re-derive two values, every session, on every machine.** Not because core is wrong to ask for them — it has no way to know your local database or site URL — but because nothing computes them for you.

## This package's way: the same contract, made fast and automatic

Everything in "The conventional way" above is still exactly true when this package is installed. Nothing about writing a Kernel or Functional test changes. What changes is who pays the two costs above, and when:

| | Conventional | This package |
|---|---|---|
| Test discovery | Full codebase scan, every run | Scanned once, cached to a file, rescanned only on `--flush` (`BootstrapCache`) |
| `SIMPLETEST_DB` | You export it, by hand, every session | Resolved automatically — your own exported value first, then your git branch's own `DATABASE_URL`, then plain `DATABASE_URL`, then Lando, then `drush sql:connect` (`DynamicConfig`) |
| `SIMPLETEST_BASE_URL` | You export it, by hand, every session | Resolved automatically — your own value first, then Lando, then Drush's site URI (`DynamicConfig`) |
| How the values reach a Kernel/Functional test | A real process environment variable (`export`), inherited by the subprocess PHPUnit forks for every test | `putenv()` — a real process environment variable, for the same reason: see [Why Do You Need This?](@why) on `runTestInSeparateProcess` |
| Command | `vendor/bin/phpunit -c web/core --testsuite=kernel,functional --group my_module` | `bin/run-phpunit-tests.sh` |
| Test discovery registration (Kernel/Functional) | None — directory and namespace only | None — directory and namespace only, unchanged |
| A module's own `autoload-dev` namespaces | **Never loaded.** `bootstrap.php` only wires up two fixed namespaces by directory name; it never reads any module's `composer.json`. Composer itself wouldn't honor it either way — `autoload-dev` is root-only. | Scanned and merged into the cached PSR-4 map automatically (`AutoloadDev`, rebuilt on `--flush`) — no equivalent to "automate," since none exists conventionally |

The "Test discovery registration" row is the one that matters most for trusting this package: for Kernel and Functional tests, it adds **zero** new registration steps on top of the conventional ones. A module's Kernel/Functional tests written against this package are the same tests, in the same place, with the same namespace, that core's own `phpunit.xml.dist` would find on its own.

The `autoload-dev` row is the one exception to that pattern — it isn't automating an existing conventional step, because no such step exists. That's also true of one more thing the table above doesn't attempt to capture: **Unit** and **Integration** tests that use Drupal classes with no bootstrap at all. Neither of those two is "the same contract, made faster" — there is no core contract for either one to accelerate. See [Why Do You Need This?](@why) for that half of the story.

## A fixed foot-gun: Unit tests now live where core already looks

Core's "Unit" discovery works exactly like Kernel and Functional, down to the same hardcoded line: `$test_path = "$dir/tests/src/Unit"`. A plain `PHPUnit\Framework\TestCase` with zero Drupal dependency, placed at `tests/src/Unit/FooTest.php` with namespace `Drupal\Tests\my_module\Unit`, is already discovered and run by core's own `phpunit.xml.dist`, with no bootstrap, no database, and no package of any kind installed. Core already has exactly the "boots nothing" Unit test type this package also offers.

This package's documentation used to place that same test type at `tests/Unit/FooTest.php` instead — one directory shallower, missing the `src/` segment core's discovery requires. That one missing path segment was enough to make the file permanently invisible to `vendor/bin/phpunit -c web/core`, `core/scripts/run-tests.sh`, and drupal.org's GitLab CI template, for a test type that had no technical reason to diverge from the conventional path at all. It was never autoloading that broke — `AutoloadDev` or a plain `autoload-dev` entry can make the class loadable either way — it was discovery, which checks the literal directory string and nothing else.

That's fixed now: both flavors live under `tests/src/`, the same root core itself uses:

```
web/modules/custom/alpha/tests/src/Unit/FooTest.php
web/modules/custom/alpha/tests/src/Integration/FooTest.php
```

**Integration tests remain a different story, and were never a flaw in the same sense.** "Integration" is not a suite core defines at all — `UnitTestSuite`, `KernelTestSuite`, and `FunctionalTestSuite` are the only three for an extension's own tests, with no fourth for a no-bootstrap-but-uses-Drupal-classes flavor. Since there is no conventional "integration" suite to fall back to, `tests/src/Integration/` costs nothing `tests/Integration/` didn't already lack — drupal.org's CI template was never going to run an "integration" suite either way, directory or no directory. It moved to `tests/src/` anyway, for one consistent rule rather than two.

## A test-only dependency, and the two different "root"s that matter

Fixing the directory only solves *discovery*. A Unit or Integration test that needs a third-party class — Mockery, Faker, anything beyond Drupal core itself — has a second, separate question: *which* `composer.json` actually has to declare it for the class to be installed anywhere at all.

A contrib module has no site of its own, so "add it to the site's root `composer.json`" — the obvious-looking answer by analogy with everything else in this comparison — doesn't even apply: there is no root the module's author controls. The real answer turns out to depend on which of two genuinely different "conventional" paths is running the test, and they disagree.

**drupal.org's actual GitLab CI already solves this, by merging your module's manifest into its own.** The template's `scripts/expand_composer_json.php` builds the scaffold site it tests your module against by deep-merging *your module's own* `composer.json` — `require`, `require-dev`, everything — into the generated manifest, before running `composer install` against the result. A dependency you declare in your own module's `require-dev` is not a dependency of some separate root the CI owns; it becomes part of the root CI builds, by design. For drupal.org's real pipeline, your module's own `require-dev` is exactly the right, fully-supported place for a test-only package. (Verified against the live script at `git.drupalcode.org/project/gitlab_templates`, not inferred from Composer's general root-only rule — that rule is real, but this template works around it deliberately.)

**This package's own local runner does not do the same merge, and that's the actual gap.** `bin/run-phpunit-tests.sh` runs `tests_phpunit/vendor/bin/phpunit`, against `tests_phpunit/`'s own, separate Composer project. `AutoloadDev` merges a module's `autoload-dev` *namespace map* into that project's autoloader, but nothing merges the module's `require-dev` *packages* into `tests_phpunit/composer.json` the way `expand_composer_json.php` does for the scaffold. A package declared only in your module's own `require-dev` is therefore exactly backwards from what the directory-convention fix addressed: it's discoverable and correctly placed, installed and working under drupal.org's real CI, and yet absent locally under this package's own runner — the opposite failure direction, and arguably the more dangerous one, since it means a maintainer can see green in CI and "class not found" on their own machine, or the reverse, depending on which vendor happens to hold the class that day.

**The fix is a code change, not a rule, and it isn't built yet.** Making local and CI agree means this package's own bootstrap should do what `expand_composer_json.php` already proves works: read the tested module's own `require-dev`, and actually install those packages into (or alongside) `tests_phpunit/vendor`, not just map their namespaces. Until that exists, a module-level `require-dev` test dependency is genuinely unsafe to rely on with this package's local runner, even though it's the correct and fully portable choice for drupal.org's own CI.

## What this means for you

- Nothing about how you *write* a Kernel or Functional test changes. The same file runs, unmodified, through `vendor/bin/phpunit -c web/core` with hand-exported variables, through `bin/run-phpunit-tests.sh` with this package installed, and through drupal.org's GitLab CI template.
- A Unit test written at `tests/src/Unit/` gets the same dual portability for free, as long as it doesn't lean on a third-party dependency — if it does, today, only drupal.org's real CI is guaranteed to have it; this package's local runner is not, until it gains its own equivalent of `expand_composer_json.php`.
- An Integration test has no conventional path to fall back to either way, so its directory choice doesn't cost portability it could otherwise have had.
- If this package were deleted from a project tonight, every Kernel, Functional, and Unit test would still run tomorrow — slower, and only after you export two variables by hand again — provided none of them depend on a third-party package only this package's own `tests_phpunit/` ever installed. That's the whole bet this package makes: it should always be safe to fall back to the row on the left.

## Related topics

- [Why Do You Need This?](@why)
- [Composer Autoload Dev](@composer_autoload_dev)
