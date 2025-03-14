#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

# ========= Begin Configutation =========
DRUPAL_ROOT="../web/"
INSTALL_PATH="../tests_integration/"
# ========= End Configuration =========

# ========= Validation =========
[[ -z "$DRUPAL_ROOT" ]] && echo "\$DRUPAL_ROOT cannot be empty" && exit 3
DRUPAL_ROOT="$(cd "$__DIR__/$DRUPAL_ROOT" && pwd)"
[[ -d "$DRUPAL_ROOT" ]] || exit 1

[[ -z "$INSTALL_PATH" ]] && echo "\$INSTALL_PATH cannot be empty" && exit 3
INSTALL_PATH="$(cd "$__DIR__/$INSTALL_PATH" && pwd)"
cd "$INSTALL_PATH" || exit 2

# ========= Internal config =========
# shellcheck disable=SC2034
coverage_reports="$INSTALL_PATH/reports"

export DRUPAL_ROOT
export INSTALL_PATH

# ========= Bootstrap Drupal =========
phpunit_args=()
for arg in "$@"; do
  if [[ "$arg" == "--flush" ]]; then
    php "$INSTALL_PATH/vendor/aklump/drupal-phpunit-integration/bootstrap.php" --flush
  else
    phpunit_args=("${phpunit_args[@]}" "$arg")
  fi
done

# ========= Execute PHPUnit =========
./vendor/bin/phpunit -c phpunit.xml "${phpunit_args[@]}"
#./vendor/bin/phpunit -c phpunit.xml --testdox "${phpunit_args[@]}"
#export XDEBUG_MODE=$XDEBUG_MODE,coverage;./vendor/bin/phpunit -c phpunit.xml --coverage-html="$coverage_reports" "${phpunit_args[@]}"
#echo "$coverage_reports/index.html"
