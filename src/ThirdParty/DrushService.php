<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

/**
 * Provides functionality to resolve Drupal test configuration using Drush.
 */
class DrushService {

  private static array $cache;

  /**
   * Builds a database URL from `drush sql:connect --show-passwords` output.
   *
   * Example input:
   * mysql --user=mysql --password=mysql --database=database_2 --host=database_2 --port=3306 -A
   *
   * @param string $sql_connect_output
   *   The output of `drush sql:connect --show-passwords`, such as coming
   *   from self::getSqlConnectOutput().
   *
   * @return string
   *   A database URL suitable for SIMPLETEST_DB, or an empty string when the
   *   output cannot be parsed, e.g. because Drush is unavailable or the
   *   database driver is not supported.
   */
  public function getDatabaseUrl(string $sql_connect_output): string {
    $command = trim($sql_connect_output);
    if (!preg_match('/^mysql\s+(.+)$/', $command, $matches)) {
      return '';
    }

    $options = [];
    foreach (preg_split('/\s+/', trim($matches[1])) as $part) {
      if (preg_match('/^--([a-z_]+)=(.*)$/', $part, $option)) {
        $options[$option[1]] = $option[2];
      }
    }

    if (empty($options['user']) || empty($options['host']) || empty($options['database'])) {
      return '';
    }

    $port = $options['port'] ?? '';

    return sprintf('mysql://%s:%s@%s%s/%s',
      $options['user'],
      $options['password'] ?? '',
      $options['host'],
      $port !== '' ? ':' . $port : '',
      $options['database'],
    );
  }

  /**
   * Determines a site's base URL from `drush status --field=uri` output.
   *
   * Drush is invoked outside of an HTTP request context, so a site that has
   * no explicit $base_url configured (the common case for local development)
   * reports the placeholder "http://default", which is not a real, browsable
   * URL. That placeholder, and anything else that isn't a URL, is treated the
   * same as "unknown".
   *
   * @param string $status_uri_output
   *   The output of `drush status --field=uri`, such as coming from
   *   self::getStatusUri().
   *
   * @return string
   *   A base URL suitable for SIMPLETEST_BASE_URL, or an empty string when
   *   one could not be determined.
   */
  public function getBaseUrl(string $status_uri_output): string {
    $uri = trim($status_uri_output);
    if ('' === $uri || 0 === strcasecmp($uri, 'http://default')) {
      return '';
    }
    if (!preg_match('#^https?://#i', $uri)) {
      return '';
    }

    return $uri;
  }

  /**
   * Retrieves the raw `drush sql:connect --show-passwords` output.
   *
   * Multiple calls are performant as the output is cached per directory path.
   *
   * @param string $base_dir
   *   The Drupal root, used as the working directory when invoking Drush.
   *
   * @return string
   *   The raw command output, or an empty string on failure.
   */
  public static function getSqlConnectOutput(string $base_dir): string {
    static::$cache['context'] = $base_dir;
    if (!isset(static::$cache['sql_connect'][static::$cache['context']])) {
      static::$cache['sql_connect'][static::$cache['context']] = trim((string) exec('cd ' . escapeshellarg($base_dir) . ' && drush sql:connect --show-passwords 2>/dev/null'));
    }

    return static::$cache['sql_connect'][static::$cache['context']];
  }

  /**
   * Retrieves the raw `drush status --field=uri` output.
   *
   * Multiple calls are performant as the output is cached per directory path.
   *
   * @param string $base_dir
   *   The Drupal root, used as the working directory when invoking Drush.
   *
   * @return string
   *   The raw command output, or an empty string on failure.
   */
  public static function getStatusUri(string $base_dir): string {
    static::$cache['context'] = $base_dir;
    if (!isset(static::$cache['status_uri'][static::$cache['context']])) {
      static::$cache['status_uri'][static::$cache['context']] = trim((string) exec('cd ' . escapeshellarg($base_dir) . ' && drush status --field=uri 2>/dev/null'));
    }

    return static::$cache['status_uri'][static::$cache['context']];
  }

}
