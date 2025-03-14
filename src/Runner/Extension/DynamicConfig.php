<?php declare(strict_types=1);

namespace AKlump\Drupal\PHPUnit\Integration\Runner\Extension;

use AKlump\Drupal\PHPUnit\Integration\Helper\GetEnv;
use AKlump\Drupal\PHPUnit\Integration\Helper\GetUserHelpForMissingSimpleTestDB;
use AKlump\Drupal\PHPUnit\Integration\Helper\PutEnv;
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
    $git_branch = exec('cd ' . __DIR__ . ' && git rev-parse --abbrev-ref HEAD 2>/dev/null');
    $key = 'DATABASE_URL';
    $get_env = new GetEnv();
    $value = $get_env($key);
    if ($git_branch) {
      $branch_key = 'DATABASE_URL__' . strtoupper($git_branch);
      $db_based_value = $get_env($branch_key);
      $value = $db_based_value ?: $value;
    }
    if (!$value
      && ($lando_info = LandoService::getLandoInfo())) {
      $value = (new LandoService($lando_info))->getDatabaseUrl();
    }

    return strval($value);
  }

  private function getSimpletestBaseUrl(): string {
    // TODO Solve for when no lando.
    $lando_info = LandoService::getLandoInfo();

    return (new LandoService($lando_info))->getBaseUrl();
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
