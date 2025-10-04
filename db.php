<?php
/**
 * db.php
 *
 * Standalone database connection helper.
 * - Loads environment from .env (uses Composer Dotenv if available)
 * - Provides a DB class with a PDO singleton via DB::getConnection()
 */

// ---- Load environment (Composer Dotenv if available, otherwise manual loader) ----
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } catch (Exception $e) {
        // fallback to manual loading below
    }
}

// Manual .env loader (if Dotenv not available or .env wasn't loaded)
if (!isset($_ENV['DB_HOST']) || !isset($_ENV['DB_NAME'])) {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                }
                putenv("$key=$value");
            }
        }
    }
}

// ---- Configuration defaults ----
defined('DB_HOST') || define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', $_ENV['DB_NAME'] ?? 'meeting_room_booking');
defined('DB_USER') || define('DB_USER', $_ENV['DB_USER'] ?? 'root');
defined('DB_PASS') || define('DB_PASS', $_ENV['DB_PASS'] ?? '');
defined('DB_CHARSET') || define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

class DB
{
    /** @var PDO|null */
    private static $pdo = null;

    /**
     * Get PDO connection (singleton)
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection()
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = DB_HOST;
        $db = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $charset = DB_CHARSET;

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$pdo = new PDO($dsn, $user, $pass, $options);
        return self::$pdo;
    }

    /** Close the connection (for testing) */
    public static function close()
    {
        self::$pdo = null;
    }
}

// Convenience wrapper: returns PDO instance
function get_db()
{
    return DB::getConnection();
}

// If this file is executed directly, run a quick connection test and show status
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $pdo = DB::getConnection();
        echo "<p style='color:green;'>Database connected: " . htmlentities(DB_NAME) . "</p>";
    } catch (Exception $e) {
        http_response_code(500);
        echo "<p style='color:red;'>Database connection failed: " . htmlentities($e->getMessage()) . "</p>";
    }
}

?>
