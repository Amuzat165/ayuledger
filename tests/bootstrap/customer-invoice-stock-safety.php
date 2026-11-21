<?php

declare(strict_types=1);

/*
 * This bootstrap intentionally runs before Composer's Laravel application
 * bootstrap. It requires externally supplied approval values and verifies
 * the live MySQL identity before loading the application or test class.
 */

$projectRoot = dirname(__DIR__, 2);
$fail = static function (string $message): void {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};
$environment = static function (string $name) {
    $value = getenv($name);

    return $value === false ? null : $value;
};
$isPlaceholder = static function (string $value): bool {
    return $value === ''
        || preg_match('/<[^>]*>|\b(?:TODO|TBD|REPLACE_ME|PLACEHOLDER|YOUR_VALUE)\b/i', $value) === 1;
};

if ($environment('AYU_CUSTOMER_INVOICE_STOCK_TARGET_APPROVED') !== '1'
    || $environment('AYU_CUSTOMER_INVOICE_STOCK_IDENTITY_APPROVED') !== '1') {
    $fail('Customer-invoice stock tests require separate explicit target and identity approvals.');
}

$approvalVariables = [
    'database' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_DATABASE',
    'host' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_HOST',
    'port' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_PORT',
    'username' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_USERNAME',
    'server_hostname' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_SERVER_HOSTNAME',
    'server_port' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_SERVER_PORT',
    'authenticated_account' => 'AYU_CUSTOMER_INVOICE_STOCK_APPROVED_AUTHENTICATED_ACCOUNT',
];
$approved = [];
foreach ($approvalVariables as $key => $variable) {
    $value = $environment($variable);
    if (!is_string($value) || $isPlaceholder($value)) {
        $fail('An independently approved customer-invoice test identity value is missing or unresolved.');
    }
    $approved[$key] = $value;
}

if (!preg_match('/^ayuglobal_step6d1_test_[a-z0-9_]+$/i', $approved['database'])
    || in_array(strtolower($approved['database']), ['karma', 'ayu_po_stage2_test', 'ayuglobal_saas_t01_test'], true)
    || !ctype_digit($approved['port'])
    || (int) $approved['port'] < 1
    || (int) $approved['port'] > 65535
    || !ctype_digit($approved['server_port'])
    || (int) $approved['server_port'] < 1
    || (int) $approved['server_port'] > 65535) {
    $fail('The approved customer-invoice test target is invalid or explicitly prohibited.');
}

if ($environment('APP_ENV') !== 'testing' || $environment('APP_RUNNING_IN_CONSOLE') !== 'true') {
    $fail('APP_ENV=testing and explicit APP_RUNNING_IN_CONSOLE=true are required.');
}

$expectedConfiguration = '--configuration=phpunit.customer-invoice-stock.xml';
$expectedFilter = 'test_duplicate_inventory_lines_(cannot_oversell_combined_available_stock|within_stock_are_preserved_and_deducted_by_combined_quantity)';
$arguments = $_SERVER['argv'] ?? [];
$configurationArgumentFound = in_array($expectedConfiguration, $arguments, true);
$filter = null;
foreach ($arguments as $index => $argument) {
    if (strpos($argument, '--filter=') === 0) {
        $filter = substr($argument, strlen('--filter='));
        break;
    }
    if ($argument === '--filter' && isset($arguments[$index + 1])) {
        $filter = $arguments[$index + 1];
        break;
    }
}
if (!$configurationArgumentFound || $filter !== $expectedFilter) {
    $fail('Use the dedicated PHPUnit configuration and exact two-test filter; no test was started.');
}

$configCacheOverride = $environment('APP_CONFIG_CACHE');
if ($configCacheOverride !== null && trim($configCacheOverride) !== '') {
    $fail('Custom Laravel configuration-cache paths are not allowed for this test.');
}
if (is_file($projectRoot . DIRECTORY_SEPARATOR . 'bootstrap/cache/config.php')) {
    $fail('A Laravel configuration cache is present; the test was not started.');
}

