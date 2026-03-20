<!--
id: developers
tags: ''
-->

# Developer Notes

### How to Run Package Self Tests

In order to self-test this package you must be able to point to a Drupal codebase using the variables shown below.  Also note, you must be using the same PHP version as the codebase.  The tests will use these variables to access the Drupal classes and bootstrapping necessary.

The PHPUnit configuration file _self.xml_, is located in the root of the package.

```shell
export VENDOR_PATH=/Users/aklump/Code/Projects/InTheLoftStudios/TabulonSB/site/app/vendor
export DRUPAL_ROOT=/Users/aklump/Code/Projects/InTheLoftStudios/TabulonSB/site/app/web
export INSTALL_PATH=/Users/aklump/Code/Projects/InTheLoftStudios/TabulonSB/site/app/tests_phpunit/
vendor/bin/phpunit -c /Users/aklump/Code/Packages/php/drupal-phpunit-integration/app/self.xml

```
