var lunrIndex = [{"id":"changelog","title":"Changelog","body":"All notable changes to this project will be documented in this file.\n\nThe format is based on [Keep a Changelog](https:\/\/keepachangelog.com\/en\/1.0.0\/),\nand this project adheres to [Semantic Versioning](https:\/\/semver.org\/spec\/v2.0.0.html).\n\n## [Unreleased]\n\n- None\n\n## [0.0.15] - 2024-02-14\n\n### Changed\n\n- init\/run_integration_tests.sh; you should cherry pick these changes on update.\n\n### Fixed\n\n- An issue that wouldn't pass more than one arg to PhpUnit in file: init\/run_integration_tests.sh was\n\n## [0.0.1] - 2023-10-28\n\n### Added\n\n- lorem\n\n### Changed\n\n- Namespace changed from `\\AKlump\\Drupal\\PHPUnit\\Integration\\` to `AKlump\\Drupal\\PHPUnit\\Integration\\`\n\n### Deprecated\n\n- lorem\n\n### Removed\n\n- lorem\n\n### Fixed\n\n- lorem\n\n### Security\n\n- lorem"},{"id":"composer_autoload_dev","title":"Composer Autoload Dev","body":"The _composer.json_ property `autoload-dev` will not be loaded from the packages by default because it's a root-only key, however it's best practice\n(https:\/\/getcomposer.org\/doc\/04-schema.md#autoload-dev) to use autoload-dev\nfor test-only classes.\n\n**This project includes the `autoload-dev` in the normal autoloader.**\n\n@see `\\AKlump\\Drupal\\PHPUnit\\Integration\\Runner\\AutoloadDev` for more info."},{"id":"developers","title":"Developer Notes","body":"### How to Run Self Tests\n\n```shell\nexport DRUPAL_ROOT=\/path\/to\/drupal\/web\nvendor\/bin\/phpunit -c vendor\/aklump\/drupal-phpunit-integration\/self.xml\n```"},{"id":"readme","title":"Drupal PHPUnit Integration Testing Framework","body":"## How to Install\n\n**You must execute the following in the directory above _web_.**  It will install this package in a directory called _tests_integration_.\n\n```shell\nmkdir -p tests_integration\/src\ncd tests_integration\necho \"vendor\/\\n*.cache\\n\" > .gitignore\necho '{\"autoload\":{\"psr-4\":{\"\\\\\\\\AKlump\\\\\\\\Drupal\\\\\\\\PHPUnit\\\\\\\\Integration\\\\\\\\\":\"src\"}},\"repositories\":[{\"type\":\"github\",\"url\":\"https:\/\/github.com\/aklump\/drupal-phpunit-integration\"}]}' > composer.json\ncomposer require aklump\/drupal-phpunit-integration:^0\n```\n\nThis will create the file for running your tests:\n\n```shell\nmkdir -p ..\/bin\ncp vendor\/aklump\/drupal-phpunit-integration\/init\/run_integration_tests.sh ..\/bin\/\n```\n\nThis will create _phpunit.xml_ for configuring testing.\n\n```shell\ncp vendor\/aklump\/drupal-phpunit-integration\/init\/phpunit.xml.dist phpunit.xml\n```\n\n## Test Class Namespace & File Structure\n\n**Before continuing please read the section _Difference Between Integration Tests and Unit Tests_ so you create the tests appropriately.**\n\nCreate your first integration test class:\n\n```\nweb\/modules\/custom\n\u2514\u2500\u2500 alpha\n    \u2514\u2500\u2500 tests\n        \u2514\u2500\u2500 Integration\n            \u2514\u2500\u2500 FooTest.php\n```\n\n_FooTest.php_\n\n```php\nnamespace Drupal\\Tests\\alpha\\Integration;\n\nclass FooTest extends \\PHPUnit\\Framework\\TestCase {\n```\n\nEnsure your module's _web\/modules\/custom\/composer.json_ has the proper autoloading configuration:\n\n```json\n{\n  \"autoload\": {\n    \"psr-4\": {\n      \"Drupal\\\\alpha\\\\\": \"src\"\n    }\n  },\n  \"autoload-dev\": {\n    \"psr-4\": {\n      \"Drupal\\\\Tests\\\\alpha\\\\\": \".\/tests\/\"\n    }\n  }\n}\n```\n\n## Config File\n\nNow open _tests_integration\/phpunit.xml_ and add one or more integration test directories:\n\n```xml\n\n    ..\/web\/modules\/custom\/alpha\/tests\/Integration\/\n    ..\/web\/modules\/custom\/bravo\/tests\/Integration\/\n    ..\/web\/modules\/custom\/charlie\/tests\/Integration\/\n\n```\n\n## Run Your Tests\n\n1. `cd` into the directory above web root.\n2. Run tests with `bin\/run_integration_tests.sh`\n\nThe first time the tests are run, a cache is built that speeds up subsequent\nruns. To flush these caches, add the `--flush` parameter,\ne.g. `bin\/run_integration_tests.sh --flush`.\n\n## Built-in Test Support Classes\n\nHave a look in the following directories:\n\n* _tests_integration\/vendor\/aklump\/drupal-phpunit-integration\/src\/Framework\/MockObject_\n\n## Custom Test Support Classes\n\n* The directory _tests_integration\/src\/_ is namespaced to `AKlump\\Drupal\\PHPUnit\\Integration`\n* Place shared traits and other test support in _src\/_ using said namespace.\n\n## Difference Between Integration Tests and Unit Tests\n\n```\nweb\/modules\/custom\n\u2514\u2500\u2500 alpha\n    \u251c\u2500\u2500 bin\n    \u2502\u00a0\u00a0 \u2514\u2500\u2500 run_unit_tests.sh\n    \u251c\u2500\u2500 src\n    \u2502\u00a0\u00a0 \u2514\u2500\u2500 Foo.php\n    \u2514\u2500\u2500 tests\n        \u251c\u2500\u2500 Integration\n        \u2502\u00a0\u00a0 \u2514\u2500\u2500 FooTest.php\n        \u2514\u2500\u2500 Unit\n            \u251c\u2500\u2500 FooTest.php\n            \u2514\u2500\u2500 phpunit.xml\n```\n\nGiven the above module file structure, you can see two directories in _tests_.  _tests\/Unit\/FooTest.php_ can be run using _alpha\/bin\/run_unit_tests.sh_ and has no Drupal dependencies. Therefore it's straight-up PHPUnit stuff. On the other hand,  _tests\/Integration\/FooTest.php_ cannot be run in the same manner as it has Drupal class dependencies, hence it \"integrates\" with Drupal. For that you must use _tests_integration\/bin\/run_integration_tests.sh_.\n\n**Use `namespace Drupal\\Tests\\alpha\\Unit;` for unit test classes.**\n\nUnit tests are only mentioned here to distinguish the difference. This package concerns itself with Integration tests, with one caveat: it is convenient to add the _Unit_ directory to  _tests_integration\/phpunit.xml_ so that Unit tests are run at the same time as the Integration tests. This is a good idea and encouraged. In our example, it will look like this.\n\n```xml\n\n    ..\/web\/modules\/custom\/alpha\/tests\/Unit\/\n\n    ..\/web\/modules\/custom\/alpha\/tests\/Integration\/\n\n```\n\n## What About _tests_integration\/composer.lock_?\n\nIt's up to you, but it seems like a good idea to source code commit this file as it will provide more stability to your app for tests passing if you have to reinstall dependencies.\n\n## How to Update this Package\n\nTo get the newest version of _aklump\/drupal-phpunit-integration_:\n\n```bash\ncd tests_integration\ncomposer update\n```\n\n**This will only update the _vendor\/_ directory so your changes and files\nin _tests_integration_ are not affected.**\n\nYou may want to diff _run_integration_tests.sh_ and _phpunit.xml_ from time to time and cherry pick as necessary, however, _CHANGELOG.md_ should make note of any changes to these files.\n\n```php\ncd tests_integration\ndiff vendor\/aklump\/drupal-phpunit-integration\/init\/run_integration_tests.sh ..\/bin\/run_integration_tests.sh\ndiff vendor\/aklump\/drupal-phpunit-integration\/init\/phpunit.xml.dist phpunit.xml\n```"},{"id":"troubleshooting","title":"Troubleshooting","body":"There may be a bug with the autoload-dev where you will see a class not found after a --flush, but the class and namespace are correct.  If this happens it may be the wrong message, and due to an incorrect autoload path, such as one that doesn't exist. I can't replicate now, but maybe it will happen again."}]