<!--
id: composer_autoload_dev
tags: ''
-->

# Composer Autoload Dev

The _composer.json_ property `autoload-dev` is not be loaded from the packages by default by Composer because it's a root-only key, however it's best practice
(https://getcomposer.org/doc/04-schema.md#autoload-dev) to use autoload-dev
for test-only classes.

Therefore this project makes allowance by scanning for autoload-dev namespaces inside the tests directories included in _phpunit.xml_. And passing those off to the autoloader automatically.

This occurs during `--flush`.

@see `\AKlump\Drupal\PHPUnit\Integration\Runner\AutoloadDev` for more info.
