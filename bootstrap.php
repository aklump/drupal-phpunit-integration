<?php

/**
 * @file
 * Bootstrap Drupal 8 for PhpUnit testing.
 *
 * Use this file as your phpunit.xml bootstrap file to speed up your tests.
 *
 * @code
 *   <phpunit bootstrap="./bootstrap.php"></phpunit>
 * @endcode
 *
 * To flush the cache do like this:
 * @code
 *   $ php ./tests_integration/bootstrap.php flush
 * @endcode
 *
 */

use AKlump\Drupal\PHPUnit\Integration\Runner\AutoloadDev;
use AKlump\Drupal\PHPUnit\Integration\Runner\BootstrapCache;

require_once __DIR__ . '/src/Runner/BootstrapCache.php';
require_once __DIR__ . '/src/Runner/AutoloadDev.php';

$drupal_root = $_ENV['DRUPAL_ROOT'] ?? getenv('DRUPAL_ROOT');
if (!$drupal_root || !file_exists($drupal_root) || !is_dir($drupal_root)) {
  die(sprintf("DRUPAL_ROOT must be a path to an existing Drupal webroot to test.  The value %s is invalid.\n", $drupal_root));
}

$extra_psr4 = ['AKlump\\Drupal\\PHPUnit\\Integration\\' => __DIR__ . '/src/'];

/** @var string $install_path The path to presumably "tests_integration/" */
$install_path = explode('vendor/', __DIR__)[0] ?? __DIR__;
if (file_exists($install_path . '/phpunit.xml')) {
  // autoload-dev will not be loaded from the packages by default because it's a
  // root-only key, however it's best practice
  // (https://getcomposer.org/doc/04-schema.md#autoload-dev) to use autoload-dev
  // for test-only classes; these will not load without this next step which
  // bubbles up all the autoload-dev PSR-4 values into Drupal's autoloader.
  $autoload_dev_psr4 = (new AutoloadDev($install_path . '/phpunit.xml', $drupal_root))
                         ->getAutoloadDev()['psr-4'] ?? [];
  if ($autoload_dev_psr4) {
    $extra_psr4 = array_merge($extra_psr4, $autoload_dev_psr4);
  }
}

$bootstrap = new BootstrapCache($install_path, $extra_psr4);

// Allow this to be called with --flush to dump our caching layer before running
// the tests..  This caching layer greatly speeds up the time it takes to run
// the first test.
if (in_array('--flush', $GLOBALS['argv'])) {
  $bootstrap->flush();
  echo "The Drupal autoload map cache has been flushed." . PHP_EOL;
}

if (!$bootstrap->cacheExists()) {
  echo "Building cache; this takes a moment..." . PHP_EOL;
}

$bootstrap->require("$drupal_root/core/tests/bootstrap.php");

