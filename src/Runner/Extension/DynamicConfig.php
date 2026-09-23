<?php declare(strict_types=1);

namespace AKlump\Drupal\PHPUnit\Integration\Runner\Extension;

use AKlump\Drupal\PHPUnit\Integration\Helper\GetEnv;
use AKlump\Drupal\PHPUnit\Integration\Helper\GetUserHelpForMissingSimpleTestDB;
use AKlump\Drupal\PHPUnit\Integration\Helper\PutEnv;
use AKlump\Drupal\PHPUnit\Integration\ThirdParty\DrushService;
use AKlump\Drupal\PHPUnit\Integration\ThirdParty\GitService;
use AKlump\Drupal\PHPUnit\Integration\ThirdParty\LandoService;
use PHPUnit\Runner\BeforeFirstTestHook;
use RuntimeException;

/**
 * This class is included in the phpunit.xml file as an extension.
 */
final class DynamicConfig implements BeforeFirstTestHook {

  /** @var string */
  const OUTPUT_DIRECTORY_NAME = 'test_output';

  public function executeBeforeFirstTest(): void {

    // Setup some environment variables that will be used by Drupal.  These
    // might normally be hard-coded in phpunit.xml, but we will make them
    // dynamic and set them here to make configuration easier.
    $putenv = new PutEnv();
    $db = $putenv('SIMPLETEST_DB', $this->getSimpletestDb());
    if (!$db) {
      $phpunit_xml_path = (new GetEnv())('INSTALL_PATH') . '/phpunit.xml';
      if (!file_exists($phpunit_xml_path)) {
        $phpunit_xml_path = 'INSTALL_PATH/phpunit.xml';
      }
      $hint = (new GetUserHelpForMissingSimpleTestDB())($phpunit_xml_path);
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

    // This will take first precedence
    // @see drupal::/web/core/tests/README.md
    $simpletest_db = (string) $get_env('SIMPLETEST_DB');
    if ($simpletest_db) {
      return $simpletest_db;
    }

    // Otherwise, try to get the database URL from the environment/git branch
    $value = $get_env('DATABASE_URL');
    $DRUPAL_ROOT = $get_env('DRUPAL_ROOT');
    $branch_name = (new GitService($DRUPAL_ROOT))->getBranchName();
    if ($branch_name) {
      $branch_key = 'DATABASE_URL__' . strtoupper($branch_name);
      $db_based_value = $get_env($branch_key);
      $value = $db_based_value ?: $value;
    }

    // Or, if Lando is running, try to get the database URL from Lando.
    if (!$value
      && ($lando_info = LandoService::getLandoInfo())) {
      $value = (new LandoService($lando_info))->getDatabaseUrl();
    }

    // Finally, fallback to a database URL resolved via Drush.
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

    // This will take first precedence, e.g. hard-coded in phpunit.xml or the
    // shell/CI environment. Without this, it would be silently overwritten
    // below.
    $value = $get_env('SIMPLETEST_BASE_URL');
    $DRUPAL_ROOT = $get_env('DRUPAL_ROOT');

    // Or, if Lando is running, try to get the base URL from Lando.
    if (!$value
      && ($lando_info = LandoService::getLandoInfo())) {
      $value = (new LandoService($lando_info))->getBaseUrl();
    }

    // Finally, fallback to a base URL resolved via Drush.
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
