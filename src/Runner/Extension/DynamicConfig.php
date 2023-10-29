<?php declare(strict_types=1);

namespace AKlump\Drupal\PHPUnit\Integration\Runner\Extension;

use PHPUnit\Runner\BeforeFirstTestHook;

/**
 * This class is included in the phpunit.xml file as an extension
 */
final class DynamicConfig implements BeforeFirstTestHook {

  public function executeBeforeFirstTest(): void {

    // Setup some environment variables that will be used by Drupal.  These
    // might normally be hard-coded in phpunit.xml, but we will make them
    // dynamic and set them here to make configuration easier.
    $_ENV['SIMPLETEST_DB'] = $this->getSimpletestDb();
    $_ENV['SIMPLETEST_BASE_URL'] = $this->getSimpletestBaseUrl();
    $_ENV['BROWSERTEST_OUTPUT_DIRECTORY'] = $_ENV['DRUPAL_ROOT'] . '/sites/simpletest/browser_output';
  }

  /**
   * Get the value for SIMPLETEST_DB.
   *
   * @return string
   *   The database configuration as an URL string.
   */
  private function getSimpletestDb(): string {
    $git_branch = exec('cd ' . __DIR__ . ' && git rev-parse --abbrev-ref HEAD 2>/dev/null', $git);
    $key = 'DATABASE_URL';
    if ($git_branch) {
      $key = 'DATABASE_URL__' . strtoupper($git_branch);
    }

    return $_ENV[$key] ?? $_ENV['DATABASE_URL'];
  }

  private function getSimpletestBaseUrl(): string {
    $json = exec('lando info --format=json 2>/dev/null');
    $data = json_decode($json, TRUE) ?? [];

    $appserver = array_values(array_filter($data, function ($service) {
      return 'appserver' === $service['service'];
    }))[0] ?? [];
    $appserver += ['urls' => []];

    return array_values(array_filter(($appserver['urls']), function ($url) {
      return strstr($url, 'localhost') === FALSE && strstr($url, 'http') !== FALSE;
    }))[0] ?? '';
  }

}
