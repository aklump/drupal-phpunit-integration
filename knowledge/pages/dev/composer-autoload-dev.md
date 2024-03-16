<!--
id: composer_autoload_dev
tags: ''
-->

# Composer Autoload Dev

The _composer.json_ property `autoload-dev` will not be loaded from the packages by default because it's a root-only key, however it's best practice
(https://getcomposer.org/doc/04-schema.md#autoload-dev) to use autoload-dev
for test-only classes.

**This project includes the `autoload-dev` in the normal autoloader.**

@see `\AKlump\Drupal\PHPUnit\Integration\Runner\AutoloadDev` for more info.
