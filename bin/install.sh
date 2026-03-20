#!/bin/bash

# Constants
VERSION=${VERSION:-'@dev'}
DRUPAL_PATH="web/core/scripts/drupal"
TEST_DIR="tests_phpunit"
COMPOSER_CONFIG='{
  "autoload": {
    "psr-4": {
      "\\\\AKlump\\\\Drupal\\\\PHPUnit\\\\Integration\\\\": "src"
    }
  },
  "repositories": [{
    "type": "github",
    "url": "https://github.com/aklump/drupal-phpunit-integration"
  }]
}'

# Verify not already installed
[ -e "$TEST_DIR" ] && echo "$TEST_DIR is already installed" && exit 1

# Check if directory is writable
[ ! -w "$(dirname "$TEST_DIR")" ] && error_exit "Parent directory not writable"

# Verify Drupal installation
[ ! -e "$DRUPAL_PATH" ] && echo "Error: Drupal not found at $DRUPAL_PATH" && exit 1

# Setup test environment
export INSTALL_DIR="$TEST_DIR" && \
mkdir -p "$INSTALL_DIR/src" && \
echo "/vendor/\n*/.cache\n/.phpunit.cache\n/reports/\n" > "$INSTALL_DIR/.gitignore" && \
echo "$COMPOSER_CONFIG" > "$INSTALL_DIR/composer.json" && \
(cd "$INSTALL_DIR" && composer require aklump/drupal-phpunit-integration:$VERSION) && \
"$TEST_DIR/vendor/aklump/drupal-phpunit-integration/bin/install.php"
