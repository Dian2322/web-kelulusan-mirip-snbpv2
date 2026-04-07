<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if (isset($_POST['action']) && $_POST['action'] === 'add_bulk') {
    $message = admin_add_bulk_students($pdo, $context['regCol'], $context['dobCol'], $_POST['bulk_data'] ?? '');
    admin_redirect_with_message('students_bulk.php', $message);
}

$settings = admin_load_settings($pdo);

admin_render_page_start('Tambah Banyak Siswa', 'bulk-student', $settings['logo'], $message);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tambah Banyak Siswa (Bulk)</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-content-width">
            <p class="text-muted">Masukkan data setiap baris format CSV: <code>nisn,nama,tanggal_lahir,status,predikat_id</code>.</p>
            <form method="post">
                <input type="hidden" name="action" value="add_bulk">
                <div class="form-group">
                    <textarea name="bulk_data" class="form-control bulk-textarea" rows="15" cols="80" placeholder="20260020,John Doe,2006-01-15,Lulus,2&#10;20260021,Jane Doe,2006-01-16,Tidak Lulus,3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Simpan Semua
                </button>
            </form>
        </div>
    </div>
</div>
<?php
admin_render_page_end();
