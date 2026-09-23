# drupal-phpunit-integration

> PHPUnit tests for Drupal custom code that needs Drupal classes, without writing a full kernel test.

![Hero image](images/drupal-phpunit-integration.jpg)

## Summary

Drupal's own test types sit at two extremes: `UnitTestCase` still pulls in Drupal's test base classes, and kernel and functional tests need a database and a bootstrapped site. Much custom module code falls in between: it uses Drupal interfaces and classes, but its logic can be tested with plain PHPUnit and a few mocks. This package gives that code a home. It installs a separate PHPUnit project in `tests_phpunit/` next to your Drupal site, autoloads Drupal core and your modules' `autoload-dev` namespaces from a cache that is built once and reused, and gives you a runner script, `bin/run-phpunit-tests.sh`, that points PHPUnit at the right configuration from anywhere in the project. When your tests do need Drupal's database, it fills in `SIMPLETEST_DB` and `SIMPLETEST_BASE_URL` for you from your environment, Lando or Drush.

## Quick Start

From the root of a Composer-based Drupal project (the directory that contains `web/core`):

```shell
curl -sSL https://raw.githubusercontent.com/aklump/drupal-phpunit-integration/main/bin/install.sh | bash -s --
```

The installer creates `tests_phpunit/`, requires this package into it, adds `drupal/core-dev` to your project if it is missing, copies Drupal core's `phpunit.xml.dist` to `tests_phpunit/phpunit.xml` and copies the runner to `bin/run-phpunit-tests.sh`. It ends with:

```text
## Final step
Run the tests with:
bin/run-phpunit-tests.sh --flush

Installation completed successfully!
```

