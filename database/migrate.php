<?php
/**
 * Database Migration & Seeder CLI/Web Runner
 */

require_once __DIR__ . '/../app/config/constants.php';
$config = require APP_PATH . '/config/database.php';

echo "=== CicalengkaGO Database Migration ===\n";

try {
    // Connect to MySQL server first (without specifying DB) to ensure DB creation
    $dsnNoDb = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdoRoot = new PDO($dsnNoDb, $config['username'], $config['password'], $config['options']);
    
    echo "Creating database `{$config['database']}` if not exists...\n";
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `{$config['database']}` ready.\n";

    // Connect to specific database
    $dsnWithDb = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsnWithDb, $config['username'], $config['password'], $config['options']);

    // Run schema
    echo "Executing schema.sql...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    echo "Schema successfully installed.\n";

    // Run seeders
    echo "Executing seeders.sql...\n";
    $seedersSql = file_get_contents(__DIR__ . '/seeders.sql');
    $pdo->exec($seedersSql);
    echo "Seeders successfully loaded.\n";

    echo "=== Migration Finished Successfully! ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
