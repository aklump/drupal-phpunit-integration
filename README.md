# Drupal PHPUnit Integration Testing

## Installation

```shell
mkdir -p tests_integration/src
cd tests_integration
echo "vendor/\ncomposer.lock\n*.cache\n" > .gitignore
echo '{"autoload":{"psr-4":{"\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\":"src"}},"repositories":[{"type":"github","url":"https://github.com/aklump/drupal-phpunit-integration"}]}' > composer.json
composer require aklump/drupal-phpunit-integration:^0
```

## Update

To get the newest version of this project use `composer update` as usual.

**This will only update the _vendor/_ directory so your changes and files
in _tests_integration_ are not affected.**

## Config File

```shell
cp vendor/aklump/drupal-phpunit-integration/init/phpunit.xml.dist phpunit.xml
```

Now open _phpunit.xml_ and add one or more integration test directories:

```xml
<testsuites>
  <testsuite name="integration">
    <directory>../web/modules/custom/alpha/tests/Integration/</directory>
    <directory>../web/modules/custom/bravo/tests/Integration/</directory>
    <directory>../web/modules/custom/charlie/tests/Integration/</directory>
  </testsuite>
</testsuites>
```

## Runner File

```shell
mkdir -p ../bin
cp vendor/aklump/drupal-phpunit-integration/init/run_integration_tests.sh ../bin/
```

## Run Your Tests

1. `cd` into the directory above web root.
2. Run tests with `bin/run_integration_tests.sh`

The first time the tests are run, a cache is built that speeds up subsequent
runs. To flush these caches, add the `--flush` parameter,
e.g. `bin/run_integration_tests.sh --flush`.

## Provided Test Support Classes

Have a look in the following directories:

* _vendor/aklump/drupal-phpunit-integration/src/Framework/MockObject_

## Custom Test Support Classes

* The directory _src/_ is namespaced to `AKlump\Drupal\PHPUnit\Integration`
* Place shared traits and other test support in _src/_ using said namespace.
