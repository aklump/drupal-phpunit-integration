# Drupal PHPUnit Integration Testing Framework

![Hero image](images/hero.jpg)

## How to Install

The following code will:

* Create a subdirectory `tests_phpunit`
* Install this package using Composer into `tests_phpunit`
* Copy `phpunit.dist.xml` from Drupal core into `tests_phpunit`, replacing some values.
* Create a test runner at `bin/run_phpunit_tests.sh`

```shell
export VERSION=^0;curl -sSL https://raw.githubusercontent.com/aklump/drupal-phpunit-integration/main/bin/install.sh | bash -s --
```

## Configuration

1. Open `tests_phpunit/phpunit.xml`
2. Replace `testsuites testsuite[name="integration"] directory` with a real path.
3. Replace `source include directory` with a real path.

## Run Your Tests

1. `chmod u+x bin/run_phpunit_tests.sh`
3. Run tests with `bin/run_phpunit_tests.sh --flush` (use `--flush` just this first time, or when you need to rebuild the autoloading for dev).

The first time the tests are run, a cache is built that speeds up subsequent
runs. To flush these caches, add the `--flush` parameter,
e.g. `bin/run_phpunit_tests.sh --flush`.

## Built-in Test Support Classes

Have a look in the following directories:

* _tests_phpunit/vendor/aklump/drupal-phpunit-integration/src/Framework/MockObject_

## Custom Test Support Classes

* The directory _tests_phpunit/src/_ is namespaced to `AKlump\Drupal\PHPUnit\Integration`
* Place shared traits and other test support in _src/_ using said namespace.

## Difference Between Integration Tests and Unit Tests

```
web/modules/custom
└── alpha
    ├── bin
    │   └── run_unit_tests.sh
    ├── src
    │   └── Foo.php
    └── tests
        ├── Integration
        │   └── FooTest.php
        └── Unit
            ├── FooTest.php
            └── phpunit.xml
```

Given the above module file structure, you can see two directories in _tests_.  _tests/Unit/FooTest.php_ can be run using _alpha/bin/run_unit_tests.sh_ and has no Drupal dependencies. Therefore it's straight-up PHPUnit stuff. On the other hand,  _tests/Integration/FooTest.php_ cannot be run in the same manner as it has Drupal class dependencies, hence it "integrates" with Drupal. For that you must use _tests_phpunit/bin/run_phpunit_tests.sh_.

**Use `namespace Drupal\Tests\alpha\Unit;` for unit test classes.**

Unit tests are only mentioned here to distinguish the difference. This package concerns itself with Integration tests, with one caveat: it is convenient to add the _Unit_ directory to  _tests_phpunit/phpunit.xml_ so that Unit tests are run at the same time as the Integration tests. This is a good idea and encouraged. In our example, it will look like this.

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

## Test Class Namespace & File Structure

**Before continuing please read the section _Difference Between Integration Tests and Unit Tests_ so you create the tests appropriately.**

Create your first integration test class:

```
web/modules/custom
└── alpha
    └── tests
        └── Integration
            └── FooTest.php
```

_FooTest.php_

```php
namespace Drupal\Tests\alpha\Integration;

class FooTest extends \PHPUnit\Framework\TestCase {
```

Ensure your module's _web/modules/custom/composer.json_ has the proper autoloading configuration:

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

## What About `tests_phpunit/composer.lock`?

It's up to you, but it seems like a good idea to source code commit this file as it will provide more stability to your app for tests passing if you have to reinstall dependencies.

## How to Update this Package

To get the newest version of _aklump/drupal-phpunit-integration_:

```bash
cd tests_phpunit
composer update
```

**This will only update the _vendor/_ directory so your changes and files
in _tests_phpunit_ are not affected.**

You may want to diff _run_phpunit_tests.sh_ and _phpunit.xml_ from time to time and cherry pick as necessary, however, _CHANGELOG.md_ should make note of any changes to these files.

```php
cd tests_phpunit
diff vendor/aklump/drupal-phpunit-integration/init/run_phpunit_tests.sh ../bin/run_phpunit_tests.sh
diff vendor/aklump/drupal-phpunit-integration/init/phpunit.xml.dist phpunit.xml
```
