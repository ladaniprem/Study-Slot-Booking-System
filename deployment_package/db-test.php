<?php
/**
 * Database Connection Diagnostic Script
 * This script helps identify database connection issues
 */

echo "<h2>🔍 Database Connection Diagnostic</h2>";

// Test 1: Check if .env file is loaded
echo "<h3>Test 1: Environment Variables</h3>";
require_once 'includes/config.php';

echo "<ul>";
echo "<li><strong>DB_HOST:</strong> " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "</li>";
echo "<li><strong>DB_NAME:</strong> " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "</li>";
echo "<li><strong>DB_USER:</strong> " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "</li>";
echo "<li><strong>DB_PASS:</strong> " . (defined('DB_PASS') ? (DB_PASS ? '[SET - Length: ' . strlen(DB_PASS) . ']' : '[EMPTY]') : 'NOT DEFINED') . "</li>";
echo "<li><strong>DB_CHARSET:</strong> " . (defined('DB_CHARSET') ? DB_CHARSET : 'NOT DEFINED') . "</li>";
echo "</ul>";

// Test 2: Check PHP Extensions
echo "<h3>Test 2: Required PHP Extensions</h3>";
$extensions = ['pdo', 'pdo_mysql'];
echo "<ul>";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "✅ Available" : "❌ Missing";
    echo "<li><strong>{$ext}:</strong> {$status}</li>";
}
echo "</ul>";

// Test 3: Test Raw PDO Connection
echo "<h3>Test 3: Raw PDO Connection Test</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    echo "<p>Testing connection to host: <code>{$dsn}</code></p>";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green;'>✅ <strong>Host connection successful!</strong></p>";
    
    // Test 4: Test Database Selection
    echo "<h3>Test 4: Database Selection Test</h3>";
    $dsn_with_db = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    echo "<p>Testing connection to database: <code>{$dsn_with_db}</code></p>";
    
    $pdo_db = new PDO($dsn_with_db, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green;'>✅ <strong>Database connection successful!</strong></p>";
    
    // Test 5: Test Database Tables
    echo "<h3>Test 5: Database Tables Check</h3>";
    $stmt = $pdo_db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠️ <strong>Database is empty!</strong> You need to import database_setup.sql</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($tables) . " tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    }
    
    // Test 6: Test Database Class
    echo "<h3>Test 6: Application Database Class Test</h3>";
    if (class_exists('Database')) {
        try {
            $db = Database::getInstance()->getConnection();
            echo "<p style='color: green;'>✅ <strong>Application database class working!</strong></p>";
            
            // Test a simple query
            $stmt = $db->query("SELECT DATABASE() as current_db, NOW() as current_time");
            $result = $stmt->fetch();
            echo "<p>Current database: <strong>{$result['current_db']}</strong></p>";
            echo "<p>Current time: <strong>{$result['current_time']}</strong></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ <strong>Application database class failed:</strong> " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>Database class not found!</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ <strong>Connection failed:</strong> " . $e->getMessage() . "</p>";
    
    // Common error solutions
    echo "<h3>🔧 Common Solutions</h3>";
    echo "<ul>";
    echo "<li><strong>Connection refused:</strong> Start XAMPP MySQL service</li>";
    echo "<li><strong>Access denied:</strong> Check DB_USER and DB_PASS in .env file</li>";
    echo "<li><strong>Unknown database:</strong> Create database or check DB_NAME in .env file</li>";
    echo "<li><strong>Host issues:</strong> Try 'localhost', '127.0.0.1', or '::1'</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Unexpected error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📋 Quick Fixes</h3>";
echo "<ol>";
echo "<li><strong>Start XAMPP:</strong> Make sure Apache and MySQL are running in XAMPP Control Panel</li>";
echo "<li><strong>Check .env:</strong> Verify database credentials in .env file</li>";
echo "<li><strong>Import database:</strong> Run database_setup.sql in phpMyAdmin if tables are missing</li>";
echo "<li><strong>Test connection:</strong> Try different host values (localhost, 127.0.0.1)</li>";
echo "</ol>";

echo "<p><a href='install-check.php'>← Back to Installation Check</a></p>";
?>