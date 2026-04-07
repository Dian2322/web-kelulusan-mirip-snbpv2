<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if ((isset($_FILES['logofile']) && isset($_FILES['logofile']['name']) && $_FILES['logofile']['name'] !== '') || isset($_POST['logoname'])) {
    $message = admin_update_logo($pdo, $context['hasSettings'], $_FILES, $_POST);
    admin_redirect_with_message('logo_settings.php', $message);
}

$settings = admin_load_settings($pdo);
$logo = $settings['logo'];

admin_render_page_start('Pengaturan Logo', 'logo', $logo, $message);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pengaturan Logo</h3>
    </div>
    <div class="card-body">
        <?php if ($logo): ?>
            <p>Logo saat ini: <strong><?php echo htmlspecialchars($logo); ?></strong></p>
            <div class="mb-3" style="max-width:200px;">
                <img id="currentLogo" src="../assets/<?php echo htmlspecialchars($logo); ?>" class="img-fluid" alt="Logo saat ini">
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Tidak ada logo yang terpasang saat ini.</div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="logofile">Upload Logo Baru (PNG, max 2MB):</label>
                <input type="file" id="logofile" name="logofile" accept="image/png" class="form-control-file">
                <small class="form-text text-muted">File harus PNG, maksimum 2MB.</small>
            </div>

            <div id="previewWrapper" class="mb-3" style="display:none; max-width:200px;">
                <p class="mb-2"><strong>Preview logo baru:</strong></p>
                <img id="logoPreview" src="#" class="img-fluid border" alt="Preview Logo">
            </div>

            <p class="text-muted">atau pilih logo PNG yang sudah ada di folder <code>assets/</code>:</p>
            <div class="form-group">
                <input type="text" name="logoname" class="form-control" value="<?php echo htmlspecialchars($logo); ?>" placeholder="logo.png">
                <small class="form-text text-muted">Contoh: <code>logo.png</code> atau <code>school-logo.png</code></small>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Logo</button>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var fileInput = document.getElementById('logofile');
            var previewWrapper = document.getElementById('previewWrapper');
            var previewImage = document.getElementById('logoPreview');
            var logonameInput = document.querySelector('input[name="logoname"]');

            fileInput.addEventListener('change', function () {
                var file = this.files[0];
                if (!file) {
                    previewWrapper.style.display = 'none';
                    previewImage.src = '#';
                    return;
                }

                if (file.type !== 'image/png') {
                    alert('Hanya file PNG yang diizinkan untuk preview.');
                    this.value = '';
                    previewWrapper.style.display = 'none';
                    previewImage.src = '#';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran logo maksimal 2MB. Pilih file PNG yang lebih kecil.');
                    this.value = '';
                    previewWrapper.style.display = 'none';
                    previewImage.src = '#';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImage.src = e.target.result;
                    previewWrapper.style.display = 'block';
                };
                reader.readAsDataURL(file);

                // Clear manual filename when file selected
                if (logonameInput) {
                    logonameInput.value = '';
                }
            });
        });
    </script>
</div>
<?php
admin_render_page_end();
