<!--
id: troubleshooting
tags: ''
-->

## Error: Class "Drupal\my_module\Bar" not found

### Fix

1. Use the `--flush` flag...
2. `./run_phpunit_tests.sh --flush`

* [Possible more info](https://drupal.stackexchange.com/a/299070)

## Error in bootstrap script: ValueError:

Path cannot be empty
#0 /Users/aaronklump/Code/Projects/NationalUniversity/TheCommons/site/app/web/core/tests/Drupal/TestTools/PhpUnitCompatibility/PhpUnit8/ClassWriter.php(58): file_get_contents('')

### Fix

Make sure you have installed "drupal/core-dev" at the same major/minor as "drupal/core"

1. grep drupal/core-recommended composer.json
2. `composer require --dev drupal/core-dev:`..., e.g. `composer require --dev drupal/core-dev:9.5.11`

## PHP Warning:  Undefined array key "INSTALL_PATH"

Have you installed _drupal/core-dev_ in your Drupal project?

### Fix

1. `composer require --dev drupal/core-dev` in the Drupal root.
