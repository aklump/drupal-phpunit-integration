<!--
id: install
tags: ''
-->

# Install

## This Package

```shell
mkdir -p tests_phpunit/src
echo "/vendor/\n*/.cache\n/.phpunit.cache\n/reports/\n" > tests_phpunit/.gitignore
```

### Main Version

```shell
echo '{"autoload":{"psr-4":{"\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\":"src"}},"repositories":[{"type":"github","url":"https://github.com/aklump/drupal-phpunit-integration"}]}' > tests_phpunit/composer.json
(cd tests_phpunit && composer require aklump/drupal-phpunit-integration:^0)
```

### Dev Version

```shell
echo '{"autoload":{"psr-4":{"\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\":"src"}},"repositories":[{"type":"path","url":"/Users/aklump/Code/Packages/php/drupal-phpunit-integration/app"}]}' > tests_phpunit/composer.json
(cd tests_phpunit && composer require aklump/drupal-phpunit-integration:@dev)
```

7. 

2. bin/run_phpunit_tests.sh --flush
