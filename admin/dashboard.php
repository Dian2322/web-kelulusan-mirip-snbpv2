<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$settings = admin_load_settings($pdo);
$logo = $settings['logo'];
$message = '';

admin_render_page_start('Dashboard Admin', 'dashboard', $logo, $message);
?>
<style>
    .dashboard-scroll-page {
        max-height: calc(100vh - 140px);
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        padding-bottom: calc(var(--admin-footer-space) + 16px);
    }
    .dashboard-menu-card .card-body {
        padding: 1rem;
    }
    .dashboard-grid {
        align-items: stretch;
    }
    .dashboard-link-card {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 14px;
        min-height: 168px;
        padding: 20px;
    }
    .dashboard-link-card i {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: linear-gradient(135deg, #dce9ff 0%, #edf4ff 100%);
        color: #19407a;
        margin-bottom: 0;
        flex-shrink: 0;
    }
    .dashboard-link-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
    }
    .dashboard-link-card-title {
        line-height: 1.35;
    }
    .dashboard-link-card-text {
        margin-top: 4px;
    }
    @media (max-width: 768px) {
        body.hold-transition.sidebar-mini.layout-fixed {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch;
        }
        .wrapper {
            min-height: auto;
        }
        .content-wrapper {
            min-height: auto !important;
            overflow-y: hidden !important;
            overflow-x: hidden !important;
            padding-bottom: 24px !important;
        }
        .content {
            padding-bottom: 24px !important;
        }
        .dashboard-scroll-page {
            max-height: calc(100vh - 170px);
        }
        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .dashboard-link-card {
            min-height: auto;
            padding: 16px;
            border-radius: 14px;
        }
        .dashboard-link-card i {
            width: 44px;
            height: 44px;
            margin-bottom: 12px;
        }
        .dashboard-link-card-title {
            font-size: 0.98rem;
        }
        .dashboard-link-card-text {
            font-size: 0.88rem;
            line-height: 1.4;
        }
    }
    @media (max-width: 480px) {
        .dashboard-scroll-page {
            max-height: calc(100vh - 180px);
        }
        .dashboard-menu-card .card-body {
            padding: 0.75rem;
        }
        .dashboard-link-card {
            padding: 14px;
        }
        .dashboard-link-card i {
            width: 40px;
            height: 40px;
            border-radius: 12px;
        }
        .dashboard-link-card-title {
            font-size: 0.94rem;
        }
    }
</style>
<div class="dashboard-scroll-page">
    <div class="card dashboard-menu-card">
        <div class="card-header">
            <h3 class="card-title">Menu Dashboard</h3>
        </div>
        <div class="card-body">
            <div class="dashboard-grid">
                <a href="announcement_settings.php" class="dashboard-link-card">
                    <i class="fas fa-clock"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Waktu Pengumuman</span>
                        <span class="dashboard-link-card-text">Atur jadwal pengumuman kelulusan yang tampil di halaman utama.</span>
                    </span>
                </a>
                <a href="result_info_settings.php" class="dashboard-link-card">
                    <i class="fas fa-circle-info"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Info Tambahan Hasil</span>
                        <span class="dashboard-link-card-text">Kelola teks informasi tambahan yang tampil di bawah kartu hasil pengumuman.</span>
                    </span>
                </a>
                <a href="skl_settings.php" class="dashboard-link-card">
                    <i class="fas fa-link"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Link SKL</span>
                        <span class="dashboard-link-card-text">Kelola label tombol dan tautan unduhan SKL untuk siswa.</span>
                    </span>
                </a>
                <a href="students_add.php" class="dashboard-link-card">
                    <i class="fas fa-user-plus"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Tambah Siswa</span>
                        <span class="dashboard-link-card-text">Masukkan data siswa satu per satu dengan form manual.</span>
                    </span>
                </a>
                <a href="students_bulk.php" class="dashboard-link-card">
                    <i class="fas fa-file-upload"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Tambah Bulk</span>
                        <span class="dashboard-link-card-text">Input banyak siswa sekaligus melalui format CSV per baris.</span>
                    </span>
                </a>
                <a href="student_photos.php" class="dashboard-link-card">
                    <i class="fas fa-camera"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Upload Foto Siswa</span>
                        <span class="dashboard-link-card-text">Unggah dan ganti foto masing-masing siswa dari halaman khusus.</span>
                    </span>
                </a>
                <a href="logo_settings.php" class="dashboard-link-card">
                    <i class="fas fa-image"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Pengaturan Logo</span>
                        <span class="dashboard-link-card-text">Upload atau pilih nama file logo yang dipakai aplikasi.</span>
                    </span>
                </a>
                <a href="password_settings.php" class="dashboard-link-card">
                    <i class="fas fa-key"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Kata Sandi</span>
                        <span class="dashboard-link-card-text">Ubah kata sandi admin dengan aman dari halaman terpisah.</span>
                    </span>
                </a>
                <a href="students.php" class="dashboard-link-card">
                    <i class="fas fa-table"></i>
                    <span class="dashboard-link-content">
                        <span class="dashboard-link-card-title">Data Siswa</span>
                        <span class="dashboard-link-card-text">Lihat, edit, dan hapus data siswa dari tabel utama.</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>
<?php
admin_render_page_end();
