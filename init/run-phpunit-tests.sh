#!/usr/bin/env bash

#!/usr/bin/env bash

# Run PHPUnit tests using this project's Drupal PHPUnit configuration.
#
# Usage:
#   bin/run-phpunit-tests.sh [options] [phpunit-args]
#
# Options:
#   --coverage
#     Generate an HTML coverage report in tests_phpunit/reports. This is slower
#     and requires a coverage driver such as Xdebug or PCOV.
#
#   --flush
#     Flush the Drupal PHPUnit integration bootstrap cache before running tests.
#
#   -c, --configuration, --configuration=FILE
#     Ignored intentionally. This script always uses tests_phpunit/phpunit.xml so
#     relative suite paths, Drupal bootstrap paths, and dynamic test configuration
#     are resolved consistently.
#
# Examples:
#   # Run all configured PHPUnit tests.
#   bin/run-phpunit-tests.sh
#
#   # Run all kernel tests.
#   bin/run-phpunit-tests.sh --kernel
#
#   # Run one test class by PHPUnit filter.
#   bin/run-phpunit-tests.sh --kernel --filter=UserFormatNameAlterTest
#
#   # Run one test file. Paths may be relative to the project root.
#   bin/run-phpunit-tests.sh web/modules/custom/commons_core/tests/src/Kernel/UserFormatNameAlterTest.php
#
#   # Flush bootstrap cache, then run kernel tests.
#   bin/run-phpunit-tests.sh --flush --kernel
#
#   # Generate coverage for a filtered run.
#   bin/run-phpunit-tests.sh --coverage --filter=GenerateSortByTest
#
# Notes:
#   CLI arguments not handled by this script are passed through to PHPUnit.
#   Existing project-root-relative file paths are converted to absolute paths
#   before PHPUnit runs because this script executes PHPUnit from tests_phpunit/.
#
#   Database configuration for Drupal kernel/functional tests is supplied by the
#   Drupal PHPUnit integration's dynamic configuration. When running under Lando,
#   it should use Lando's internal database connection. When running from the host
#   machine, it should use Lando's external database connection.

s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

# ========= Begin Configuration =========
# Paths may be absolute or relative to this script's directory.
INSTALL_PATH="../tests_phpunit/"
DRUPAL_ROOT="../web/"
VENDOR_PATH="../tests_phpunit/vendor/"
# Set this to true to automatically create coverage reports (slower) or use
# `--coverage` in the CLI to run them on demand.
CODE_COVERAGE=false
# Set any default PHPUnit arguments here; CLI args are appended below. @see
# phpunit -h for available.
PHPUNIT_ARGS=()
# Optional PHP binary directory to prepend to PATH; keep empty for default php.
#PHP_BIN="/Applications/MAMP/bin/php/php8.3.14/bin"
# ========= End Configuration =========

# ========= Validation =========
[[ -z "$DRUPAL_ROOT" ]] && echo "❌️ \$DRUPAL_ROOT cannot be empty" && exit 3
DRUPAL_ROOT="$(cd "$__DIR__/$DRUPAL_ROOT" && pwd)"
[[ -d "$DRUPAL_ROOT" ]] || { echo "❌️ \"$DRUPAL_ROOT\" does not exist; check the \$DRUPAL_ROOT variable in $0"; exit 1; }

[[ -z "$INSTALL_PATH" ]] && echo "❌️ \$INSTALL_PATH cannot be empty" && exit 3
INSTALL_PATH="$(cd "$__DIR__/$INSTALL_PATH" && pwd)"
[[ -d "$INSTALL_PATH" ]] || { echo "❌️ \"$INSTALL_PATH\" does not exist; check the \$INSTALL_PATH variable in $0"; exit 2; }
[[ -f "$INSTALL_PATH/phpunit.xml" ]] || { echo "❌️ \"$INSTALL_PATH/phpunit.xml\" does not exist"; exit 7; }

[[ -z "$VENDOR_PATH" ]] && echo "❌️ \$VENDOR_PATH cannot be empty" && exit 4
VENDOR_PATH="$(cd "$__DIR__/$VENDOR_PATH" && pwd)"
[[ -d "$VENDOR_PATH" ]] || { echo "❌️ \"$VENDOR_PATH\" does not exist; check the \$VENDOR_PATH variable in $0"; exit 5; }
[[ -f "$VENDOR_PATH/bin/phpunit" ]] || { echo "❌️ missing dependencies; try \`composer install\`"; echo; exit 6; }


# ========= Internal config =========
PROJECT_ROOT="$(cd "$__DIR__/.." && pwd)"

[[ -n "$PHP_BIN" ]] && export PATH="$PHP_BIN:$PATH"

# shellcheck disable=SC2034
coverage_reports="$INSTALL_PATH/reports"

export DRUPAL_ROOT
export INSTALL_PATH
export VENDOR_PATH

# ========= Bootstrap Drupal =========
skip_next_arg=false

for arg in "$@"; do
  if [[ "$skip_next_arg" == true ]]; then
    skip_next_arg=false
    continue
  fi

  case "$arg" in
    --flush)
      bootstrap_file="$VENDOR_PATH/aklump/drupal-phpunit-integration/bootstrap.php"
      [[ -f "$bootstrap_file" ]] || { echo "❌️ \"$bootstrap_file\" does not exist; try \`composer install\`"; exit 8; }
      php "$bootstrap_file" --flush
      ;;

    --coverage)
      CODE_COVERAGE=true
      ;;

    --configuration=*)
      # This script owns PHPUnit configuration. Ignore external config args so
      # callers do not accidentally point PHPUnit at the wrong phpunit.xml.
      ;;

    --configuration|-c)
      # Ignore this arg and its value for the same reason as above.
      skip_next_arg=true
      ;;

    /*)
      PHPUNIT_ARGS=("${PHPUNIT_ARGS[@]}" "$arg")
      ;;

    *)
      # Preserve each forwarded PHPUnit argument as a single argument, including
      # values containing spaces.
      if [[ -e "$PROJECT_ROOT/$arg" ]]; then
        PHPUNIT_ARGS=("${PHPUNIT_ARGS[@]}" "$PROJECT_ROOT/$arg")
      else
        PHPUNIT_ARGS=("${PHPUNIT_ARGS[@]}" "$arg")
      fi
      ;;
  esac
done

cd "$INSTALL_PATH" || exit 2

# ========= Execute PHPUnit =========
if [[ "$CODE_COVERAGE" == true ]]; then
  export XDEBUG_MODE="${XDEBUG_MODE:+$XDEBUG_MODE,}coverage"
  PHPUNIT_ARGS=("${PHPUNIT_ARGS[@]}" "--coverage-html=$coverage_reports")
fi
"$VENDOR_PATH/bin/phpunit" -c "$INSTALL_PATH/phpunit.xml" "${PHPUNIT_ARGS[@]}"
if [[ "$CODE_COVERAGE" == true ]]; then
  echo "$coverage_reports/index.html"
fi
