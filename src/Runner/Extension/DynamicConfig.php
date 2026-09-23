<?php declare(strict_types=1);

namespace AKlump\Drupal\PHPUnit\Integration\Runner\Extension;

use AKlump\Drupal\PHPUnit\Integration\Helper\GetEnv;
use AKlump\Drupal\PHPUnit\Integration\Helper\GetUserHelpForMissingSimpleTestDB;
use AKlump\Drupal\PHPUnit\Integration\Helper\PutEnv;
use AKlump\Drupal\PHPUnit\Integration\ThirdParty\DrushService;
use AKlump\Drupal\PHPUnit\Integration\ThirdParty\GitService;
use AKlump\Drupal\PHPUnit\Integration\ThirdParty\LandoService;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;

/**
 * This class is included in the phpunit.xml file as an extension.
 */
final class DynamicConfig implements Extension {

  /** @var string */
  const OUTPUT_DIRECTORY_NAME = 'test_output';

  public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void {
    // Setup some environment variables that will be used by Drupal.  These
    // might normally be hard-coded in phpunit.xml, but we will make them
    // dynamic and set them here to make configuration easier.
    $putenv = new PutEnv();
    $db = $putenv('SIMPLETEST_DB', $this->getSimpletestDb());
    if (!$db) {
      $hint = (new GetUserHelpForMissingSimpleTestDB())();
      throw new RuntimeException($hint);
    }
    $putenv('SIMPLETEST_BASE_URL', $this->getSimpletestBaseUrl());
    $putenv('BROWSERTEST_OUTPUT_DIRECTORY', $this->getBrowserTestOutputDirectory());
  }

  /**
   * Get the value for SIMPLETEST_DB.
   *
   * @return string
   *   The database configuration as an URL string.
   */
  private function getSimpletestDb(): string {
    $get_env = new GetEnv();
    $DRUPAL_ROOT = $get_env('DRUPAL_ROOT');

    // Respect a value the user has already configured, e.g. directly in
    // phpunit.xml or their shell/CI environment. Without this, it would be
    // silently overwritten below.
    $value = $get_env('SIMPLETEST_DB');

    if (!$value) {
      $value = $get_env('DATABASE_URL');
      $branch_name = (new GitService($DRUPAL_ROOT))->getBranchName();
      if ($branch_name) {
        $branch_key = 'DATABASE_URL__' . strtoupper($branch_name);
        $db_based_value = $get_env($branch_key);
        $value = $db_based_value ?: $value;
      }
    }
    if (!$value
      && ($lando_info = LandoService::getLandoInfo())) {
      $value = (new LandoService($lando_info))->getDatabaseUrl();
    }
    if (!$value
      && ($sql_connect_output = DrushService::getSqlConnectOutput((string) $DRUPAL_ROOT))) {
      $value = (new DrushService())->getDatabaseUrl($sql_connect_output);
    }

    return strval($value);
  }

  /**
   * Get the value for SIMPLETEST_BASE_URL.
   *
   * @return string
   *   The base URL of the site under test.
   */
  private function getSimpletestBaseUrl(): string {
    $get_env = new GetEnv();
    // Respect a value the user has already configured, e.g. directly in
    // phpunit.xml or their shell/CI environment, per the example given in
    // phpunit.xml. Without this, it would be silently overwritten below.
    $value = $get_env('SIMPLETEST_BASE_URL');
    $DRUPAL_ROOT = $get_env('DRUPAL_ROOT');

    if (!$value
      && ($lando_info = LandoService::getLandoInfo())) {
      $value = (new LandoService($lando_info))->getBaseUrl();
    }
    if (!$value
      && ($status_uri = DrushService::getStatusUri((string) $DRUPAL_ROOT))) {
      $value = (new DrushService())->getBaseUrl($status_uri);
    }

    return strval($value);
  }

  /**
   * @return string The directory where browser test results (e.g., screenshots, page dumps, logs, or other debug information) are saved
   */
  private function getBrowserTestOutputDirectory() {
    $getenv = new GetEnv();
    if ($value = $getenv('BROWSERTEST_OUTPUT_DIRECTORY')) {
      return $value;
    }

    return $getenv('INSTALL_PATH') . '/' . trim(self::OUTPUT_DIRECTORY_NAME, '/') . '/';
  }

}
