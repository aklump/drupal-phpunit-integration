<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

use PHPUnit\Framework\TestCase;

final class PutEnvTest extends TestCase {

  private const KEY = 'AKLUMP_PUT_ENV_TEST';

  protected function tearDown(): void {
    putenv(self::KEY);
    unset($_ENV[self::KEY]);
  }

  public function testSetsRealEnvironmentVariable() {
    (new PutEnv())(self::KEY, 'bar');
    $this->assertSame('bar', getenv(self::KEY));
  }

  public function testSetsDollarEnvSuperglobalUnderTheGivenKey() {
    (new PutEnv())(self::KEY, 'bar');
    $this->assertArrayHasKey(self::KEY, $_ENV);
    $this->assertSame('bar', $_ENV[self::KEY]);
  }

  public function testReturnsTheValuePassedIn() {
    $this->assertSame('baz', (new PutEnv())(self::KEY, 'baz'));
  }

}
