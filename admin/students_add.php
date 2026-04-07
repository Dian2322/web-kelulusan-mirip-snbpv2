<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if (isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $message = admin_add_manual_student($pdo, $context['regCol'], $context['dobCol'], $_POST);
    admin_redirect_with_message('students_add.php', $message);
}

$settings = admin_load_settings($pdo);
$predicates = admin_load_predicates($pdo);

admin_render_page_start('Tambah Siswa Manual', 'add-student', $settings['logo'], $message);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tambah Siswa Manual</h3>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="action" value="add_manual">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="nisn_manual">NISN / No. Pendaftaran</label>
                        <input type="text" id="nisn_manual" name="nisn" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="name_manual">Nama</label>
                        <input type="text" id="name_manual" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="dob_manual">Tanggal Lahir</label>
                        <input type="date" id="dob_manual" name="birth_date" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status_manual">Status Kelulusan</label>
                        <select id="status_manual" name="status" class="form-control" required>
                            <option value="Lulus">Lulus</option>
                            <option value="Tidak Lulus">Tidak Lulus</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="predikat_manual">Predikat</label>
                        <select id="predikat_manual" name="predikat_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($predicates as $predicate): ?>
                                <option value="<?php echo (int)$predicate['id']; ?>"><?php echo htmlspecialchars($predicate['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Siswa
            </button>
        </form>
    </div>
</div>
<?php
admin_render_page_end();
