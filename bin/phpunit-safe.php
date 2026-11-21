<?php

declare(strict_types=1);

/*
 * Controlled PHPUnit entrypoint. This file and its gate are deliberately
 * independent of Composer so approval is checked before PHPUnit's autoloader.
 */

$projectRoot = dirname(__DIR__);
$fail = static function (string $message): void {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

if (!chdir($projectRoot)) {
    $fail('Could not enter the project directory; PHPUnit was not started.');
}

$gate = $projectRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR
    . 'bootstrap' . DIRECTORY_SEPARATOR . 'database-safety.php';
$phpunitProxy = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
    . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
$launcherMarker = 'AYU_GLOBAL_PHPUNIT_SAFE_LAUNCHER';
$gateMarker = 'AYU_GLOBAL_NO_DATABASE_GATE_PASSED';

if (!is_file($gate) || !is_readable($gate)) {
    $fail('The database safety gate is missing or unreadable; PHPUnit was not started.');
}

if (!is_file($phpunitProxy) || !is_readable($phpunitProxy)) {
    $fail('The installed PHPUnit entrypoint is missing or unreadable.');
}

// This launcher only supports the locked no-database configuration. Reject
// positional paths and unknown options so callers cannot add tests or change
// how PHPUnit loads the configured suite. Selection options can only narrow
// the four tests listed in phpunit.xml.
$arguments = array_slice($_SERVER['argv'] ?? [], 1);
$flagsWithoutValues = [
    '--debug',
    '--do-not-cache-result',
    '--no-coverage',
    '--stop-on-failure',
    '--stderr',
    '--testdox',
    '--verbose',
    '-v',
];
$optionsWithValues = [
    '--colors' => true,
    '--exclude-group' => true,
    '--filter' => true,
    '--group' => true,
    '--testsuite' => true,
];
for ($index = 0, $argumentCount = count($arguments); $index < $argumentCount; $index++) {
    $argument = $arguments[$index];
    $equalsPosition = strpos($argument, '=');
    $option = $equalsPosition === false ? $argument : substr($argument, 0, $equalsPosition);
    $value = $equalsPosition === false ? null : substr($argument, $equalsPosition + 1);

    if (in_array($option, $flagsWithoutValues, true) && $value === null) {
        continue;
    }

    if (array_key_exists($option, $optionsWithValues)) {
        if ($value === null) {
            if (!isset($arguments[$index + 1])) {
                $fail('A PHPUnit option is missing its value.');
            }
            $value = $arguments[++$index];
        }
        if ($value === '') {
            $fail('A PHPUnit option has an empty value.');
        }
        if ($option === '--testsuite' && $value !== 'Unit') {
            $fail('Only the configured Unit test suite is permitted.');
        }
        if ($option === '--colors' && !in_array($value, ['always', 'never', 'auto'], true)) {
            $fail('The PHPUnit colors option has an unsupported value.');
        }
        continue;
    }

    $fail('Unsupported PHPUnit argument; positional test paths and suite expansion are not allowed.');
}

$profile = getenv('AYU_TEST_PROFILE');
if ($profile === false || $profile === '') {
    putenv('AYU_TEST_PROFILE=no-database');
    $_ENV['AYU_TEST_PROFILE'] = 'no-database';
    $_SERVER['AYU_TEST_PROFILE'] = 'no-database';
} elseif ($profile !== 'no-database') {
    $fail('Unsupported test profile; PHPUnit was not started.');
}

// The XML bootstrap must be able to distinguish this pre-Composer pass from
// a direct PHPUnit --prepend invocation. Constants cannot be supplied by the
// ordinary process environment.
if (class_exists('Composer\\Autoload\\ClassLoader', false)
    || defined($launcherMarker)
    || defined($gateMarker)) {
    $fail('The safety launcher must start in a fresh pre-Composer process.');
}
define($launcherMarker, true);

$gateResult = require $gate;
if ($gateResult !== true) {
    $fail('The database safety gate did not approve this run; PHPUnit was not started.');
}

// The standalone gate must not load Composer itself.
if (class_exists('Composer\\Autoload\\ClassLoader', false)) {
    $fail('Composer was loaded before the safety gate completed; PHPUnit was not started.');
}

// Requiring Composer's generated proxy preserves the installed PHPUnit
// bootstrap, isolation settings, CLI options, and the original argv unchanged.
require $phpunitProxy;
