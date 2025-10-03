<?php
/**
 * Installation Check Script for Study Slot Booking System
 * This script helps verify that your deployment is correctly configured
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Check - Study Slot Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .check-pass { color: #28a745; }
        .check-fail { color: #dc3545; }
        .check-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">📋 Installation Check</h1>
        <p class="text-muted">This script checks if your Study Slot Booking System is properly configured.</p>
        
        <div class="row">
            <div class="col-md-8">
                
                <?php
                $checks = [];
                $errors = [];
                $warnings = [];
                
                // Check 1: PHP Version
                $phpVersion = PHP_VERSION;
                if (version_compare($phpVersion, '7.4.0', '>=')) {
                    $checks[] = "✅ PHP Version: {$phpVersion} (OK)";
                } else {
                    $errors[] = "❌ PHP Version: {$phpVersion} (Requires 7.4+)";
                }
                
                // Check 2: Required Extensions
                $requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'session'];
                foreach ($requiredExtensions as $ext) {
                    if (extension_loaded($ext)) {
                        $checks[] = "✅ PHP Extension '{$ext}': Available";
                    } else {
                        $errors[] = "❌ PHP Extension '{$ext}': Missing";
                    }
                }
                
                // Check 3: .env File
                $envFile = __DIR__ . '/.env';
                if (file_exists($envFile)) {
                    $checks[] = "✅ Environment file (.env): Found";
                    
                    // Check if .env is readable
                    if (is_readable($envFile)) {
                        $checks[] = "✅ Environment file: Readable";
                    } else {
                        $errors[] = "❌ Environment file: Not readable (check permissions)";
                    }
                } else {
                    $errors[] = "❌ Environment file (.env): Missing - Create from .env.example";
                }
                
                // Check 4: Config Loading
                try {
                    require_once 'includes/config.php';
                    $checks[] = "✅ Configuration: Loaded successfully";
                    
                    // Check database constants
                    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
                        $checks[] = "✅ Database configuration: Constants defined";
                    } else {
                        $errors[] = "❌ Database configuration: Constants not defined";
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "❌ Configuration loading failed: " . $e->getMessage();
                }
                
                // Check 5: Database Connection
                if (class_exists('Database')) {
                    try {
                        $db = Database::getInstance()->getConnection();
                        $checks[] = "✅ Database connection: Successful";
                        
                        // Check required tables
                        $requiredTables = ['users', 'rooms', 'bookings', 'booking_details'];
                        foreach ($requiredTables as $table) {
                            $stmt = $db->prepare("SHOW TABLES LIKE ?");
                            $stmt->execute([$table]);
                            if ($stmt->rowCount() > 0) {
                                $checks[] = "✅ Database table '{$table}': Exists";
                            } else {
                                $errors[] = "❌ Database table '{$table}': Missing";
                            }
                        }
                        
                    } catch (Exception $e) {
                        $errors[] = "❌ Database connection failed: " . $e->getMessage();
                    }
                } else {
                    $errors[] = "❌ Database class not found";
                }
                
                // Check 6: Directory Permissions
                $dirs = ['logs', 'qr_codes'];
                foreach ($dirs as $dir) {
                    if (is_dir($dir)) {
                        if (is_writable($dir)) {
                            $checks[] = "✅ Directory '{$dir}': Writable";
                        } else {
                            $warnings[] = "⚠️ Directory '{$dir}': Not writable (may cause issues)";
                        }
                    } else {
                        $warnings[] = "⚠️ Directory '{$dir}': Not found (will be created automatically)";
                    }
                }
                
                // Check 7: Composer (Optional)
                $vendorPath = __DIR__ . '/vendor/autoload.php';
                if (file_exists($vendorPath)) {
                    $checks[] = "✅ Composer dependencies: Installed (optimal performance)";
                } else {
                    $warnings[] = "⚠️ Composer dependencies: Not installed (using fallback - still works!)";
                }
                
                // Display Results
                echo "<div class='card mb-4'>";
                echo "<div class='card-header'><h5>✅ Passed Checks</h5></div>";
                echo "<div class='card-body'>";
                if (!empty($checks)) {
                    echo "<ul class='list-unstyled check-pass'>";
                    foreach ($checks as $check) {
                        echo "<li>{$check}</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='text-muted'>No checks passed.</p>";
                }
                echo "</div></div>";
                
                if (!empty($warnings)) {
                    echo "<div class='card mb-4'>";
                    echo "<div class='card-header'><h5>⚠️ Warnings</h5></div>";
                    echo "<div class='card-body'>";
                    echo "<ul class='list-unstyled check-warning'>";
                    foreach ($warnings as $warning) {
                        echo "<li>{$warning}</li>";
                    }
                    echo "</ul>";
                    echo "</div></div>";
                }
                
                if (!empty($errors)) {
                    echo "<div class='card mb-4'>";
                    echo "<div class='card-header'><h5>❌ Errors (Need Fixing)</h5></div>";
                    echo "<div class='card-body'>";
                    echo "<ul class='list-unstyled check-fail'>";
                    foreach ($errors as $error) {
                        echo "<li>{$error}</li>";
                    }
                    echo "</ul>";
                    echo "</div></div>";
                }
                
                // Overall Status
                if (empty($errors)) {
                    if (empty($warnings)) {
                        echo "<div class='alert alert-success'>";
                        echo "<h4>🎉 Perfect Installation!</h4>";
                        echo "<p>All checks passed. Your Study Slot Booking System is ready to use!</p>";
                        echo "<a href='index.php' class='btn btn-success'>Go to Application</a>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-warning'>";
                        echo "<h4>✅ Installation OK (with warnings)</h4>";
                        echo "<p>Your system will work, but you may want to address the warnings above for optimal performance.</p>";
                        echo "<a href='index.php' class='btn btn-primary'>Go to Application</a>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>";
                    echo "<h4>❌ Installation Issues Found</h4>";
                    echo "<p>Please fix the errors above before using the application.</p>";
                    echo "</div>";
                }
                ?>
                
                <hr>
                <h5>📚 Quick Setup Guide</h5>
                <ol>
                    <li><strong>Create .env file:</strong> Copy <code>.env.example</code> to <code>.env</code></li>
                    <li><strong>Edit database settings:</strong> Update <code>.env</code> with your database credentials</li>
                    <li><strong>Import database:</strong> Run <code>database_setup.sql</code> in phpMyAdmin</li>
                    <li><strong>Set permissions:</strong> Ensure <code>logs/</code> and <code>qr_codes/</code> are writable</li>
                    <li><strong>Test:</strong> Refresh this page to verify all checks pass</li>
                </ol>
                
                <div class="mt-4">
                    <a href="?" class="btn btn-outline-primary">🔄 Re-run Check</a>
                    <a href="README.md" class="btn btn-outline-info">📖 Full Documentation</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>System Information</h6>
                    </div>
                    <div class="card-body small">
                        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
                        <p><strong>Script Path:</strong> <?php echo __DIR__; ?></p>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6>Environment Variables</h6>
                    </div>
                    <div class="card-body small">
                        <?php
                        $envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_NAME'];
                        foreach ($envVars as $var) {
                            $value = $_ENV[$var] ?? 'Not set';
                            if ($var === 'DB_PASS') {
                                $value = isset($_ENV[$var]) ? '[Hidden]' : 'Not set';
                            }
                            echo "<p><strong>{$var}:</strong> {$value}</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>