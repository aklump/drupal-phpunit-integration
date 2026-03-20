#!/usr/bin/env php
<?php

/**
 * Installation script for Drupal PHPUnit Integration.
 *
 * This script automates the installation process described in the documentation.
 */

// Ensure script is run from the command line
if (PHP_SAPI !== 'cli') {
  echo "This script must be run from the command line.\n";
  exit(1);
}

define('APP_ROOT', getcwd());

const INSTALL_PATH = APP_ROOT . '/tests_phpunit';
if (!file_exists(INSTALL_PATH) || !is_dir(INSTALL_PATH)) {
  echo "The package has not yet been installed.\n";
  exit(1);
}

const DRUPAL_CORE = APP_ROOT . '/web/core';
if (!file_exists(DRUPAL_CORE) || !is_dir(DRUPAL_CORE)) {
  echo "Drupal is not installed at " . DRUPAL_CORE . ".\n";
  exit(1);
}

// Function to execute shell commands and display output
function execute_command($command, $display_output = TRUE) {
  echo "Executing: $command\n";
  $output = [];
  $return_var = 0;
  exec($command . " 2>&1", $output, $return_var);

  if ($display_output && !empty($output)) {
    echo implode("\n", $output) . "\n";
  }

  if ($return_var !== 0) {
    echo "Command failed with exit code $return_var\n";
    if (!$display_output && !empty($output)) {
      echo implode("\n", $output) . "\n";
    }

    return FALSE;
  }

  return $output;
}

// Function to modify phpunit.xml
function modify_phpunit_xml($file_path) {
  if (!file_exists($file_path)) {
    echo "Error: $file_path does not exist.\n";

    return FALSE;
  }

  $content = file_get_contents($file_path);
  if ($content === FALSE) {
    echo "Error: Could not read $file_path.\n";

    return FALSE;
  }

  // Replace bootstrap attribute
  $content = preg_replace('/bootstrap="[^"]*"/', 'bootstrap="./vendor/aklump/drupal-phpunit-integration/bootstrap.php"', $content);

  // Detect PHPUnit version
  $phpunit_version = '';
  if (file_exists('vendor/bin/phpunit')) {
    $version_output = execute_command('vendor/bin/phpunit --version', FALSE);
    if ($version_output) {
      $version_line = $version_output[0];
      if (preg_match('/PHPUnit (\d+)\./', $version_line, $matches)) {
        $phpunit_version = $matches[1];
      }
    }
  }

  // Add extension or bootstrap based on PHPUnit version
  if ($phpunit_version == '9') {
    echo "Detected PHPUnit 9, adding extension...\n";
    $extension_tag = '<extension class="AKlump\Drupal\PHPUnit\Integration\Runner\Extension\DynamicConfig"/>';
    $content = preg_replace('/<extensions>/', "<extensions>\n    $extension_tag", $content);
  }
  elseif ($phpunit_version == '10') {
    echo "Detected PHPUnit 10, adding bootstrap...\n";
    $bootstrap_tag = '<bootstrap class="AKlump\Drupal\PHPUnit\Integration\Runner\Extension\DynamicConfig"/>';
    $content = preg_replace('/<extensions>/', "<extensions>\n    $bootstrap_tag", $content);
  }
  else {
    echo "Could not detect PHPUnit version or unsupported version. You'll need to manually add the appropriate tag.\n";
    echo "For PHPUnit 9: <extension class=\"AKlump\\Drupal\\PHPUnit\\Integration\\Runner\\Extension\\DynamicConfig\"/>\n";
    echo "For PHPUnit 10: <bootstrap class=\"AKlump\\Drupal\\PHPUnit\\Integration\\Runner\\Extension\\DynamicConfig\"/>\n";
  }

  // Replace testsuite configuration
  $testsuite_pattern = '#(<testsuites.*?>).+?(</testsuites>)#s';
  $testsuite_replacement = '$1<testsuite name="integration"><directory>../web/modules/custom/my_module/tests/src</directory></testsuite>$2';
  $content = preg_replace($testsuite_pattern, $testsuite_replacement, $content);

  // Replace source configuration
  $source_pattern = '/(<source.*?>).*?(<\/source>)/s';
  $source_replacement = '$1<include><directory>../web/modules/custom/my_module/src</directory></include>$2';
  $content = preg_replace($source_pattern, $source_replacement, $content);

  // Write the modified content back to the file
  if (file_put_contents($file_path, $content) === FALSE) {
    echo "Error: Could not write to $file_path.\n";

    return FALSE;
  }

  echo "Successfully modified $file_path.\n";

  return TRUE;
}

