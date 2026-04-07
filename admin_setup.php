<?php
session_start();

// Setup password - Ubah sesuai kebutuhan
$SETUP_PASSWORD = 'Slariang21*';

// Check if user is authenticated
$is_authenticated = isset($_SESSION['setup_authenticated']) && $_SESSION['setup_authenticated'] === true;

// Process logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_setup.php');
    exit;
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_password'])) {
    if ($_POST['setup_password'] === $SETUP_PASSWORD) {
        $_SESSION['setup_authenticated'] = true;
        header('Location: admin_setup.php');
        exit;
    } else {
        $login_error = 'Password salah!';
    }
}

// If not authenticated, show login form
if (!$is_authenticated) {
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Setup Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group label {
            font-weight: 600;
            color: #555;
        }
        .btn {
            width: 100%;
            padding: 10px;
            font-weight: 600;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2>🔐 Setup Diagnosa Admin</h2>
    
    <?php if (isset($login_error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="password">Password Setup:</label>
            <input 
                type="password" 
                id="password" 
                name="setup_password" 
                class="form-control" 
                placeholder="Masukkan password" 
                required 
                autofocus
            >
            <small class="text-muted d-block mt-2">
                Password default: <code>setup2024</code>
            </small>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px; color: #999;">
        <small>Setup & Diagnosa Admin System</small>
    </p>
</div>
</body>
</html>
    <?php
    exit;
}

// If authenticated, proceed with original content
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnosa & Setup Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container-diag { max-width: 800px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-err { color: #dc3545; font-weight: bold; }
        .status-warn { color: #ffc107; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .step { margin-bottom: 20px; padding: 15px; background: white; border-radius: 5px; border-left: 4px solid #007bff; }
        button { margin-top: 10px; }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-custom a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }
        .navbar-custom a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="navbar-custom">
    <h3 style="margin: 0;">🔧 Setup Diagnosa Admin</h3>
    <a href="?logout=1">Logout</a>
</div>

<div class="container-diag">

<?php

// Database credentials
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'db' => getenv('DB_NAME') ?: 'belajarinfotmati_pengumuman',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
];

$pdo = null;
$errors = [];

// ============ STEP 1: Database Connection ============
echo '<div class="step">';
echo '<h3>Step 1: Koneksi Database</h3>';
echo '<p><strong>Config:</strong></p>';
echo '<ul>';
echo '<li>Host: ' . htmlspecialchars($db_config['host']) . '</li>';
echo '<li>Database: ' . htmlspecialchars($db_config['db']) . '</li>';
echo '<li>User: ' . htmlspecialchars($db_config['user']) . '</li>';
echo '</ul>';

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['db']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo '<p class="status-ok">✓ Database berhasil terkoneksi</p>';
} catch (PDOException $e) {
    echo '<p class="status-err">✗ Koneksi database gagal!</p>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    $errors[] = 'Database connection';
    $pdo = null;
}
echo '</div>';

// ============ STEP 2: Check Admins Table ============
echo '<div class="step">';
echo '<h3>Step 2: Cek Tabel Admins</h3>';

if ($pdo) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'admins'");
        if ($result->rowCount() > 0) {
            echo '<p class="status-ok">✓ Tabel admins ditemukan</p>';
            
            // Show admins in table
            $stmt = $pdo->query("SELECT id, username FROM admins");
            $admins = $stmt->fetchAll();
            
            if (!empty($admins)) {
                echo '<p><strong>Admin yang terdaftar:</strong></p>';
                echo '<table class="table table-sm">';
                echo '<tr><th>ID</th><th>Username</th></tr>';
                foreach ($admins as $admin) {
                    echo '<tr><td>' . htmlspecialchars($admin['id']) . '</td><td>' . htmlspecialchars($admin['username']) . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="status-warn">⚠ Tabel admins kosong (tidak ada user)</p>';
                $errors[] = 'No admin user';
            }
        } else {
            echo '<p class="status-err">✗ Tabel admins TIDAK ditemukan</p>';
            echo '<p>Anda perlu import file SQL terlebih dahulu.</p>';
            $errors[] = 'Table not found';
        }
    } catch (Exception $e) {
        echo '<p class="status-err">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        $errors[] = 'Table check';
    }
} else {
    echo '<p class="status-err">⚠ Langkah ini membutuhkan database terkoneksi</p>';
}
echo '</div>';

// ============ STEP 3: Test Credentials ============
echo '<div class="step">';
echo '<h3>Step 3: Test Kredensial (admin / admin123)</h3>';

if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ?');
        $stmt->execute(['admin']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo '<p class="status-ok">✓ User "admin" ditemukan</p>';
            echo '<p><strong>Password Hash:</strong></p>';
            echo '<pre>' . htmlspecialchars($admin['password']) . '</pre>';
            
            // Test password
            $test_pass = 'admin123';
            if (password_verify($test_pass, $admin['password'])) {
                echo '<p class="status-ok">✓ Password "admin123" COCOK</p>';
                echo '<p style="color: green;"><strong>Anda bisa login sekarang!</strong></p>';
                echo '<p><a href="admin/login.php" class="btn btn-success">Ke Halaman Login</a></p>';
            } else {
                echo '<p class="status-err">✗ Password "admin123" TIDAK COCOK</p>';
                echo '<p>Password yang tersimpan adalah hash berbeda. Anda perlu reset password.</p>';
                $errors[] = 'Password mismatch';
            }
        } else {
            echo '<p class="status-err">✗ User "admin" TIDAK ditemukan</p>';
            $errors[] = 'Admin user not found';
        }
    } catch (Exception $e) {
        echo '<p class="status-err">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        $errors[] = 'Credential check';
    }
} else {
    echo '<p class="status-err">⚠ Langkah ini membutuhkan database terkoneksi</p>';
}
echo '</div>';

