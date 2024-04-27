#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

cd "$__DIR__/.."
export DRUPAL_ROOT="$(cd ./web && pwd)"
cd tests_integration
export TESTS_ROOT="$PWD"
coverage_reports="$TESTS_ROOT/reports"

phpunit_args=()
for arg in "$@"; do
  if [[ "$arg" == "--flush" ]]; then
    php $TESTS_ROOT/vendor/aklump/drupal-phpunit-integration/bootstrap.php --flush
  else
    phpunit_args=("${phpunit_args[@]}" "$arg")
  fi
done

./vendor/bin/phpunit -c phpunit.xml "${phpunit_args[@]}"
#./vendor/bin/phpunit -c phpunit.xml --testdox "${phpunit_args[@]}"
#export XDEBUG_MODE=$XDEBUG_MODE,coverage;./vendor/bin/phpunit -c phpunit.xml --coverage-html="$coverage_reports" "${phpunit_args[@]}"
#echo "$coverage_reports/index.html"
