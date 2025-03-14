<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

class LandoService {

  private static array $cache;

  private array $landoInfo;

  /**
   * @param array $lando_info Pass the lando info to be parsed. Such as coming from self::$landoInfo.
   */
  public function __construct(array $lando_info) {
    $this->landoInfo = $lando_info;
  }

  /**
   * @return string The database connection for the Lando app.
   */
  public function getDatabaseUrl(): string {
    $lando_info = $this->landoInfo;
    while ($service = array_shift($lando_info)) {
      if (isset($service['creds']['database'])) {
        return sprintf('mysql://%s:%s@%s%s/%s',
          $service['creds']['user'],
          $service['creds']['password'],
          $service['internal_connection']['host'],
          ltrim(':' . ($service['internal_connection']['port'] ?? '')),
          $service['creds']['database'],
        );
      }
    }

    return '';
  }

  /**
   * @return string  The base URL of the lando application web server.
   */
  public function getBaseUrl(): string {
    $appserver = array_values(array_filter($this->landoInfo, function ($service) {
      return 'appserver' === $service['service'];
    }))[0] ?? [];
    $appserver += ['urls' => []];

    return array_values(array_filter(($appserver['urls']), function ($url) {
      return strstr($url, 'localhost') === FALSE && strstr($url, 'http') !== FALSE;
    }))[0] ?? '';
  }

  /**
   * Retrieves Lando information for the current working directory.
   *
   * Multiple calls are performant as the data is cached per directory path.
   *
   * @return array Returns an array containing Lando information for current working directory
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
