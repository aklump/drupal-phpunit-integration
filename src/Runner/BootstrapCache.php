<?php

namespace AKlump\Drupal\PHPUnit\Integration\Runner;

/**
 * A class to speed up PhpUnit testing in Drupal 8 when bootstrapping Drupal.
 */
final class BootstrapCache {

  /**
   * @var string
   */
  private $cachePath;

  /**
   * @var bool
   */
  private $saveCacheFile = TRUE;

  /**
   * @var array
   */
  private $addPsr4 = [];

  /**
   * @param string $cache_dir
   *   Path to the directory to use for caching.
   */
  public function __construct(string $cache_dir, array $extra_autoload_psr4 = []) {
    $this->cachePath = $cache_dir . '/.bootstrap.cache';
    $this->addPsr4 = $extra_autoload_psr4;
  }

  /**
   * Flush the cache.
   *
   * @return bool
   *   False if the file could not be deleted.
   */
  public function flush(): bool {
    if (file_exists($this->cachePath)) {
      return unlink($this->cachePath);
    }

    return TRUE;
  }

  /**
   * Require core/tests/bootstrap.php with extra caching.
   *
   * Use this to vastly speed up integration testing in Drupal 8.  The core bootstrap
   * file uses a recursive directory iterator, which slows down tests.  This
   * method will cache the results so that subsequent bootstraps will skip the
   * discovery phase.  This speeds things up very much.
   *
   * @param string $drupal_tests_bootstrap
   *   Absolute path to Drupal's test boostrap file e.g.,
   *   "web/core/tests/bootstrap.php"
   * @param array $extra_autoload_psr4
   *   Include an array as you might in composer.json, however the paths need to
   *   be absolute.
   *
   * @return \TestsPhpUnit\Runner\BootstrapCache
   */
  public function require(string $drupal_tests_bootstrap): void {
    $this->readFromCache();
    require_once $drupal_tests_bootstrap;
    if (isset($loader) && $this->addPsr4) {
      foreach ($this->addPsr4 as $ns => $paths) {
        $loader->addPsr4($ns, $paths);
      }
    }
    if ($this->saveCacheFile) {
      $result = file_put_contents($this->cachePath, json_encode($GLOBALS['namespaces']));
      if (!$result) {
        throw new \InvalidArgumentException(sprintf('Could not save cached autoloaders to %s', $this->cachePath));
      }
    }
  }

  private function readFromCache() {
    // Drupal will look for $GLOBALS['namespaces'], if it's already populated by
    // our local cache file, then Drupal will bypass it's scan mechanism which
    // saves a number of seconds depending on the size of the installation.
    if (file_exists($this->cachePath)) {
      $json = file_get_contents($this->cachePath);
      $ns = json_decode($json, TRUE);
      if (!empty($ns)) {
        $this->saveCacheFile = FALSE;
        $GLOBALS['namespaces'] = $ns;
      };
    }
  }

}
