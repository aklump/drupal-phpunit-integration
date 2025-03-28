<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

class GitService {

  private static array $cache;

  private string $baseDir;

  public function __construct(string $base_dir) {
    $this->baseDir = $base_dir;
  }

  /**
   * Retrieves the current Git branch name of the repository.
   *
   * @return string The name of the current Git branch.
   */
  public function getBranchName(): string {
    static::$cache['context'] = $this->baseDir;
    if (!isset(static::$cache['branch'][static::$cache['context']])) {
      static::$cache['branch'][static::$cache['context']] = exec('cd ' . $this->baseDir . ' && git rev-parse --abbrev-ref HEAD 2>/dev/null');
    }

    return static::$cache['branch'][static::$cache['context']];
  }
}
