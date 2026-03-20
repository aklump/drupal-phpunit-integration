<?php

namespace AKlump\Drupal\PHPUnit\Integration\Tests\Helper\DrupalSettings;

use AKlump\Drupal\PHPUnit\Integration\Helper\DrupalSettingsResolver;
use PHPUnit\Framework\TestCase;

class DrupalSettingsResolverTest extends TestCase {

  private $tempDir;

  protected function setUp(): void {
    $this->tempDir = sys_get_temp_dir() . '/drupal_settings_test_' . uniqid();
    mkdir($this->tempDir);
  }

  protected function tearDown(): void {
    $this->removeDir($this->tempDir);
  }

  private function removeDir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir . "/" . $object)) {
            $this->removeDir($dir . "/" . $object);
          } else {
            unlink($dir . "/" . $object);
          }
        }
      }
      rmdir($dir);
    }
  }

  public function testSimpleAssignment() {
    $settingsFile = $this->tempDir . '/settings.php';
    file_put_contents($settingsFile, "<?php \$databases['default']['default'] = ['database' => 'drupal', 'username' => 'user', 'password' => 'pass', 'host' => 'localhost', 'driver' => 'mysql'];");

    $resolver = new DrupalSettingsResolver();
    $databases = $resolver->resolve($settingsFile);

    $expected = [
      'default' => [
        'default' => [
          'database' => 'drupal',
          'username' => 'user',
          'password' => 'pass',
          'host' => 'localhost',
          'driver' => 'mysql',
        ],
      ],
    ];
    $this->assertEquals($expected, $databases);
  }

  public function testIncludeOrder() {
    $settingsFile = $this->tempDir . '/settings.php';
    $localSettingsFile = $this->tempDir . '/settings.local.php';

    file_put_contents($settingsFile, "<?php 
\$databases['default']['default'] = ['database' => 'drupal'];
include __DIR__ . '/settings.local.php';
");
    file_put_contents($localSettingsFile, "<?php 
\$databases['default']['default'] = ['database' => 'local_drupal'];
");

    $resolver = new DrupalSettingsResolver();
    $databases = $resolver->resolve($settingsFile);

    $this->assertEquals('local_drupal', $databases['default']['default']['database']);
  }

  public function testCircularInclude() {
    $fileA = $this->tempDir . '/a.php';
    $fileB = $this->tempDir . '/b.php';

    file_put_contents($fileA, "<?php 
\$databases['default']['default'] = ['database' => 'a'];
include __DIR__ . '/b.php';
");
    file_put_contents($fileB, "<?php 
\$databases['default']['default'] = ['database' => 'b'];
include __DIR__ . '/a.php';
");

    $resolver = new DrupalSettingsResolver();
    $databases = $resolver->resolve($fileA);

    // It should not hang and return the last value before recursion stopped
    $this->assertEquals('b', $databases['default']['default']['database']);
  }

  public function testNestedIncludes() {
    $file1 = $this->tempDir . '/settings.php';
    $file2 = $this->tempDir . '/inc1.php';
    $file3 = $this->tempDir . '/inc2.php';

    file_put_contents($file1, "<?php include 'inc1.php';");
    file_put_contents($file2, "<?php include 'inc2.php';");
    file_put_contents($file3, "<?php \$databases['default']['default'] = ['database' => 'nested'];");

    $resolver = new DrupalSettingsResolver();
    $databases = $resolver->resolve($file1);

    $this->assertEquals('nested', $databases['default']['default']['database']);
  }
}
