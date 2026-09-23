<?php

namespace AKlump\Drupal\PHPUnit\Integration\ThirdParty;

use PHPUnit\Framework\TestCase;

final class DrushServiceTest extends TestCase {

  public function testGetDatabaseUrlWithFullOutput() {
    $drush = new DrushService();
    $this->assertSame(
      'mysql://mysql:mysql@database_2:3306/database_2',
      $drush->getDatabaseUrl('mysql --user=mysql --password=mysql --database=database_2 --host=database_2 --port=3306 -A')
    );
  }

  public function testGetDatabaseUrlTrimsSurroundingWhitespaceAndNewline() {
    $drush = new DrushService();
    $this->assertSame(
      'mysql://mysql:mysql@database_2:3306/database_2',
      $drush->getDatabaseUrl("  mysql --user=mysql --password=mysql --database=database_2 --host=database_2 --port=3306 -A\n")
    );
  }

  public function testGetDatabaseUrlWithoutPortOmitsPort() {
    $drush = new DrushService();
    $this->assertSame(
      'mysql://mysql:mysql@database_2/database_2',
      $drush->getDatabaseUrl('mysql --user=mysql --password=mysql --database=database_2 --host=database_2 -A')
    );
  }

  public function testGetDatabaseUrlWithoutPasswordUsesEmptyPassword() {
    $drush = new DrushService();
    $this->assertSame(
      'mysql://mysql:@database_2:3306/database_2',
      $drush->getDatabaseUrl('mysql --user=mysql --database=database_2 --host=database_2 --port=3306 -A')
    );
  }

  public function testGetDatabaseUrlHandlesOptionsInAnyOrder() {
    $drush = new DrushService();
    $this->assertSame(
      'mysql://mysql:mysql@database_2:3306/database_2',
      $drush->getDatabaseUrl('mysql --port=3306 --database=database_2 --host=database_2 --password=mysql --user=mysql')
    );
  }

  /**
   * @dataProvider dataForTestGetDatabaseUrlReturnsEmptyStringProvider
   */
  public function testGetDatabaseUrlReturnsEmptyString(string $sql_connect_output) {
    $drush = new DrushService();
    $this->assertSame('', $drush->getDatabaseUrl($sql_connect_output));
  }

  public static function dataForTestGetDatabaseUrlReturnsEmptyStringProvider(): array {
    return [
      'empty output' => [''],
      'unsupported driver' => ['psql --username=pg --database=database_2 --host=database_2 --port=5432'],
      'missing user' => ['mysql --password=mysql --database=database_2 --host=database_2'],
      'missing host' => ['mysql --user=mysql --password=mysql --database=database_2'],
      'missing database' => ['mysql --user=mysql --password=mysql --host=database_2'],
    ];
  }

  public function testGetBaseUrlWithValidUri() {
    $drush = new DrushService();
    $this->assertSame('http://example.local.loft/', $drush->getBaseUrl('http://example.local.loft/'));
  }

  public function testGetBaseUrlTrimsSurroundingWhitespaceAndNewline() {
    $drush = new DrushService();
    $this->assertSame('https://example.com/', $drush->getBaseUrl("  https://example.com/\n"));
  }

  /**
   * @dataProvider dataForTestGetBaseUrlReturnsEmptyStringProvider
   */
  public function testGetBaseUrlReturnsEmptyString(string $status_uri_output) {
    $drush = new DrushService();
    $this->assertSame('', $drush->getBaseUrl($status_uri_output));
  }

  public static function dataForTestGetBaseUrlReturnsEmptyStringProvider(): array {
    return [
      'empty output' => [''],
      'unconfigured base_url placeholder' => ['http://default'],
      'unconfigured base_url placeholder, mixed case' => ['HTTP://DEFAULT'],
      'not a url' => ['default'],
    ];
  }

  public function testGetSqlConnectOutputCachesPerBaseDir() {
    $first = DrushService::getSqlConnectOutput(__DIR__);
    $second = DrushService::getSqlConnectOutput(__DIR__);
    $this->assertSame($first, $second);
    $this->assertIsString($first);
  }

  public function testGetSqlConnectOutputReturnsEmptyStringWhenDrushIsUnavailable() {
    $this->assertSame('', DrushService::getSqlConnectOutput('/path/does/not/exist/' . uniqid()));
  }

  public function testGetSqlConnectOutputHandlesBaseDirWithSpaces() {
    $dir = sys_get_temp_dir() . '/drush service test ' . uniqid();
    mkdir($dir);
    try {
      $this->assertSame('', DrushService::getSqlConnectOutput($dir));
    }
    finally {
      rmdir($dir);
    }
  }

  public function testGetStatusUriCachesPerBaseDir() {
    $first = DrushService::getStatusUri(__DIR__);
    $second = DrushService::getStatusUri(__DIR__);
    $this->assertSame($first, $second);
    $this->assertIsString($first);
  }

  public function testGetStatusUriReturnsEmptyStringWhenDrushIsUnavailable() {
    $this->assertSame('', DrushService::getStatusUri('/path/does/not/exist/' . uniqid()));
  }

}
