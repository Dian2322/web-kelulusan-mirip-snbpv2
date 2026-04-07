<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = admin_update_student_photo($pdo, $context['photoCol'], $_POST, $_FILES);
    admin_redirect_with_message('student_photos.php', $message);
}

$settings = admin_load_settings($pdo);
$students = admin_load_students($pdo, $context['regCol'], $context['dobCol'], $context['photoCol']);
$idLabel = $context['regCol'] === 'registration_number' ? 'No. Pendaftaran' : 'NISN';
$photoCol = $context['photoCol'];
$regCol = $context['regCol'];

admin_render_page_start('Upload Foto Siswa', 'student-photo', $settings['logo'], $message);
?>
<style>
    .photo-upload-scroll {
        max-height: calc(100vh - 260px - var(--admin-footer-space) - var(--admin-footer-gap));
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 12px;
        padding-bottom: 8px;
    }
    .photo-upload-scroll table {
        min-width: 760px !important;
    }
    .photo-upload-mobile {
        display: none;
    }
    .photo-upload-mobile-list {
        display: block;
    }
    .photo-upload-card {
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        padding: 14px;
        background: #fff;
        box-shadow: 0 8px 18px rgba(31, 59, 109, 0.08);
    }
    .photo-upload-card + .photo-upload-card {
        margin-top: 14px;
    }
    .photo-upload-card-head {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 12px;
    }
    .photo-upload-card-image {
        width: 72px;
        height: 72px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #dbe3ef;
        background: #f3f6fb;
        flex-shrink: 0;
    }
    .photo-upload-card-id {
        font-size: .82rem;
        font-weight: 700;
        color: #46648f;
        margin-bottom: 2px;
    }
    .photo-upload-card-name {
        font-size: 1rem;
        font-weight: 700;
        color: #1a2f52;
        line-height: 1.35;
    }
    .photo-upload-card-form .form-group {
        margin-bottom: 10px;
    }
    .photo-upload-card-form .btn {
        width: 100%;
    }
    @media (max-width: 768px) {
        .photo-upload-scroll {
            max-height: calc(100vh - 220px - var(--admin-footer-space) - var(--admin-footer-gap));
        }
    }
    @media (max-width: 576px) {
        .photo-upload-scroll {
            display: none;
        }
        .photo-upload-mobile {
            display: block;
            max-height: calc(100vh - 210px - var(--admin-footer-space) - var(--admin-footer-gap));
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding-right: 2px;
            padding-bottom: 16px;
        }
        .photo-upload-mobile-list {
            padding-bottom: 8px;
        }
    }
</style>
<div class="card">
    <div class="card-body">
        <div class="student-table-container photo-upload-scroll">
            <table class="table table-bordered table-striped" style="width:100%;">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars($idLabel); ?></th>
                        <th>Nama</th>
                        <th>Foto Saat Ini</th>
                        <th>Upload Foto Baru</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(($regCol !== null && isset($student[$regCol])) ? $student[$regCol] : $student['id']); ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td style="min-width:120px;">
                                <?php if ($photoCol !== null && !empty($student[$photoCol])): ?>
                                    <img src="../assets/students/<?php echo htmlspecialchars($student[$photoCol]); ?>" alt="Foto siswa" style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:1px solid #dbe3ef;">
                                <?php else: ?>
                                    <span class="text-muted">Belum ada foto</span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width:280px;">
                                <form method="post" enctype="multipart/form-data" class="d-block mb-0">
                                    <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
                                    <div class="form-group mb-2">
                                        <input type="file" name="student_photo" class="form-control-file" accept=".jpg,.jpeg,.png,.webp" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-upload"></i> Upload Foto
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="photo-upload-mobile">
            <div class="photo-upload-mobile-list">
                <?php foreach ($students as $student): ?>
                    <div class="photo-upload-card">
                        <div class="photo-upload-card-head">
                            <?php if ($photoCol !== null && !empty($student[$photoCol])): ?>
                                <img src="../assets/students/<?php echo htmlspecialchars($student[$photoCol]); ?>" alt="Foto siswa" class="photo-upload-card-image">
                            <?php else: ?>
                                <div class="photo-upload-card-image d-flex align-items-center justify-content-center text-muted">Foto</div>
                            <?php endif; ?>
                            <div>
                                <div class="photo-upload-card-id"><?php echo htmlspecialchars($idLabel); ?>: <?php echo htmlspecialchars(($regCol !== null && isset($student[$regCol])) ? $student[$regCol] : $student['id']); ?></div>
                                <div class="photo-upload-card-name"><?php echo htmlspecialchars($student['name']); ?></div>
                            </div>
                        </div>
                        <form method="post" enctype="multipart/form-data" class="photo-upload-card-form">
                            <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
                            <div class="form-group mb-2">
                                <input type="file" name="student_photo" class="form-control-file" accept=".jpg,.jpeg,.png,.webp" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-upload"></i> Upload Foto
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
admin_render_page_end();
