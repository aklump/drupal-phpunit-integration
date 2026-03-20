<!--
id: changelog
tags: ''
-->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

- init/run_phpunit_tests.sh; you should cherry pick these changes on update.

### Fixed

- An issue that wouldn't pass more than one arg to PhpUnit in file: init/run_phpunit_tests.sh was

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

