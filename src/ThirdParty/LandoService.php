<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

/**
 * Provides functionality to interact with Lando environment services.
 */
class LandoService {

  private static array $cache;

  private array $landoInfo;

  /**
   * @param array $landoInfo Pass the lando info to be parsed. Such as coming
   *   from self::$landoInfo.
   */
  public function __construct(array $landoInfo) {
    $this->landoInfo = $landoInfo;
  }

  /**
   * Builds a database URL from Lando service connection information.
   *
   * When PHPUnit runs inside Lando, the internal service connection is used
   * because service hostnames such as "database" are resolvable from within
   * the
   * Lando network. When PHPUnit runs from the host machine, the external
   * connection is required because internal service hostnames are not
   * resolvable outside Lando.
   *
   * @return string
   *   A database URL suitable for SIMPLETEST_DB, or an empty string when no
   *   database service is present in the Lando service information.
   *
   * @throws \RuntimeException
   *   Thrown when a database service is present, but the connection
   *   information
   *   required for the current runtime context is missing.
   */
  public function getDatabaseUrl(): string {
    $isRunningInLando = getenv('LANDO') === 'ON';
    $connectionKey = $isRunningInLando ? 'internal_connection' : 'external_connection';
    $landoInfo = $this->landoInfo;

    while ($service = array_shift($landoInfo)) {
      if (!isset($service['creds']['database'])) {
        continue;
      }

      if (empty($service[$connectionKey]['host'])) {
        throw new \RuntimeException(sprintf(
          'Lando database service was found, but "%s" is missing. Cannot build PHPUnit database URL while running %s Lando.',
          $connectionKey,
          $isRunningInLando ? 'inside' : 'outside'
        ));
      }

      $port = $service[$connectionKey]['port'] ?? '';

      return sprintf('mysql://%s:%s@%s%s/%s',
        $service['creds']['user'],
        $service['creds']['password'],
        $service[$connectionKey]['host'],
        $port !== '' ? ':' . $port : '',
        $service['creds']['database'],
      );
    }

    return '';
  }

  /**
   * @return string  The base URL of the lando application web server.
   */
  public function getBaseUrl(): string {
    $appserver = array_values(array_filter($this->landoInfo, function($service) {
      return 'appserver' === $service['service'];
    }))[0] ?? [];
    $appserver += ['urls' => []];

    return array_values(array_filter(($appserver['urls']), function($url) {
      return strstr($url, 'localhost') === FALSE && strstr($url, 'http') !== FALSE;
    }))[0] ?? '';
  }

  /**
   * Retrieves Lando information for the current working directory.
   *
   * Multiple calls are performant as the data is cached per directory path.
   *
   * @return array Returns an array containing Lando information for current
   *   working directory
   */
  public static function getLandoInfo(): array {
    static::$cache['context'] = getcwd();
    if (!isset(static::$cache['info'][static::$cache['context']])) {
      $json = exec('lando info --format=json 2>/dev/null');
      static::$cache['info'][static::$cache['context']] = json_decode($json, TRUE) ?? [];
    }

    return static::$cache['info'][static::$cache['context']];
  }

}