Point the test suite at your module (see [Configuration](#configuration)), then run your tests. Use `--flush` the first time so the autoload cache is built:

```shell
bin/run-phpunit-tests.sh --flush
```

## Requirements

- A Composer-based Drupal site whose webroot is `web/`; the installer stops with `Error: Drupal not found at web/core/scripts/drupal` otherwise.
- `drupal/core-dev` at the same major and minor version as `drupal/core`. The installer tries to add it for you.
- Composer, and a PHP version supported by PHPUnit 10.5 or 11, which this package requires. For PHPUnit 9, install the 9.x line (see below).

## Installation

### Installer script

The Quick Start command is the supported install. It refuses to run if `tests_phpunit/` already exists. By default it requires the `@dev` version of the package; set `VERSION` to choose another constraint, for example the 9.x line for PHPUnit 9:

```shell
export VERSION=^9;curl -sSL https://raw.githubusercontent.com/aklump/drupal-phpunit-integration/main/bin/install.sh | bash -s --
```

### Should `tests_phpunit/composer.lock` be committed?

That is up to you, but committing it keeps your test dependencies stable when they are reinstalled.

### Updating

```bash
cd tests_phpunit
composer update
```

This only updates `tests_phpunit/vendor/`, so your `phpunit.xml`, runner and support classes are not touched. The changelog notes when the runner template or `phpunit.xml` changes; compare yours with the package's copies from time to time and cherry-pick what you need:

```bash
cd tests_phpunit
diff vendor/aklump/drupal-phpunit-integration/init/run-phpunit-tests.sh ../bin/run-phpunit-tests.sh
diff ../web/core/phpunit.xml.dist phpunit.xml
```

## Configuration

### Test suites and source paths

The installer writes placeholder paths for a module named `my_module` into `tests_phpunit/phpunit.xml`. Open it and replace the `<testsuites>` directories and the `<source><include>` directory with your real paths, relative to `tests_phpunit/`. It is convenient to list your modules' unit tests here as well, so both kinds run together:

```xml
<testsuites>
    <testsuite name="unit">
        <directory>../web/modules/custom/alpha/tests/Unit/</directory>
    </testsuite>
    <testsuite name="integration">
        <directory>../web/modules/custom/alpha/tests/Integration/</directory>
    </testsuite>
</testsuites>
```

### The runner

`bin/run-phpunit-tests.sh` has a configuration block at the top: `INSTALL_PATH`, `DRUPAL_ROOT` and `VENDOR_PATH` (relative to the script), `CODE_COVERAGE` to always generate coverage, `PHPUNIT_ARGS` for default PHPUnit arguments, and an optional `PHP_BIN` directory to put first on `PATH`.

### Database and base URL

Kernel and functional tests need `SIMPLETEST_DB`. The `DynamicConfig` extension sets it at bootstrap, using the first of these that has a value:

1. `SIMPLETEST_DB`, if you already set it in `phpunit.xml` or your environment.
2. `DATABASE_URL__<BRANCH>`, where `<BRANCH>` is the upper-cased name of the git branch checked out in the Drupal root.
3. `DATABASE_URL`.
4. The database of the current Lando app, using its internal connection inside Lando and its external connection from the host.
5. The output of `drush sql:connect`.

`SIMPLETEST_BASE_URL` works the same way: your own value first, then Lando, then Drush's site URI. `BROWSERTEST_OUTPUT_DIRECTORY` defaults to `tests_phpunit/test_output/`. If no database can be found, the run stops with instructions for setting `SIMPLETEST_DB`, for example `mysql://username:password@localhost/databasename`.

## Usage

### Running tests

```shell
# Everything in phpunit.xml
bin/run-phpunit-tests.sh

# Rebuild the autoload cache first, e.g. after adding a module or namespace
bin/run-phpunit-tests.sh --flush

# One test class
bin/run-phpunit-tests.sh --filter=FooTest

# One file; paths relative to the project root work
bin/run-phpunit-tests.sh web/modules/custom/alpha/tests/Integration/FooTest.php

# HTML coverage report in tests_phpunit/reports (needs Xdebug or PCOV)
bin/run-phpunit-tests.sh --coverage
```

Any argument the runner does not handle is passed to PHPUnit. `-c` and `--configuration` are ignored on purpose: the runner always uses `tests_phpunit/phpunit.xml`, so relative paths resolve the same way every time. The autoload cache is built on the first run and reused after that; `autoload-dev` paths are only scanned during `--flush`, so flush when a class is reported as not found.

### Unit tests versus integration tests

```
web/modules/custom
└── alpha
    ├── src
    │   └── Foo.php
    └── tests
        ├── Integration
        │   └── FooTest.php
        └── Unit
            └── FooTest.php
```

`tests/Unit/FooTest.php` has no Drupal dependencies; it is plain PHPUnit and can run on its own. `tests/Integration/FooTest.php` uses Drupal classes, so it has to run through `bin/run-phpunit-tests.sh`, which loads them. Use the `Drupal\Tests\alpha\Unit` and `Drupal\Tests\alpha\Integration` namespaces:

```php
namespace Drupal\Tests\alpha\Integration;

class FooTest extends \PHPUnit\Framework\TestCase {
```

and declare them in the module's `composer.json` so the runner can autoload them:

```json
{
  "autoload": {
    "psr-4": {
      "Drupal\\alpha\\": "src"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Drupal\\Tests\\alpha\\": "./tests/"
    }
  }
}
```

### Mocking entities

`MockDrupalEntityTrait` builds entity mocks whose fields behave like the real thing. Fields are keyed by name; each holds a list of items, and a scalar value sets the entity ID:

```php
use AKlump\Drupal\PHPUnit\Integration\Framework\MockObject\MockDrupalEntityTrait;
use Drupal\node\NodeInterface;

final class FooTest extends \PHPUnit\Framework\TestCase {

  use MockDrupalEntityTrait;

  public function testTitle() {
    $node = $this->createEntityMock('node', 'page', [
      'nid' => 9,
      'title' => [['value' => 'Somewhere Over the Rainbow']],
    ], '', NodeInterface::class);

    $this->assertSame('9', $node->id());
    $this->assertSame('Somewhere Over the Rainbow', $node->getTitle());
    $this->assertSame('Somewhere Over the Rainbow', $node->get('title')->first()->value);
  }
}
```

An item can hold another mocked entity (`'field_account' => [$account]`), which is then reachable through `->get('entity')->getTarget()->getEntity()`. `createUserMock($fields)` is a shortcut for a mocked `UserInterface`. To make any mock iterable, use `MakeMockIterable`:

```php
(new MakeMockIterable())($mock, ['lorem', 'ipsum']);
```

### Your own test support classes

`tests_phpunit/src/` is autoloaded under the `AKlump\Drupal\PHPUnit\Integration` namespace. Put shared traits and helpers there.

## License

[GPL-2.0](LICENSE)
