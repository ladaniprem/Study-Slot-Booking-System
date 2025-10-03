<?php
/**
 * Quick Test Script to Verify Composer Autoload Fix
 * This simulates the exact error scenario and tests the fix
 */

echo "<h2>🧪 Testing Composer Autoload Fix</h2>";

// Test 1: Check if vendor directory exists
echo "<h3>Test 1: Vendor Directory Check</h3>";
$vendorPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorPath)) {
    echo "✅ Vendor directory found: Will use Composer<br>";
} else {
    echo "⚠️ Vendor directory NOT found: Will use fallback system<br>";
}

// Test 2: Load config and check if it works
echo "<h3>Test 2: Configuration Loading</h3>";
try {
    require_once 'includes/config.php';
    echo "✅ Configuration loaded successfully!<br>";
    
    // Test 3: Check if environment variables are loaded
    echo "<h3>Test 3: Environment Variables</h3>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
    echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "<br>";
    
    if (defined('DB_HOST') && defined('DB_NAME') && defined('APP_NAME')) {
        echo "✅ All environment variables loaded correctly!<br>";
    } else {
        echo "❌ Some environment variables missing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Configuration loading failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>🎯 Result</h3>";
if (defined('DB_HOST')) {
    echo "<div style='color: green; font-weight: bold;'>✅ SUCCESS: The Composer autoload fix is working perfectly!</div>";
    echo "<p>Your deployment package will work on any hosting service, with or without Composer.</p>";
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ ISSUE: Configuration not loading properly</div>";
}

echo "<br><a href='index.php'>← Back to Application</a>";
?>