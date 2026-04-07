<?php
require_once '../config.php';

// If already logged in, jump straight to dashboard
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :u');
            $stmt->execute(['u' => $username]);
            $admin = $stmt->fetch();

            $isValid = false;

            if ($admin) {
                $stored = $admin['password'];

                // Support both hashed passwords (password_hash) and legacy plain text passwords.
                if (password_verify($password, $stored)) {
                    $isValid = true;

                    // Upgrade legacy hashes if needed (e.g. algorithm/cost changes)
                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $update = $pdo->prepare('UPDATE admins SET password = :p WHERE id = :id');
                        $update->execute(['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $admin['id']]);
                    }
                } elseif ($password === $stored) {
                    $isValid = true;

                    // Convert plain text password to a secure hash automatically.
                    $update = $pdo->prepare('UPDATE admins SET password = :p WHERE id = :id');
                    $update->execute(['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $admin['id']]);
                }
            } else {
                $error = 'Username tidak ditemukan.';
            }

            if ($isValid) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                // Verify session was set
                if (isset($_SESSION['admin_id'])) {
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Gagal menyimpan session. Cek pengaturan session PHP Anda.';
                }
            } elseif (!$error) {
                $error = 'Username atau password salah.';
            }
        } catch (\PDOException $e) {
            $error = 'Terjadi kesalahan database. Hubungi administrator.';
        } catch (\Throwable $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Aplikasi Kelulusan</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../style.css">
<?php
// apply background if set
if (function_exists('ensure_settings_table')) {
    ensure_settings_table($pdo);
} else {
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `value` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Throwable $e) {
        // Biarkan halaman login tetap tampil walau tabel settings belum bisa dibuat.
    }
}
$stmt = $pdo->query("SELECT value FROM settings WHERE name='background'");
$bg = $stmt->fetchColumn();
if ($bg) {
    echo "<style>body{background:url('../assets/".htmlspecialchars($bg)."') no-repeat center/cover;}</style>";
}
?>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>Admin</b>Kelulusan</a>
    </div>
    <!-- /.login-logo -->
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to start your session</p>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true" aria-label="Close">&times;</button>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember">
                            <label for="remember">
                                Remember Me
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>

            <p style="margin-top: 20px; text-align: center;">
                <small>
                    <a href="../admin_setup.php">Setup / Diagnosa Admin</a>
                </small>
            </p>
        </div>
        <!-- /.login-card-body -->
    </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
