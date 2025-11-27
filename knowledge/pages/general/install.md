<!--
id: install
tags: ''
-->

# Install

## This Package

1. mkdir -p tests_phpunit/src
2. cd tests_phpunit
3. echo "/vendor/\n*/.cache\n/.phpunit.cache\n/reports/\n" > .gitignore
4. echo '{"autoload":{"psr-4":{"\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\":"src"}},"repositories":[{"type":"github","url":"https://github.com/aklump/drupal-phpunit-integration"}]}' > composer.json
5. composer require aklump/drupal-phpunit-integration:^0

## Drupal Core Dev

1. cd ../web
2. composer show | grep drupal/core
3. Note the Drupal version, e.g. 11.2.8
4. composer require --dev drupal/core-dev:^11.2 to match drupal/core

## Configure this Package

6. cp ../web/core/phpunit.xml.dist phpunit.xml
7. open `phpunit.xml`
8. Replace with `bootstrap="./vendor/aklump/drupal-phpunit-integration/bootstrap.php"`

### PHP Unit 9

9. Add `<extension class="AKlump\Drupal\PHPUnit\Integration\Runner\Extension\DynamicConfig"/>` to the top of `<extensions/>`

### PHP Unit 10

9. Add `<bootstrap class="AKlump\Drupal\PHPUnit\Integration\Runner\Extension\DynamicConfig"/>` to the top of `<extensions/>`
   
---

10. Replace all testsuites with

```xml

<testsuite name="integration">
    <directory>../web/modules/custom/my_module/tests</directory>
</testsuite>
```

11. Replace all `<source>` with:

```xml
  <source ignoreSuppressionOfDeprecations="true">
    <include>
      <directory>../web/modules/custom/my_module/src</directory>
    </include>
  </source>
```

1. mkdir tests_phpunit/test_output
2. bin/run_phpunit_tests.sh --flush
