<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

class PutEnv {

  /**
   * Sets an environment variable with the given key and value.
   *
   * @param string $key The name of the environment variable.
   * @param string $value The value to set for the environment variable.
   *
   * @return string The value of $value as passed to the method.
   */
  public function __invoke(string $key, string $value): string {
    $_ENV['$key'] = $value;
    putenv($key . '=' . $value);

    return $value;
  }
}
