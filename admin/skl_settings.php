<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if (isset($_POST['action']) && $_POST['action'] === 'update_skl_link') {
    $message = admin_update_skl_settings($pdo, $context['hasSettings'], $_POST['skl_link'] ?? '', $_POST['skl_label'] ?? '');
    admin_redirect_with_message('skl_settings.php', $message);
}

$settings = admin_load_settings($pdo);
$logo = $settings['logo'];
$skl_link = $settings['skl_link'];
$skl_label = $settings['skl_label'];

admin_render_page_start('Link Download SKL', 'skl', $logo, $message);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pengaturan Link Download SKL</h3>
    </div>
    <div class="card-body">
        <form method="post" class="dashboard-content-width">
            <input type="hidden" name="action" value="update_skl_link">
            <div class="form-group">
                <label for="skl_label">Nama Tombol Download SKL</label>
                <input type="text" id="skl_label" name="skl_label" class="form-control" value="<?php echo htmlspecialchars($skl_label); ?>" placeholder="Download SKL.Pdf">
            </div>
            <div class="form-group">
                <label for="skl_link">Link Download SKL</label>
                <input type="url" id="skl_link" name="skl_link" class="form-control" value="<?php echo htmlspecialchars($skl_link); ?>" placeholder="https://example.com/skl.pdf">
            </div>
            <button type="submit" class="btn btn-primary">Simpan Link SKL</button>
        </form>
    </div>
</div>
<?php
admin_render_page_end();
