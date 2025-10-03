<?php
/**
 * Database and Environment Test Script
 * This script tests if the .env configuration and database connection are working properly
 */

// Include the config file
require_once 'includes/config.php';

echo "<h2>Environment and Database Test Results</h2>";

// Test 1: Environment Variables
echo "<h3>1. Environment Variables Test</h3>";
echo "<ul>";
echo "<li>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "</li>";
echo "<li>DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "</li>";
echo "<li>DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "</li>";
echo "<li>DB_PASS: " . (defined('DB_PASS') ? (DB_PASS ? '[SET]' : '[EMPTY]') : 'NOT DEFINED') . "</li>";
echo "<li>APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "</li>";
echo "<li>ENCRYPTION_KEY: " . (defined('ENCRYPTION_KEY') ? (ENCRYPTION_KEY ? '[SET]' : '[EMPTY]') : 'NOT DEFINED') . "</li>";
echo "</ul>";

// Test 2: Database Connection
echo "<h3>2. Database Connection Test</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $result = $stmt->fetch();
    echo "<p>Tables in database: " . $result['table_count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 3: Tables Existence
echo "<h3>3. Required Tables Test</h3>";
$required_tables = ['users', 'rooms', 'bookings', 'booking_details'];
echo "<ul>";

try {
    $db = Database::getInstance()->getConnection();
    foreach ($required_tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? "✅" : "❌";
        echo "<li>$status $table</li>";
    }
} catch (Exception $e) {
    echo "<li>❌ Error checking tables: " . $e->getMessage() . "</li>";
}
echo "</ul>";

// Test 4: Sample Data
echo "<h3>4. Sample Data Test</h3>";
try {
    $db = Database::getInstance()->getConnection();
    
    // Check users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $users_count = $stmt->fetchColumn();
    echo "<p>Users in database: $users_count</p>";
    
    // Check rooms
    $stmt = $db->query("SELECT COUNT(*) as count FROM rooms");
    $rooms_count = $stmt->fetchColumn();
    echo "<p>Rooms in database: $rooms_count</p>";
    
    // Check bookings
    $stmt = $db->query("SELECT COUNT(*) as count FROM bookings");
    $bookings_count = $stmt->fetchColumn();
    echo "<p>Bookings in database: $bookings_count</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking sample data: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Test completed!</strong> If all tests show ✅, your system is ready for use.</p>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
?>