<?php

declare(strict_types=1);

/*
 * Standalone no-database gate. The controlled launcher runs this before
 * Composer. PHPUnit later loads this file again from phpunit.xml; that second
 * pass is accepted only in the same process after exact sentinel validation.
 */

$fail = static function (string $message): void {
    fwrite(STDERR, 'Database safety gate: ' . $message . PHP_EOL);
    exit(1);
};
$environment = static function (string $name) {
    $value = getenv($name);

    return $value === false ? null : $value;
};
$setEnvironment = static function (string $name, string $value): void {
    putenv($name . '=' . $value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
};

$projectRoot = dirname(__DIR__, 2);
$launcherMarker = 'AYU_GLOBAL_PHPUNIT_SAFE_LAUNCHER';
$reentryMarker = 'AYU_GLOBAL_NO_DATABASE_GATE_PASSED';
$pinnedEnvironment = [
    'APP_ENV' => 'testing',
    'APP_RUNNING_IN_CONSOLE' => 'true',
    'AYU_TEST_PROFILE' => 'no-database',
    'DB_CONNECTION' => 'ayu_disabled',
    'DB_HOST' => 'disabled.invalid',
    'DB_PORT' => '1',
    'DB_DATABASE' => 'ayu_disabled',
    'DB_USERNAME' => 'ayu_disabled',
    'DB_PASSWORD' => 'ayu_disabled',
    'DB_SOCKET' => '',
    'DB_URL' => '',
    'DATABASE_URL' => '',
    'MYSQL_HOST' => '',
    'MYSQL_TCP_PORT' => '',
    'MYSQL_UNIX_PORT' => '',
];

$assertNoConfigCache = static function () use ($environment, $projectRoot, $fail): void {
    $cacheOverride = $environment('APP_CONFIG_CACHE');
    if ($cacheOverride !== null && trim($cacheOverride) !== '') {
        $fail('Custom Laravel configuration-cache paths are not allowed.');
    }
    if (is_file($projectRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR
        . 'cache' . DIRECTORY_SEPARATOR . 'config.php')) {
        $fail('A Laravel configuration cache is present.');
    }
};

$profile = $environment('AYU_TEST_PROFILE');
if ($profile !== 'no-database') {
    $fail('Only the no-database profile is enabled by this entrypoint.');
}

$assertNoConfigCache();

// The launcher marker is process-local and cannot be supplied through an
// environment variable. It also prevents PHPUnit's --prepend route from
// treating this first pass as authorization.
if (!defined($launcherMarker) || constant($launcherMarker) !== true) {
    $fail('Use bin/phpunit-safe.php; direct PHPUnit bootstrap is not permitted.');
}

// The XML bootstrap must re-enter only in the same process that passed the
// launcher's pre-Composer check.
if (defined($reentryMarker)) {
    if (constant($reentryMarker) !== true) {
        $fail('The database safety gate marker is invalid.');
    }
    foreach ($pinnedEnvironment as $name => $expected) {
        if ($environment($name) !== $expected
            || !array_key_exists($name, $_ENV)
            || $_ENV[$name] !== $expected
            || !array_key_exists($name, $_SERVER)
            || $_SERVER[$name] !== $expected) {
            $fail('Pinned no-database environment changed after the safety gate passed.');
        }
    }

    $environmentVariables = getenv();
    foreach ([$environmentVariables, $_ENV, $_SERVER] as $values) {
        if (!is_array($values)) {
            continue;
        }
        foreach ($values as $name => $value) {
            $upperName = strtoupper((string) $name);
            $isDatabaseVariable = strpos($upperName, 'DB_') === 0
                || strpos($upperName, 'MYSQL_') === 0
                || strpos($upperName, 'AYU_') === 0 && $upperName !== 'AYU_TEST_PROFILE'
                || $upperName === 'DATABASE_URL';
            if ($isDatabaseVariable
                && (!array_key_exists($name, $pinnedEnvironment)
                    || (string) $value !== $pinnedEnvironment[$name])) {
                $fail('Unexpected database-related environment value during gate re-entry.');
            }
        }
    }

    return true;
}

if (class_exists('Composer\\Autoload\\ClassLoader', false)) {
    $fail('The first database safety gate pass must run before Composer.');
}

foreach (['AYU_TEST_APPROVAL_MANIFEST', 'AYU_TEST_APPROVAL_PUBLIC_KEY'] as $pathOverride) {
    if ($environment($pathOverride) !== null
        || array_key_exists($pathOverride, $_ENV)
        || array_key_exists($pathOverride, $_SERVER)) {
        $fail('Approval path overrides are not accepted.');
    }
}

$appEnvironment = $environment('APP_ENV');
if ($appEnvironment !== null && $appEnvironment !== 'testing') {
    $fail('An unapproved application environment override was supplied.');
}
$consoleEnvironment = $environment('APP_RUNNING_IN_CONSOLE');
if ($consoleEnvironment !== null && strtolower($consoleEnvironment) !== 'true') {
    $fail('APP_RUNNING_IN_CONSOLE must be explicitly true for test execution.');
}

// Reject caller-supplied database endpoints and credentials before pinning
// inert sentinels. Include PHP's environment superglobals as well as getenv().
$environmentVariables = getenv();
foreach ([$environmentVariables, $_ENV, $_SERVER] as $values) {
    if (!is_array($values)) {
        continue;
    }
    foreach ($values as $name => $value) {
        $upperName = strtoupper((string) $name);
        $isDatabaseOverride = strpos($upperName, 'DB_') === 0
            || strpos($upperName, 'MYSQL_') === 0
            || strpos($upperName, 'AYU_') === 0 && $upperName !== 'AYU_TEST_PROFILE'
            || $upperName === 'DATABASE_URL';
        if ($isDatabaseOverride && trim((string) $value) !== '') {
            $fail('An unapproved database-related environment override was supplied.');
        }
    }
}

foreach ($pinnedEnvironment as $name => $value) {
    $setEnvironment($name, $value);
}

if (!defined($reentryMarker)) {
    define($reentryMarker, true);
}

return true;
