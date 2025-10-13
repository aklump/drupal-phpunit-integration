<!--
id: troubleshooting
tags: ''
-->

## Error: Class "Drupal\my_module\ViewModes" not found

### Fix

1. Use the `--flush` flag...
2. `./run_phpunit_tests.sh --flush`

* [Possible more info](https://drupal.stackexchange.com/a/299070)

## Error in bootstrap script: ValueError:

Path cannot be empty
#0 /Users/aaronklump/Code/Projects/NationalUniversity/TheCommons/site/app/web/core/tests/Drupal/TestTools/PhpUnitCompatibility/PhpUnit8/ClassWriter.php(58): file_get_contents('')

### Fix

Make sure you have installed "drupal/core-dev" at the same major/minor as "drupal/core"

## PHP Warning:  Undefined array key "INSTALL_PATH"

Have you installed _drupal/core-dev_ in your Drupal project?

### Fix

1. `composer require --dev drupal/core-dev` in the Drupal root.

## PHP Fatal error: Declaration of Symfony\Component\DependencyInjection\ContainerInterface::has($id) must be compatible with Psr\Container\ContainerInterface::has(string $id): bool

### Fix

This is probably happening on an older version of Drupal, where the container version is mismatched.
Change the installed version of psr/container to match that of the Drupal installation.

```php
cd tests_php_unit
composer require psr/container:^1.1
```
