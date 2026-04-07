<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if (isset($_POST['time'])) {
    $message = admin_update_announcement_time($pdo, $context['hasSettings'], $_POST['time']);
    admin_redirect_with_message('announcement_settings.php', $message);
}

$settings = admin_load_settings($pdo);
$logo = $settings['logo'];
$announcement_time = $settings['announcement_time'];

admin_render_page_start('Waktu Pengumuman', 'announcement', $logo, $message);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pengaturan Waktu Pengumuman</h3>
    </div>
    <div class="card-body">
        <form method="post" class="announcement-time-form">
            <div class="form-group">
                <label for="time">Waktu Pengumuman:</label>
                <input type="datetime-local" id="time" name="time" class="form-control" value="<?php echo htmlspecialchars($announcement_time); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>
<?php
admin_render_page_end();
