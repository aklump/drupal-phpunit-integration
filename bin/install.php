#!/usr/bin/env php
<?php

/**
 * Installer for Drupal PHPUnit Integration.
 *
 * Self-contained: it bootstraps tests_phpunit/, requires this package with
 * Composer, then configures it. It does not depend on the package being on
 * disk, so it can be piped straight into PHP:
 *
 *   curl -sSL .../bin/install.php | php
 *
 * Set the DRUPAL_PHPUNIT_INTEGRATION_VERSION environment variable to choose the Composer constraint.
 * It goes on the php side of the pipe, e.g.:
 *
 *   curl -sSL .../bin/install.php | DRUPAL_PHPUNIT_INTEGRATION_VERSION='^9' php
 *
 * Other settings, each absolute or relative to the directory you run it from:
 *
 *   DRUPAL_PHPUNIT_INTEGRATION_INSTALL_PATH  Where to install; default tests_phpunit
 *   DRUPAL_PHPUNIT_INTEGRATION_DRUPAL_CORE   Drupal core directory; default web/core
 *
 * Run it from the Drupal app root (the directory containing web/).
 */

// Ensure script is run from the command line
if (PHP_SAPI !== 'cli') {
  echo "This script must be run from the command line.\n";
  exit(1);
}

define('APP_ROOT', getcwd());
const PACKAGE = 'aklump/drupal-phpunit-integration';

/**
 * Resolve a path setting from the environment to an absolute path.
 *
 * @param string $name
 *   The environment variable name.
 * @param string $default
 *   The default, relative to APP_ROOT.
 */
function path_setting($name, $default) {
  $path = getenv($name);
  $path = $path === FALSE || trim($path) === '' ? $default : trim($path);
  if ($path[0] !== '/') {
    $path = APP_ROOT . '/' . $path;
  }

  return rtrim($path, '/');
}

/**
 * Get the relative path from one absolute directory to another.
 */
function relative_path($from, $to) {
  $from = explode('/', trim($from, '/'));
  $to = explode('/', trim($to, '/'));
  while ($from && $to && $from[0] === $to[0]) {
    array_shift($from);
    array_shift($to);
  }
  $parts = array_merge(array_fill(0, count($from), '..'), $to);

  return $parts ? implode('/', $parts) : '.';
}

/**
 * Get a path as shown to the user: relative to APP_ROOT when inside it.
 */
function display_path($absolute) {
  return strpos($absolute . '/', APP_ROOT . '/') === 0 ? relative_path(APP_ROOT, $absolute) : $absolute;
}

define('INSTALL_PATH', path_setting('DRUPAL_PHPUNIT_INTEGRATION_INSTALL_PATH', 'tests_phpunit'));
define('DRUPAL_CORE', path_setting('DRUPAL_PHPUNIT_INTEGRATION_DRUPAL_CORE', 'web/core'));
// The Drupal web root is the parent of core.
define('DRUPAL_ROOT', dirname(DRUPAL_CORE));
define('INSTALL_LABEL', display_path(INSTALL_PATH));
define('CORE_LABEL', display_path(DRUPAL_CORE));

// Set once this run creates INSTALL_PATH, so fail() knows a partial install exists.
$created_install_path = FALSE;

/**
 * Print a loud failure banner and exit non-zero.
 *
 * @param string $message
 *   What went wrong.
 * @param string ...$fixes
 *   How the user can fix it.
 */
function fail($message, ...$fixes) {
  global $created_install_path;
  $tty = function_exists('posix_isatty') && @posix_isatty(STDERR);
  $red = $tty ? "\033[1;97;41m" : '';
  $reset = $tty ? "\033[0m" : '';
  $bar = str_repeat('#', 60);
  $title = '##  INSTALLATION FAILED';
  $title = str_pad($title, 58) . '##';

  $out = "\n$red$bar$reset\n$red$title$reset\n$red$bar$reset\n\n";
  $out .= "❌️ $message\n";
  if ($fixes) {
    $out .= "\nHow to fix:\n";
    foreach ($fixes as $fix) {
      $out .= "  - $fix\n";
    }
  }
  $out .= "\n" . ($created_install_path
      ? "The installation is INCOMPLETE. Do not use " . INSTALL_LABEL . " as-is.\n"
      : "Nothing was installed.\n");
  fwrite(STDERR, $out . "\n");
  exit(1);
}

/**
 * Whether an executable is on the PATH.
 */
function command_exists($name) {
  exec('command -v ' . escapeshellarg($name) . ' 2>&1', $output, $status);

  return $status === 0;
}

// Preflight: nothing is created until every check passes.
if (file_exists(INSTALL_PATH)) {
  fail(INSTALL_LABEL . ' is already installed.', 'Delete it (rm -r ' . INSTALL_LABEL . ') to reinstall, or edit the existing install.');
}
if (!is_writable(APP_ROOT)) {
  fail('The current directory is not writable.', 'Run from a directory you can write to: your Drupal app root.');
}
if (!is_dir(DRUPAL_CORE)) {
  fail('Drupal not found at ' . CORE_LABEL . ' (cwd: ' . APP_ROOT . ').', 'cd to your Drupal app root (the directory containing web/) and run the installer again.', 'Or set DRUPAL_PHPUNIT_INTEGRATION_DRUPAL_CORE to the location of Drupal core.');
}
if (!command_exists('composer')) {
  fail('composer is not on your PATH.', 'Install Composer: https://getcomposer.org');
}

