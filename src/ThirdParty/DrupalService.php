<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

use AKlump\Drupal\PHPUnit\Integration\Helper\DrupalSettingsResolver;

/**
 * A class to interact with Drupal.
 */
class DrupalService {

  protected string $drupalRoot;

  public function __construct(string $path_to_drupal_root) {
    $this->drupalRoot = $path_to_drupal_root;
  }

  /**
   * Get the database url for the default database from Drupal
   */
  public function getDatabaseUrl(): string {
    $settings_paths = glob("$this->drupalRoot/sites/default/*settings*.php");
    $resolver = new DrupalSettingsResolver();
    foreach ($settings_paths as $path) {
      $databases = $resolver->resolve($path);
      if (empty($databases)) {
        continue;
      }
      $database = $databases['default'] ?? reset($databases);
      if (!empty($database['default'])) {
        $db = $database['default'];
        $url = sprintf(
          '%s://%s:%s@%s/%s',
          $db['driver'] ?? 'mysql',
          $db['username'] ?? '',
          $db['password'] ?? '',
          $db['host'] ?? 'localhost',
          $db['database'] ?? ''
        );
        if (!empty($db['prefix'])) {
          $url .= '#' . (is_array($db['prefix']) ? ($db['prefix']['default'] ?? '') : $db['prefix']);
        }

        return $url;
      }
    }

    return '';
  }
}
