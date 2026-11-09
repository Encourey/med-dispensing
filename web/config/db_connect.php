<?php
/**
 * Database connection - nothing else.
 * Schema lives in database/schema.sql (import it once; see docs/setup-laragon.md).
 *
 * Credentials come from config.local.php (git-ignored) if it exists,
 * otherwise from config.example.php (Laragon defaults: root, no password).
 */
$configFile = file_exists(__DIR__ . '/config.local.php')
    ? __DIR__ . '/config.local.php'
    : __DIR__ . '/config.example.php';
$dbConfig = require $configFile;

try {
    // PHP 8.1+ throws mysqli_sql_exception on connection failure; older versions
    // set connect_error instead. Handle both.
    $conn = new mysqli(
        $dbConfig['db_host'],
        $dbConfig['db_user'],
        $dbConfig['db_pass'],
        $dbConfig['db_name']
    );
    if ($conn->connect_error) {
        throw new mysqli_sql_exception($conn->connect_error);
    }
} catch (mysqli_sql_exception $e) {
    // Details go to the server log, not to the visitor's browser.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Check web/config/config.local.php and that MySQL is running.');
}

$conn->set_charset('utf8mb4');

unset($configFile, $dbConfig);   // don't leak credentials into pages that include this file
