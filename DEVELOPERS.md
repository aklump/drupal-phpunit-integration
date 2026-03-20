# Developer Notes

### How to Run Package Self Tests

In order to self-test this package you must be able to point to a Drupal codebase. To do that you will set these first two variables. The tests will use these variables to access the Drupal classes and bootstrapping necessary.

The PHPUnit configuration file _self.xml_, is located in the root of the package.

```shell
export DRUPAL_ROOT=/Users/aklump/Code/Projects/MyDrupalProject/web
export INSTALL_PATH=/Users/aklump/Code/Projects/MyDrupalProject/
vendor/bin/phpunit -c /Users/aklump/Code/Packages/php/drupal-phpunit-integration/app/self.xml
```
