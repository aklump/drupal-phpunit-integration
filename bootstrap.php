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

use AKlump\Drupal\PHPUnit\Integration\Helper\GetEnv;
use AKlump\Drupal\PHPUnit\Integration\Runner\AutoloadDev;
use AKlump\Drupal\PHPUnit\Integration\Runner\BootstrapCache;
use Symfony\Component\Console\Output\OutputInterface;

// It's possible autoloading has not happened yet, i.e., flushing.
if (!class_exists('AKlump\Drupal\PHPUnit\Integration\Helper\GetEnv')) {
  require_once __DIR__ . '/src/Helper/GetEnv.php';
}

$INSTALL_PATH = (new GetEnv())('INSTALL_PATH');
if (!isset($INSTALL_PATH)) {
  throw new RuntimeException("INSTALL_PATH environment variable cannot be empty" . PHP_EOL);
}

require_once $INSTALL_PATH . '/vendor/autoload.php';
require_once __DIR__ . '/src/Runner/BootstrapCache.php';
require_once __DIR__ . '/src/Runner/AutoloadDev.php';

$output = new Symfony\Component\Console\Output\ConsoleOutput();

$DRUPAL_ROOT = (new GetEnv())('DRUPAL_ROOT');
if (!$DRUPAL_ROOT || !file_exists($DRUPAL_ROOT) || !is_dir($DRUPAL_ROOT)) {
  $output->writeln(sprintf("<error>DRUPAL_ROOT must be a path to an existing Drupal webroot.  The value %s is invalid.</error>", $DRUPAL_ROOT));
  exit(1);
}

$bootstrap = new BootstrapCache($INSTALL_PATH);
$build_cache = in_array('--flush', $GLOBALS['argv']) || !$bootstrap->cacheExists();
if ($build_cache) {
  $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
}

$phpunit_configuration = "$INSTALL_PATH/phpunit.xml";
if (!file_exists($phpunit_configuration)) {
  $output->writeln(sprintf("Missing configuration: %s", $phpunit_configuration));
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
  $autoload_dev = (new AutoloadDev($phpunit_configuration, $DRUPAL_ROOT))
                    ->getAutoloadDev()['psr-4'] ?? [];
  $extra_psr4 += $autoload_dev;
  $bootstrap = new BootstrapCache($INSTALL_PATH, $extra_psr4);
  $output->writeln(sprintf('<info>Imported %d autoload-dev %s.</info>', count($autoload_dev), count($autoload_dev) === 1 ? 'namespace' : 'namespaces'));
  $output->writeln(array_keys($autoload_dev), OutputInterface::VERBOSITY_VERBOSE);
  $output->writeln('<info>Building cache; this takes a moment...</info>');
}

$bootstrap->require("$DRUPAL_ROOT/core/tests/bootstrap.php");
