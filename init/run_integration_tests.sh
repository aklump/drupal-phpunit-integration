#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

cd "$__DIR__/.."
export DRUPAL_ROOT="$(cd ./web && pwd)"
./vendor/bin/phpunit -c ./tests_integration/phpunit.xml "$@"
#./vendor/bin/phpunit -c ./tests_integration/phpunit.xml --testdox "$@"
#./vendor/bin/phpunit -c ./tests_integration/phpunit.xml --coverage-html=./tests_integration/reports "$@"
#echo "./tests_integration/reports/index.html"