foreach (['DB_URL', 'DATABASE_URL'] as $urlVariable) {
    $value = $environment($urlVariable);
    if ($value !== null && trim($value) !== '') {
        $fail('URL-based database overrides are not allowed for this test.');
    }
}

foreach (['MYSQL_HOST', 'MYSQL_TCP_PORT', 'MYSQL_UNIX_PORT'] as $mysqlOverride) {
    $value = $environment($mysqlOverride);
    if ($value !== null && trim($value) !== '') {
        $fail('Unreviewed MySQL client connection overrides are not allowed for this test.');
    }
}

$configuredValues = [
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => $approved['host'],
    'DB_PORT' => $approved['port'],
    'DB_DATABASE' => $approved['database'],
    'DB_USERNAME' => $approved['username'],
    'AYU_TEST_DATABASE' => $approved['database'],
];
foreach ($configuredValues as $variable => $expected) {
    $value = $environment($variable);
    if (!is_string($value) || $value !== $expected) {
        $fail('A process-scoped database target setting is missing or conflicts with the approved target.');
    }
}

$socket = $environment('DB_SOCKET');
if ($socket !== null && trim($socket) !== '') {
    $fail('A nonempty MySQL socket override is not allowed for this test.');
}

$password = $environment('DB_PASSWORD');
if (!is_string($password) || $password === '') {
    $fail('A process-scoped database password is required; no application bootstrap was started.');
}

if (!extension_loaded('pdo_mysql')) {
    $fail('The PDO MySQL extension is unavailable; no application bootstrap was started.');
}

// Ensure an absent DB_SOCKET cannot be supplied later by .env.testing.
putenv('DB_SOCKET=');
$_ENV['DB_SOCKET'] = '';
$_SERVER['DB_SOCKET'] = '';
// Pin Laravel to the already-checked default cache path and neutralize file-based URL overrides.
putenv('APP_CONFIG_CACHE=bootstrap/cache/config.php');
$_ENV['APP_CONFIG_CACHE'] = 'bootstrap/cache/config.php';
$_SERVER['APP_CONFIG_CACHE'] = 'bootstrap/cache/config.php';
foreach (['DB_URL', 'DATABASE_URL'] as $urlVariable) {
    putenv($urlVariable . '=');
    $_ENV[$urlVariable] = '';
    $_SERVER[$urlVariable] = '';
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $approved['host'],
        $approved['port'],
        $approved['database']
    );
    $pdo = new PDO($dsn, $approved['username'], $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $identity = $pdo->query(
        'SELECT DATABASE() AS database_name, CURRENT_USER() AS authenticated_account, @@hostname AS server_hostname, @@port AS server_port'
    )->fetch(PDO::FETCH_ASSOC);
    $pdo = null;
} catch (Throwable $exception) {
    unset($exception, $password, $pdo);
    $fail('Pre-bootstrap MySQL identity verification failed; Laravel and the tests were not started.');
}
unset($password);

if (!is_array($identity)
    || (string) ($identity['database_name'] ?? '') !== $approved['database']
    || (string) ($identity['authenticated_account'] ?? '') !== $approved['authenticated_account']
    || (string) ($identity['server_hostname'] ?? '') !== $approved['server_hostname']
    || (string) ($identity['server_port'] ?? '') !== $approved['server_port']) {
    unset($identity);
    $fail('The live MySQL identity does not match the independently approved target; Laravel was not started.');
}
unset($identity);

if (is_file($projectRoot . DIRECTORY_SEPARATOR . 'bootstrap/cache/config.php')) {
    $fail('Laravel configuration cache appeared during preflight; the application was not started.');
}

if (!defined('AYU_CUSTOMER_INVOICE_STOCK_SAFETY_GATE_PASSED')) {
    define('AYU_CUSTOMER_INVOICE_STOCK_SAFETY_GATE_PASSED', true);
}

require $projectRoot . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
