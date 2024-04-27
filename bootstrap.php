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
use Symfony\Component\Console\Output\OutputInterface;

require_once $_ENV['TESTS_ROOT'] . '/vendor/autoload.php';
require_once __DIR__ . '/src/Runner/BootstrapCache.php';
require_once __DIR__ . '/src/Runner/AutoloadDev.php';

$output = new Symfony\Component\Console\Output\ConsoleOutput();

$drupal_root = $_ENV['DRUPAL_ROOT'] ?? getenv('DRUPAL_ROOT');
if (!$drupal_root || !file_exists($drupal_root) || !is_dir($drupal_root)) {
  $output->writeln(sprintf("<error>DRUPAL_ROOT must be a path to an existing Drupal webroot to test.  The value %s is invalid.</error>", $drupal_root));
  exit(1);
}

/** @var string $install_path The path to tests_integration/ * */
$install_path = $_ENV['TESTS_ROOT'] ?? getcwd();
$bootstrap = new BootstrapCache($install_path);
$build_cache = in_array('--flush', $GLOBALS['argv']) || !$bootstrap->cacheExists();
if ($build_cache) {
  $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
}

$phpunit_configuration = "$install_path/phpunit.xml";
if (!file_exists($phpunit_configuration)) {
  $output->writeln(sprintf("phpunit.xml not found at %s\nDid you set TESTS_ROOT?\n", $phpunit_configuration));
  exit(1);
}

// Allow this to be called with --flush to dump our caching layer before running
// the tests..  This caching layer greatly speeds up the time it takes to run
// the first test.
if (in_array('--flush', $GLOBALS['argv'])) {
  $bootstrap->flush();
  $output->writeln('<info>The Drupal autoload map cache has been flushed.</info>');
}

// When we flush, we scan for autoload-dev namespaces.
if ($build_cache) {
  $extra_psr4['AKlump\\Drupal\\PHPUnit\\Integration\\'] = [__DIR__ . '/src/'];
  $autoload_dev = (new AutoloadDev($phpunit_configuration, $drupal_root))
                    ->getAutoloadDev()['psr-4'] ?? [];
  $extra_psr4 += $autoload_dev;
  $bootstrap = new BootstrapCache($install_path, $extra_psr4);
  $output->writeln(sprintf('<info>Imported %d autoload-dev %s.</info>', count($autoload_dev), count($autoload_dev) === 1 ? 'namespace' : 'namespaces'));
  $output->writeln(array_keys($autoload_dev), OutputInterface::VERBOSITY_VERBOSE);
  $output->writeln('<info>Building cache; this takes a moment...</info>');
}

$bootstrap->require("$drupal_root/core/tests/bootstrap.php");
