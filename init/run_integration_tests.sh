#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

cd "$__DIR__/.."
export DRUPAL_ROOT="$(cd ./web && pwd)"

phpunit_args=()
for arg in "$@"; do
  if [[ "$arg" == "--flush" ]]; then
    php tests_integration/vendor/aklump/drupal-phpunit-integration/bootstrap.php --flush
  else
    phpunit_args=("${phpunit_args[@]}" "$arg")
  fi
done

cd tests_integration
./vendor/bin/phpunit -c phpunit.xml "${phpunit_args[@]}"
#./vendor/bin/phpunit -c phpunit.xml --testdox "${phpunit_args[@]}"
#php -d xdebug.mode=coverage ./vendor/bin/phpunit -c phpunit.xml --coverage-html=reports "${phpunit_args[@]}"
#echo "reports/index.html"