// Bootstrap the install path and require this package.
$version = getenv('DRUPAL_PHPUNIT_INTEGRATION_VERSION') ?: 'dev-main';
// Use dev-main by default, not @dev: @dev resolves to the highest dev branch,
// which is the 9.x line.
if (!mkdir(INSTALL_PATH . '/src', 0755, TRUE)) {
  fail('Could not create ' . INSTALL_LABEL . '/src.');
}
$created_install_path = TRUE;

$composer_json = [
  'autoload' => ['psr-4' => ['AKlump\\Drupal\\PHPUnit\\Integration\\' => 'src']],
  'repositories' => [
    ['type' => 'github', 'url' => 'https://github.com/' . PACKAGE],
  ],
];
$write = [
  INSTALL_PATH . '/.gitignore' => "/vendor/\n*/.cache\n/.phpunit.cache\n/reports/\n",
  INSTALL_PATH . '/composer.json' => json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
];
foreach ($write as $path => $contents) {
  if (file_put_contents($path, $contents) === FALSE) {
    fail("Could not write $path.");
  }
}

echo "Requiring " . PACKAGE . ":$version ...\n";
passthru('cd ' . escapeshellarg(INSTALL_PATH) . ' && composer require ' . escapeshellarg(PACKAGE . ':' . $version), $status);
if ($status !== 0) {
  fail('composer require ' . PACKAGE . ":$version failed.", 'Read the Composer error above.', 'Remove the partial install first: rm -r ' . INSTALL_LABEL, "To pick another version: set DRUPAL_PHPUNIT_INTEGRATION_VERSION='<constraint>' and run again.");
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
  elseif ((int) $phpunit_version >= 10) {
    echo "Detected PHPUnit $phpunit_version, adding bootstrap...\n";
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
  $testsuite_replacement = '$1<testsuite name="integration"><directory>' . relative_path(INSTALL_PATH, DRUPAL_ROOT) . '/modules/custom/my_module/tests/src</directory></testsuite>$2';
  $content = preg_replace($testsuite_pattern, $testsuite_replacement, $content);

  // Replace source configuration
  $source_pattern = '/(<source.*?>).*?(<\/source>)/s';
  $source_replacement = '$1<include><directory>' . relative_path(INSTALL_PATH, DRUPAL_ROOT) . '/modules/custom/my_module/src</directory></include>$2';
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
      echo "cd " . display_path(DRUPAL_ROOT) . "\n";
      echo "composer show | grep drupal/core\n";
      echo "Note the Drupal version, e.g. 11.2.8\n";
      echo "composer require --dev drupal/core-dev:^11.2 (replace 11.2 with your version)\n\n";
    }
  }
}
else {
  echo "Could not change to web directory. Please run the following commands manually:\n";
  echo "cd " . display_path(DRUPAL_ROOT) . "\n";
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
    echo "cp " . relative_path(INSTALL_PATH, DRUPAL_CORE) . "/phpunit.xml.dist phpunit.xml\n";
  }
}
else {
  echo "Could not find " . CORE_LABEL . "/phpunit.xml.dist. Please run the command manually:\n";
  echo "cp " . relative_path(INSTALL_PATH, DRUPAL_CORE) . "/phpunit.xml.dist phpunit.xml\n";
}

// Create test_output directory
echo "Creating test_output directory...\n";
if (!is_dir('test_output')) {
  if (!mkdir('test_output', 0755, TRUE)) {
    fail('Failed to create ' . INSTALL_LABEL . '/test_output.');
  }
}

// Create the runner
$runner_template = glob(INSTALL_PATH . '/vendor/aklump/drupal-phpunit-integration/init/run[-_]*.sh')[0] ?? NULL;
if (!$runner_template || !file_exists($runner_template)) {
  fail('The runner script was not found in the installed package.', 'Report this at https://github.com/aklump/drupal-phpunit-integration/issues', 'Copy the runner by hand: vendor/aklump/drupal-phpunit-integration/init/run-phpunit-tests.sh to bin/');
}
$runner_path = APP_ROOT . '/bin/' . basename($runner_template);
if (!file_exists($runner_path)) {
  echo "\n## Creating runner script...\n";
  chdir(APP_ROOT);
  if (!file_exists(APP_ROOT . '/bin')) {
    if (!mkdir(APP_ROOT . '/bin', 0755, TRUE)) {
      fail('Failed to create the bin directory.');
    }
  }

  if ($runner_template && !file_exists($runner_path)) {
    echo "Copying runner script to " . $runner_path . "\n";
    copy($runner_template, $runner_path);

    // Point the runner at the configured locations (relative to bin/).
    $bin = APP_ROOT . '/bin';
    $install_rel = relative_path($bin, INSTALL_PATH);
    $settings = [
      'INSTALL_PATH' => $install_rel . '/',
      'DRUPAL_ROOT' => relative_path($bin, DRUPAL_ROOT) . '/',
      'VENDOR_PATH' => $install_rel . '/vendor/',
    ];
    $runner = file_get_contents($runner_path);
    foreach ($settings as $name => $value) {
      $runner = preg_replace('/^' . $name . '="[^"]*"$/m', $name . '="' . $value . '"', $runner, 1);
    }
    file_put_contents($runner_path, $runner);
  }
}

// Final step
echo "\n## Final step\n";
echo "Run the tests with:\n";
echo "bin/run-phpunit-tests.sh --flush\n\n";

echo "✅ drupal-phpunit-integration installed in " . INSTALL_LABEL . ".\n";
