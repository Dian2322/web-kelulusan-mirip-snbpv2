<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $message = admin_change_password($pdo, $context['currentAdminId'], $_POST);
    admin_redirect_with_message('password_settings.php', $message);
}

$settings = admin_load_settings($pdo);

admin_render_page_start('Ubah Kata Sandi Admin', 'password', $settings['logo'], $message);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ubah Kata Sandi Admin</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-content-width">
            <form method="post">
                <input type="hidden" name="action" value="change_password">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="current_password">Kata Sandi Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="new_password">Kata Sandi Baru</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Simpan Kata Sandi
                </button>
            </form>
        </div>
    </div>
</div>
<?php
admin_render_page_end();
