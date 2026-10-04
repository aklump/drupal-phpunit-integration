#!/bin/bash

# Constants
# Must be dev-main, not @dev: @dev resolves to the highest dev branch (9.x-dev),
# which predates bin/install.php and requires PHPUnit 9.
VERSION=${VERSION:-'dev-main'}
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

# Set once this run creates $TEST_DIR, so fail() knows a partial install exists.
CREATED_TEST_DIR=

# Print a loud, unmissable failure banner, then exit non-zero.
# Usage: fail "what went wrong" ["how to fix it" ...]
fail() {
  local red='' reset=''
  [ -t 2 ] && red=$'\033[1;97;41m' && reset=$'\033[0m'
  {
    echo
    echo "${red}############################################################${reset}"
    echo "${red}##  INSTALLATION FAILED -- drupal-phpunit-integration     ##${reset}"
    echo "${red}############################################################${reset}"
    echo
    echo "❌️ $1"
    shift
    if [ $# -gt 0 ]; then
      echo
      echo "How to fix:"
      for line in "$@"; do echo "  - $line"; done
    fi
    echo
    if [ -n "$CREATED_TEST_DIR" ]; then
      echo "The installation is INCOMPLETE. Do not use $TEST_DIR as-is."
    else
      echo "Nothing was installed."
    fi
    echo
  } >&2
  exit 1
}

# Verify not already installed
[ -e "$TEST_DIR" ] && fail "$TEST_DIR is already installed." \
  "Delete $TEST_DIR (rm -r $TEST_DIR) to reinstall, or edit the existing install."

# Check if directory is writable
[ ! -w "$(dirname "$TEST_DIR")" ] && fail "Parent directory not writable." \
  "Run from a directory you can write to: your Drupal app root."

# Verify Drupal installation
[ ! -e "$DRUPAL_PATH" ] && fail "Drupal not found at $DRUPAL_PATH (cwd: $PWD)." \
  "cd to your Drupal app root (the directory containing web/) and run the installer again."

command -v composer >/dev/null 2>&1 || fail "composer is not on your PATH." \
  "Install Composer: https://getcomposer.org"
command -v php >/dev/null 2>&1 || fail "php is not on your PATH." \
  "Install PHP or add it to your PATH."

# Setup test environment
INSTALLER_PHP="$TEST_DIR/vendor/aklump/drupal-phpunit-integration/bin/install.php"

mkdir -p "$TEST_DIR/src" || fail "Could not create $TEST_DIR/src."
CREATED_TEST_DIR=1
printf '%s\n' "/vendor/" "*/.cache" "/.phpunit.cache" "/reports/" > "$TEST_DIR/.gitignore" \
  || fail "Could not write $TEST_DIR/.gitignore."
echo "$COMPOSER_CONFIG" > "$TEST_DIR/composer.json" \
  || fail "Could not write $TEST_DIR/composer.json."

(cd "$TEST_DIR" && composer require "aklump/drupal-phpunit-integration:$VERSION") \
  || fail "composer require aklump/drupal-phpunit-integration:$VERSION failed." \
    "Read the Composer error above." \
    "Remove the partial install first: rm -r $TEST_DIR" \
    "To pick another version: export VERSION='<constraint>' and run again."

[ -f "$INSTALLER_PHP" ] || fail "Version '$VERSION' was installed but it does not contain bin/install.php." \
  "That version predates the installer (the 9.x line has none; it also requires PHPUnit 9)." \
  "Remove the partial install: rm -r $TEST_DIR" \
  "Run again with the default (unset VERSION, which uses dev-main), or a release that ships bin/install.php." \
  "For 9.x, install manually: see the README, \"Installation\"."

php "$INSTALLER_PHP" || fail "$INSTALLER_PHP exited with an error (see output above)." \
  "Fix the reported problem, then remove the partial install: rm -r $TEST_DIR" \
  "Run the installer again."

echo
echo "✅ drupal-phpunit-integration installed in $TEST_DIR."
