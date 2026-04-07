<?php
require_once 'config.php';

echo "<h2>Database & Login Test</h2>";

// Test 1: Database connection
echo "<h3>1. Database Connection</h3>";
try {
    $test = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connected</p>";
} catch (\Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check admins table exists
echo "<h3>2. Check Admins Table</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Admins table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Admins table NOT found</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 3: Check admin user
echo "<h3>3. Check Admin User</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin user found</p>";
        echo "<pre>";
        echo "ID: " . htmlspecialchars($admin['id']) . "\n";
        echo "Username: " . htmlspecialchars($admin['username']) . "\n";
        echo "Password Hash: " . htmlspecialchars($admin['password']) . "\n";
        echo "</pre>";
        
        // Test password verification
        $test_password = 'admin123';
        if (password_verify($test_password, $admin['password'])) {
            echo "<p style='color: green;'>✓ Password 'admin123' is CORRECT</p>";
        } else {
            echo "<p style='color: red;'>✗ Password 'admin123' is WRONG</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin user NOT found</p>";
        echo "<p>Try running: INSERT INTO admins (username, password) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 4: Session test
echo "<h3>4. Session Test</h3>";
// Check if session is started
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✓ Session is active</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session Path: " . session_save_path() . "</p>";
} else {
    echo "<p style='color: red;'>✗ Session is NOT active</p>";
}

echo "<hr>";
echo "<p><a href='admin/login.php'>← Back to Login</a></p>";
?>