echo "Starting configuration of Drupal PHPUnit Integration...\n\n";

// Step 2: Install Drupal Core Dev
echo "\n## Ensuring Drupal Core Dev is installed...\n";
echo "Checking Drupal core version...\n";

// Change to web directory
if (chdir(APP_ROOT)) {
  $core_dev_is_installed = execute_command('composer show drupal/core-dev', FALSE);
  if (!$core_dev_is_installed) {
    $drupal_version = execute_command('composer show | grep drupal/core-recommended', FALSE);
    if ($drupal_version && preg_match('#\d+\.\d+(\.\d+)?#', implode("\n", $drupal_version), $matches)) {
      $version = $matches[0];
      echo "Detected Drupal core version: $version\n";

      echo "Installing drupal/core-dev:^$version...\n";
      if (execute_command("composer require --dev drupal/core-dev:^$version") === FALSE) {
        echo "Failed to install drupal/core-dev. Please run the command manually:\n";
        echo "composer require --dev drupal/core-dev:^$version\n\n";
      }
    }
    else {
      echo "Could not detect Drupal core version. Please run the following commands manually:\n";
      echo "cd ../web\n";
      echo "composer show | grep drupal/core\n";
      echo "Note the Drupal version, e.g. 11.2.8\n";
      echo "composer require --dev drupal/core-dev:^11.2 (replace 11.2 with your version)\n\n";
    }
  }
}
else {
  echo "Could not change to web directory. Please run the following commands manually:\n";
  echo "cd ../web\n";
  echo "composer show | grep drupal/core\n";
  echo "Note the Drupal version, e.g. 11.2.8\n";
  echo "composer require --dev drupal/core-dev:^11.2 (replace 11.2 with your version)\n\n";
}

// Step 3: Configure the package
echo "\n## Configuring the package...\n";
chdir(INSTALL_PATH);

// Copy phpunit.xml.dist from Drupal core
echo "Copying phpunit.xml.dist from Drupal core...\n";
if (file_exists(DRUPAL_CORE . '/phpunit.xml.dist')) {
  if (copy(DRUPAL_CORE . '/phpunit.xml.dist', 'phpunit.xml')) {
    echo "Successfully copied phpunit.xml.dist to phpunit.xml\n";

    // Modify phpunit.xml
    echo "Modifying phpunit.xml...\n";
    modify_phpunit_xml('phpunit.xml');
  }
  else {
    echo "Failed to copy phpunit.xml.dist. Please run the command manually:\n";
    echo "cp ../web/core/phpunit.xml.dist phpunit.xml\n";
  }
}
else {
  echo "Could not find ../web/core/phpunit.xml.dist. Please run the command manually:\n";
  echo "cp ../web/core/phpunit.xml.dist phpunit.xml\n";
}

// Create test_output directory
echo "Creating test_output directory...\n";
if (!is_dir('test_output')) {
  if (!mkdir('test_output', 0755, TRUE)) {
    echo "Failed to create test_output directory.\n";
    exit(1);
  }
}

// Create the runner
$runner_template = glob(INSTALL_PATH . '/vendor/aklump/drupal-phpunit-integration/init/run_*.sh')[0] ?? NULL;
if (!$runner_template || !file_exists($runner_template)) {
  echo "\n## Runner script not found. Skipping runner creation.\n";
  exit(1);
}
$runner_path = APP_ROOT . '/bin/' . basename($runner_template);
if (!file_exists($runner_path)) {
  echo "\n## Creating runner script...\n";
  chdir(APP_ROOT);
  if (!file_exists(APP_ROOT . '/bin')) {
    if (!mkdir(APP_ROOT . '/bin', 0755, TRUE)) {
      echo "Failed to create bin directory.\n";
    }
  }

  if ($runner_template && !file_exists($runner_path)) {
    echo "Copying runner script to " . $runner_path . "\n";
    copy($runner_template, $runner_path);
  }
}

// Final step
echo "\n## Final step\n";
echo "Run the tests with:\n";
echo "bin/run_phpunit_tests.sh --flush\n\n";

echo "Installation completed successfully!\n";
