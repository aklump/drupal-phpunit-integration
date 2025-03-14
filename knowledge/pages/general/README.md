<!--
id: readme
tags: ''
-->

# Drupal PHPUnit Integration Testing Framework

## How to Install

**You must execute the following in the directory above _web_.**  It will install this package in a directory called _tests\_integration_.

```shell
mkdir -p tests_integration/src
cd tests_integration
echo "/vendor/\n*/.cache\n/reports/\n" > .gitignore
echo '{"autoload":{"psr-4":{"\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\":"src"}},"repositories":[{"type":"github","url":"https://github.com/aklump/drupal-phpunit-integration"}]}' > composer.json
composer require aklump/drupal-phpunit-integration:^0
```

This will create the file for running your tests:

```shell
mkdir -p ../bin
cp vendor/aklump/drupal-phpunit-integration/init/run_phpunit_tests.sh ../bin/
```

This will create _phpunit.xml_ for configuring testing.

```shell
cp vendor/aklump/drupal-phpunit-integration/init/phpunit.xml.dist phpunit.xml
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

## Config File

Now open _tests_integration/phpunit.xml_ and add one or more integration test directories:

```xml
<testsuites>
  <testsuite name="integration">
    <directory>../web/modules/custom/alpha/tests/Integration/</directory>
    <directory>../web/modules/custom/bravo/tests/Integration/</directory>
    <directory>../web/modules/custom/charlie/tests/Integration/</directory>
  </testsuite>
</testsuites>
```

## Run Your Tests

1. `cd` into the directory above web root.
2. Run tests with `bin/run_phpunit_tests.sh`

The first time the tests are run, a cache is built that speeds up subsequent
runs. To flush these caches, add the `--flush` parameter,
e.g. `bin/run_phpunit_tests.sh --flush`.

## Built-in Test Support Classes

Have a look in the following directories:

* _tests_integration/vendor/aklump/drupal-phpunit-integration/src/Framework/MockObject_

## Custom Test Support Classes

* The directory _tests_integration/src/_ is namespaced to `AKlump\Drupal\PHPUnit\Integration`
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

Given the above module file structure, you can see two directories in _tests_.  _tests/Unit/FooTest.php_ can be run using _alpha/bin/run_unit_tests.sh_ and has no Drupal dependencies. Therefore it's straight-up PHPUnit stuff. On the other hand,  _tests/Integration/FooTest.php_ cannot be run in the same manner as it has Drupal class dependencies, hence it "integrates" with Drupal. For that you must use _tests_integration/bin/run_phpunit_tests.sh_.

**Use `namespace Drupal\Tests\alpha\Unit;` for unit test classes.**

Unit tests are only mentioned here to distinguish the difference. This package concerns itself with Integration tests, with one caveat: it is convenient to add the _Unit_ directory to  _tests_integration/phpunit.xml_ so that Unit tests are run at the same time as the Integration tests. This is a good idea and encouraged. In our example, it will look like this.

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

## What About _tests\_integration/composer.lock_?

It's up to you, but it seems like a good idea to source code commit this file as it will provide more stability to your app for tests passing if you have to reinstall dependencies.

## How to Update this Package

To get the newest version of _aklump/drupal-phpunit-integration_:

```bash
cd tests_integration
composer update
```

**This will only update the _vendor/_ directory so your changes and files
in _tests_integration_ are not affected.**

You may want to diff _run_phpunit_tests.sh_ and _phpunit.xml_ from time to time and cherry pick as necessary, however, _CHANGELOG.md_ should make note of any changes to these files.

```php
cd tests_integration
diff vendor/aklump/drupal-phpunit-integration/init/run_phpunit_tests.sh ../bin/run_phpunit_tests.sh
diff vendor/aklump/drupal-phpunit-integration/init/phpunit.xml.dist phpunit.xml
```
