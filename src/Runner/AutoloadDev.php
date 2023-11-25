<?php

namespace AKlump\Drupal\PHPUnit\Integration\Runner;

/**
 * Given phpunit.xml get all the autoload-dev values by testsuites.
 */
class AutoloadDev {

  /**
   * @var string
   */
  private $baseDir;

  /**
   * @var string
   */
  private $pathToPhpUnit;

  /**
   * @param string $path_to_integration_phpunit
   *   Filepath to the phpunit.xml file to read from.
   * @param string $base_dir
   *   (Optional) All returned paths will be made relative to this.
   */
  public function __construct(string $path_to_integration_phpunit, string $base_dir) {
    if (!file_exists($path_to_integration_phpunit)) {
      throw new \InvalidArgumentException(sprintf('$path_to_integration_phpunit does not exist: %s', $path_to_integration_phpunit));
    }
    $this->pathToPhpUnit = $path_to_integration_phpunit;
    $this->baseDir = realpath($base_dir);
  }

  /**
   * @return array
   *   A merged array of all autoload-dev values from the testsuite packages.
   */
  public function getAutoloadDev(): array {
    $result = [];
    foreach ($this->getTestSuiteDirectories() as $directory) {
      $result = array_merge_recursive($result, $this->getAutoloadDevByPackageDirectory($directory));
    }

    return $this->removeDuplicates($result);
  }

  private function getAutoloadDevByPackageDirectory($path_to_package): array {
    $base = $path_to_package;
    do {
      $composer = "$base/composer.json";
      $base = dirname($base);
      $found = file_exists($composer);
    } while (!$found && $base && $base !== $this->baseDir);

    if (!$found) {
      return [];
    }

    $data = json_decode(file_get_contents($composer), TRUE);
    if (empty($data['autoload-dev'])) {
      return [];
    }

    foreach ($data['autoload-dev'] as $key => $values) {
      foreach ($values as &$value) {
        $value = $this->normalizePaths($value, dirname($composer));
        if ($this->baseDir) {
          foreach ($value as &$item) {
            $item = $this->makeRelativeIfPossible($item, $this->baseDir);
          }
        }
      }
      $data['autoload-dev'][$key] = $values;
    }

    return $data['autoload-dev'] ?? [];
  }

  /**
   * Make $value an array of absolute paths.
   *
   * @param $value
   * @param string $base_dir
   *
   * @return array
   *
   */
  private function normalizePaths($value, string $base_dir): array {
    if (is_string($value)) {
      $value = [$value];
    }

    return array_map(function ($path) use ($base_dir) {
      if (!$this->pathIsAbsolute($path)) {
        $path = $this->pathMakeAbsolute($path, $base_dir);
      }

      $real = realpath($path);

      return $real ?: $path;

    }, $value);
  }

  private function getTestSuiteDirectories(): array {
    $phpunit = simplexml_load_file($this->pathToPhpUnit);
    $directories = [];
    foreach ($phpunit->testsuites as $testsuite) {
      foreach ($testsuite->testsuite as $directory) {
        foreach ($directory->directory as $item) {
          $item = (string) $item;
          if (!$this->pathIsAbsolute($item)) {
            $item = dirname($this->pathToPhpUnit) . "/$item";
          }
          $directories[] = $item;
        }
      }
    }

    return $directories;
  }

  private function pathIsAbsolute(string $path): bool {
    return substr($path, 0, 1) === '/';
  }

  private function pathMakeAbsolute(string $path, string $base_dir): string {
    $path = preg_replace('#^./#', '', $path);

    return rtrim($base_dir, '/') . '/' . $path;
  }

  private function makeRelativeIfPossible(string $path, string $base_dir): string {
    if (strpos($path, $base_dir) !== 0) {
      return $path;
    }

    return ltrim(substr($path, strlen($base_dir)), '/');
  }

  private function removeDuplicates(array $autoload_dev): array {
    if (isset($autoload_dev['files'])) {
      foreach ($autoload_dev['files'] as $key => $value) {
        $autoload_dev['files'][$key] = $this->bubbleUpStrings($value);
      }
      $autoload_dev['files'] = array_unique($autoload_dev['files']);
    }

    if (isset($autoload_dev['psr-4'])) {
      foreach ($autoload_dev['psr-4'] as &$stack) {
        $stack = array_unique($stack);
      }
    }

    return $autoload_dev;
  }

  private function bubbleUpStrings($value, &$path = NULL) {
    if (is_string($value)) {
      $path = $value;
    }
    else {
      foreach ($value as $item) {
        $this->bubbleUpStrings($item, $path);
      }
    }

    return $path;
  }
}