// ============ STEP 4: Session Test ============
echo '<div class="step">';
echo '<h3>Step 4: Cek Session</h3>';
echo '<p>Session Status: <span class="status-ok">' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . '</span></p>';
echo '<p>Session ID: <code>' . htmlspecialchars(session_id()) . '</code></p>';
echo '<p>Session Path: <code>' . htmlspecialchars(session_save_path()) . '</code></p>';
echo '</div>';

// ============ STEP 5: Auto Fix ============
if (!empty($errors)) {
    echo '<div class="step" style="border-left-color: #dc3545;">';
    echo '<h3>Step 5: ⚙️ Auto Fix</h3>';
    echo '<p>Ditemukan masalah: ' . implode(', ', $errors) . '</p>';
    
    // Button to fix issues
    if (in_array('No admin user', $errors) || in_array('Admin user not found', $errors)) {
        echo '<form method="POST">';
        echo '<button type="submit" name="action" value="create_admin" class="btn btn-warning">Buat/Reset Admin User</button>';
        echo '</form>';
    }
    
    if (in_array('Password mismatch', $errors)) {
        echo '<form method="POST">';
        echo '<button type="submit" name="action" value="reset_password" class="btn btn-danger">Reset Password ke admin123</button>';
        echo '</form>';
    }
}

// ============ Process Actions ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_admin') {
        try {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            
            // First try to update
            $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE username = ?');
            $stmt->execute([$hash, 'admin']);
            
            if ($stmt->rowCount() === 0) {
                // If no rows updated, insert new
                $stmt = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
                $stmt->execute(['admin', $hash]);
            }
            
            echo '<div class="alert alert-success">';
            echo '<strong>✓ Berhasil!</strong> User admin berhasil ditambahkan/diperbarui.';
            echo '<p>Password: admin123</p>';
            echo '<p><a href="admin/login.php" class="btn btn-primary">Login Sekarang</a></p>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">';
            echo '<strong>✗ Error: </strong>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } elseif ($action === 'reset_password') {
        try {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE username = ?');
            $stmt->execute([$hash, 'admin']);
            
            echo '<div class="alert alert-success">';
            echo '<strong>✓ Berhasil!</strong> Password admin direset ke admin123.';
            echo '<p><a href="admin/login.php" class="btn btn-primary">Login Sekarang</a></p>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">';
            echo '<strong>✗ Error: </strong>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    }
}

?>

    <hr>
    <p style="color: #666;">Created: Diagnosa Auto untuk Admin Login System</p>
</div>
</body>
</html>
