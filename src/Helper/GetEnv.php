<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

class GetEnv {

  /**
   * Retrieves the value of an environment variable by its key.
   *
   * @param string $key The key of the environment variable to retrieve.
   *
   * @return string|null The value of the environment variable if it exists, or null if it does not.
   */
  public function __invoke(string $key): ?string {
    if (isset($_ENV[$key])) {
      return (string) $_ENV[$key];
    }
    if (getenv($key)) {
      return (string) getenv($key);
    }

    return NULL;
  }
}
