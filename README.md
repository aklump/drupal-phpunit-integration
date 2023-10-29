# Drupal PHPUnit Integration Testing

## Installation

```shell
mkdir -p tests_integration/src
cd tests_integration
echo "vendor/\ncomposer.lock\n*.cache\n" > .gitignore
echo '{"autoload":{"psr-4":{"\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\":"src"}},"repositories":[{"type":"github","url":"https://github.com/aklump/drupal-phpunit-integration"}]}' > composer.json
composer require aklump/drupal-phpunit-integration:@dev
```

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

## Test Support Classes

* The directory _src/_ is namespaced to `AKlump\Drupal\PHPUnit\Integration`
* Place shared traits and other test support in _src/_ using said namespace.

## Project Development Notes

### How to Run Self Tests

```shell
export DRUPAL_ROOT=/path/to/drupal/web
vendor/bin/phpunit -c vendor/aklump/drupal-phpunit-integration/self.xml
```
