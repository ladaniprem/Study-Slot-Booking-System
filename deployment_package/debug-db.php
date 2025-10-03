<?php
/**
 * Database Connection Debug Script
 * Shows the exact error causing database connection issues
 */

echo "<h2>🔍 Database Connection Debug</h2>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Step 1: Loading Configuration</h3>";
try {
    require_once 'includes/config.php';
    echo "✅ Configuration loaded successfully<br>";
    
    echo "<strong>Database Settings:</strong><br>";
    echo "• Host: " . DB_HOST . "<br>";
    echo "• Database: " . DB_NAME . "<br>";
    echo "• User: " . DB_USER . "<br>";
    echo "• Password: " . (empty(DB_PASS) ? '[EMPTY]' : '[SET - ' . strlen(DB_PASS) . ' chars]') . "<br>";
    echo "• Charset: " . DB_CHARSET . "<br><br>";
    
} catch (Exception $e) {
    echo "❌ Configuration failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 2: Testing Direct PDO Connection</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    echo "DSN: <code>{$dsn}</code><br>";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✅ Direct PDO connection successful!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as db, VERSION() as version");
    $result = $stmt->fetch();
    echo "• Connected to database: <strong>{$result['db']}</strong><br>";
    echo "• MySQL version: <strong>{$result['version']}</strong><br><br>";
    
} catch (PDOException $e) {
    echo "❌ Direct PDO connection failed!<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br><br>";
    
    // Show common solutions based on error
    $error_msg = $e->getMessage();
    echo "<h4>🔧 Suggested Solutions:</h4>";
    
    if (strpos($error_msg, 'Connection refused') !== false) {
        echo "• <strong>MySQL is not running:</strong> Start XAMPP MySQL service<br>";
        echo "• Check XAMPP Control Panel and start MySQL<br>";
    } elseif (strpos($error_msg, 'Access denied') !== false) {
        echo "• <strong>Wrong credentials:</strong> Check username/password in .env file<br>";
        echo "• For XAMPP, usually user='root' and password='' (empty)<br>";
    } elseif (strpos($error_msg, 'Unknown database') !== false) {
        echo "• <strong>Database doesn't exist:</strong> Create '{DB_NAME}' database in phpMyAdmin<br>";
        echo "• Or import database_setup.sql file<br>";
    } elseif (strpos($error_msg, 'php_network_getaddresses') !== false) {
        echo "• <strong>Host resolution issue:</strong> Try different host values:<br>";
        echo "  - localhost<br>";
        echo "  - 127.0.0.1<br>";
        echo "  - ::1<br>";
    }
    echo "<br>";
}

echo "<h3>Step 3: Testing Application Database Class</h3>";
try {
    // Try to get database instance (this is what fails in your app)
    $db = Database::getInstance()->getConnection();
    echo "✅ Application Database class working!<br>";
    
    $stmt = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
    $result = $stmt->fetch();
    echo "• Tables in database: <strong>{$result['table_count']}</strong><br>";
    
} catch (Exception $e) {
    echo "❌ Application Database class failed!<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "This is the exact error your application is experiencing.<br><br>";
}

echo "<h3>Step 4: Quick Fixes</h3>";
echo "<ol>";
echo "<li><strong>Start XAMPP:</strong> Open XAMPP Control Panel and start MySQL</li>";
echo "<li><strong>Check database exists:</strong> Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a> and verify 'meeting_room_booking' database exists</li>";
echo "<li><strong>Import database:</strong> If database is empty, import database_setup.sql</li>";
echo "<li><strong>Check .env file:</strong> Verify credentials match your MySQL setup</li>";
echo "</ol>";

echo "<h3>Step 5: XAMPP Status Check</h3>";
echo "<p>Checking if XAMPP services are running...</p>";

// Check if we can connect to MySQL port
$connection = @fsockopen('localhost', 3306, $errno, $errstr, 5);
if ($connection) {
    echo "✅ MySQL port 3306 is accessible<br>";
    fclose($connection);
} else {
    echo "❌ MySQL port 3306 is not accessible<br>";
    echo "• Error: {$errstr} ({$errno})<br>";
    echo "• <strong>Solution:</strong> Start MySQL in XAMPP Control Panel<br>";
}

echo "<br><p><a href='index.php'>← Back to Application</a> | <a href='install-check.php'>Installation Check</a></p>";
?>