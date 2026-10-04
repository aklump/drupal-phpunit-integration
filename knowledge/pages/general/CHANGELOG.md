<!--
id: changelog
tags: ''
-->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [10.2.1] - 2026-10-04

### Added

- `DRUPAL_PHPUNIT_INTEGRATION_VERSION='^9'` now installs the 9.x line.
- The install path and the Drupal core path are configurable with `DRUPAL_PHPUNIT_INTEGRATION_INSTALL_PATH` and `DRUPAL_PHPUNIT_INTEGRATION_DRUPAL_CORE`; the generated `phpunit.xml` and runner follow them.

### Changed

- **Breaking:** install with `curl -sSL https://raw.githubusercontent.com/aklump/drupal-phpunit-integration/main/bin/install.php | php` (was `install.sh | bash`).
- All installer logic now lives in `bin/install.php`, which bootstraps `tests_phpunit/`, requires the package and configures it. `bin/install.sh` is removed.
- PHPUnit 11 is now configured the same way as PHPUnit 10.

### Fixed

- The installer resolved the default `@dev` version to the `9.x-dev` branch, which has no `bin/install.php`, and then failed without making the failure obvious. The default is now `dev-main`.
- The installer now stops with a prominent failure banner and fix instructions on every error.
- The installer wrote literal `\n` characters into `tests_phpunit/.gitignore` and called an undefined `error_exit`.
- The installer exited with "Runner script not found" at its last step because it looked for `init/run_*.sh` and the runner is `init/run-phpunit-tests.sh`.

## [10.0.0] - 2025-11-27
  
### Changed

- Bumped version to match the PhpUnit version supported, e.g. 10.
- Simplified the installation process considerably

## [9.0.0] - 2025-11-27

### Changed

- Bumped version to match the PhpUnit version supported, e.g. 9.

## [0.0.23] - 2025-10-10

### Added

- Ability to use `-c` on CLI to point to a different config file.
- Support for `getTitle` method when creating entity mocks using \Drupal\node\NodeInterface.

### Fixed

- Ability to run self tests with _self.xml_ config file.

## [0.0.21] - 2025-03-27

### Added

- Better handling of environment variables

### Changed

- Language change "integration_tests" to "phpunit_tests" in several places

## [0.0.18] - 2024-04-27

### Changed

- autoload-dev is now only scanned during --flush

### Fixed

- An issue with some autoload-dev paths not being found.

## [0.0.15] - 2024-02-14

### Changed

- init/run-phpunit-tests.sh; you should cherry pick these changes on update.

### Fixed

- An issue that wouldn't pass more than one arg to PhpUnit in file: init/run-phpunit-tests.sh was

## [0.0.1] - 2023-10-28

### Added

- lorem

### Changed

- Namespace changed from `\AKlump\Drupal\PHPUnit\Integration\` to `AKlump\Drupal\PHPUnit\Integration\`

### Deprecated

- lorem

### Removed

- lorem

### Fixed

- lorem

### Security

- lorem

