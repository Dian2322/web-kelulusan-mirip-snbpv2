<?php
// config.php - database configuration
// Show PHP errors (helpful for debugging on hosting environments).
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Ensure session cookies work correctly on shared hosting (cPanel) and subfolders.
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $host,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        // For older PHP versions, only set the core params (samesite unsupported).
        session_set_cookie_params(0, '/', $host, $secure, true);
    }

    session_start();
}

// Database configuration - adjust for hosting environment
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'belajarinfotmati_pengumuman';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Instead of throwing, display a user-friendly error and stop execution.
    die("Database connection failed: " . htmlspecialchars($e->getMessage()) . ". Please check your database credentials in config.php.");
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        // Use absolute path for redirects on hosting
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME']);
        $login_url = $protocol . '://' . $host . $script . '/login.php';
        header('Location: ' . $login_url);
        exit;
    }
}

function ensure_settings_table(PDO $pdo) {
    static $initialized = false;

    if ($initialized) {
        return true;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `value` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        try {
            $pdo->exec("ALTER TABLE `settings` MODIFY COLUMN `value` TEXT DEFAULT NULL");
        } catch (\Throwable $e) {
            // Keep existing schema if ALTER is unsupported.
        }

        $defaults = [
            'announcement_time' => '',
            'logo' => 'logo.png',
            'background' => '',
            'skl_link' => '',
            'skl_label' => 'Download SKL.Pdf',
            'result_info_note' => '',
            'result_info_note_color' => '#f5f8ff',
            'result_info_note_icon' => 'fas fa-circle-info'
        ];

        $stmt = $pdo->prepare(
            "INSERT INTO settings (name, value)
             VALUES (:name, :value)
             ON DUPLICATE KEY UPDATE value = COALESCE(value, VALUES(value))"
        );

        foreach ($defaults as $name => $value) {
            $stmt->execute([
                'name' => $name,
                'value' => $value
            ]);
        }

        $initialized = true;
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}
