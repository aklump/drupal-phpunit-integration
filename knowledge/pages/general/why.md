<!--
id: why
tags: ''
-->

# Why Do You Need This?

* https://www.drupal.org/docs/develop/automated-testing
* https://www.drupal.org/docs/develop/automated-testing/phpunit-in-drupal

This package does two different things. Neither one changes what a "correct" Drupal test is, or invents a competing way to write one — see [The Conventional Way, Then This Package's Way](@conventional_vs_this_way) for a line-by-line comparison. This page is about *why* each half exists.

## 1. It fills a real gap: tests that use Drupal classes but need no bootstrap at all

Drupal's own test types sit at two extremes. `Drupal\Tests\UnitTestCase` still pulls in Drupal's test base classes and container plumbing, even for a test that doesn't touch the database. Kernel and functional tests need a real database and a bootstrapped site. A large amount of ordinary module code falls in the gap between those two: it type-hints Drupal interfaces (`EntityInterface`, `ConfigFactoryInterface`, …) and needs real Drupal classes to mock against, but its own logic is pure PHP — no query, no request, no filesystem.

Core gives that code nowhere good to live — and this isn't a matter of degree, it's absolute. `core/tests/bootstrap.php` wires up the test autoloader with exactly two fixed, hardcoded namespaces per extension, derived purely from directory names (`Drupal\<ext>\` from `src/`, `Drupal\Tests\<ext>\` from `tests/src/`); it never opens any module's `composer.json`, so a module's own `autoload-dev` — a shared test trait, say, meant for other modules to reuse — is invisible to it, full stop. Composer itself wouldn't honor that declaration either way: its schema docs mark `autoload-dev` `[root-only]`, meaning it's only ever processed for the root project, never a dependency. Two independent reasons, same dead end.

This package does give that code somewhere to live, with two test flavors that need nothing but plain PHPUnit and an autoloader:

- **Unit** tests have zero Drupal dependency — no `UnitTestCase`, no bootstrap, no autoload-dev scanning. They run as fast as any PHP test ever runs.
- **Integration** tests may use real Drupal classes (and this package's `MockDrupalEntityTrait` for realistic entity mocks), loaded from a namespace map this package builds once and caches by actually reading each module's own `autoload-dev` — the one thing neither Composer nor core's bootstrap does. See [Composer Autoload Dev](@composer_autoload_dev) for how.

Neither flavor is a Drupal convention at all — core has no name for "a test that uses Drupal classes but boots nothing." This package invented one because the gap is real and common, not because core's own test types were insufficient at what *they* do. See [The Conventional Way, Then This Package's Way](@conventional_vs_this_way) for the two-independent-reasons case traced through actual source.

## 2. For the tests that do need Drupal's database (Kernel, Functional), it removes toil without changing the contract

This half is different in kind from the first: Kernel and Functional tests already have a fully conventional, fully sufficient way to run, defined by Drupal core itself — `core/phpunit.xml.dist`, two environment variables (`SIMPLETEST_DB`, `SIMPLETEST_BASE_URL`), and `drupal/core-dev`. This package does not replace, extend, or reinterpret that contract. It automates two specific pieces of manual toil in running it, confirmed by tracing core's own source and then proving the result against a real module's real tests:

**Test discovery rescans the whole codebase, every single invocation.** Core's own `KernelTestSuite`/`FunctionalTestSuite` classes call `addTestsBySuiteNamespace()`, which walks the entire Drupal root looking for every module's `tests/src/Kernel` or `tests/src/Functional` directory — on every `phpunit` run, with no caching, by design (it has to, since it doesn't know what changed). For a tight local edit-test loop, paying that scan on every single run is pure overhead. `BootstrapCache` caches the discovered namespace map to a file and only rebuilds it when you pass `--flush`.

**`SIMPLETEST_DB`/`SIMPLETEST_BASE_URL` are not static values you set once.** They differ per developer machine, per Lando app, and sometimes per git branch if you keep more than one database fixture around. Nobody enjoys re-deriving a `mysql://` connection string or looking up today's Lando URL by hand before every test run, and "I forgot to export `SIMPLETEST_DB`" is one of the most frequently asked Drupal testing questions on record for exactly that reason. `DynamicConfig` resolves both automatically — your own exported value first, then your git branch's own `DATABASE_URL`, then a plain `DATABASE_URL`, then Lando, then `drush sql:connect` — and fails loudly with exact next steps when none of those produce a value, instead of letting the test suite blanket-fail with no explanation.

### Proof, not assertion

The second half above isn't a hunch — the same test files this package would normally run were pointed directly at Drupal core's own `phpunit.xml.dist`, with both environment variables exported by hand, and no trace of this package anywhere in the invocation:

```shell
export SIMPLETEST_DB="mysql://drupal10:drupal10@database:3306/drupal10"
export SIMPLETEST_BASE_URL="http://drupal10.local.loft/"
vendor/bin/phpunit -c web/core --testsuite=kernel,functional --group auto_retina --testdox
```

```
OK (18 tests, 39 assertions)
```

Eighteen tests, zero module-specific configuration beyond the two variables above, and zero involvement from this package — which is exactly the point: the tests this package runs for you are never special. It is free to disappear from the picture entirely, any time, with the same tests still passing the same way. What it buys you is not having to re-derive those two values and re-pay that discovery scan every time you want to run them.

## The one rule that keeps this honest

Both of `DynamicConfig`'s values are set with a real `putenv()` call, not just a `$_ENV[...] = ...` assignment — and that distinction is not cosmetic. `KernelTestBase` and `BrowserTestBase` both declare `protected $runTestInSeparateProcess = TRUE;` in core itself, so PHPUnit forks a brand-new PHP process for *every single test method*. A plain `$_ENV` assignment lives only in the parent process's memory and never reaches that child; a real process environment variable, set via `putenv()`, is inherited across the fork the same way any shell `export` would be. Get this wrong — as this package's own `0.0.x` line did — and every Kernel/Functional test fails identically with a confusing "no database connection" or "no base URL" error that has nothing apparent to do with the test itself.

The underlying design rule, worth restating for anyone extending this package: it must only ever compute the same value a developer would conventionally `export` by hand, and must deliver it the same way a real export would — never a parallel, package-specific mechanism that only works when this package is present. The day it stops doing that, it stops being "the same tests, faster" and becomes a second, incompatible way to test Drupal — which is the one thing it must never become.

## Related topics

- [The Conventional Way, Then This Package's Way](@conventional_vs_this_way)
- [Composer Autoload Dev](@composer_autoload_dev)
- [Install](@install)
