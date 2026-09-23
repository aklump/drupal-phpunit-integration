<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

use PHPUnit\Framework\TestCase;

final class GetEnvTest extends TestCase {

  private const KEY = 'AKLUMP_GET_ENV_TEST';

  protected function tearDown(): void {
    putenv(self::KEY);
    unset($_ENV[self::KEY]);
  }

  public function testReturnsNullWhenNotSet() {
    $this->assertNull((new GetEnv())(self::KEY));
  }

  public function testReturnsValueFromDollarEnvSuperglobal() {
    $_ENV[self::KEY] = 'from-env-array';
    $this->assertSame('from-env-array', (new GetEnv())(self::KEY));
  }

  public function testReturnsValueFromRealEnvironmentVariable() {
    putenv(self::KEY . '=from-getenv');
    $this->assertSame('from-getenv', (new GetEnv())(self::KEY));
  }

  public function testReturnsNullWhenSetToEmptyString() {
    putenv(self::KEY . '=');
    $this->assertNull((new GetEnv())(self::KEY));
  }

}
