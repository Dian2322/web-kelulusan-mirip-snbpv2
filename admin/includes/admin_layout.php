<?php
function admin_render_page_start($pageTitle, $activeMenu, $logo, $message = '') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'href' => 'dashboard.php'],
        'announcement' => ['label' => 'Waktu Pengumuman', 'icon' => 'fas fa-clock', 'href' => 'announcement_settings.php'],
        'result-info' => ['label' => 'Info Tambahan Hasil', 'icon' => 'fas fa-circle-info', 'href' => 'result_info_settings.php'],
        'skl' => ['label' => 'Link SKL', 'icon' => 'fas fa-link', 'href' => 'skl_settings.php'],
        'add-student' => ['label' => 'Tambah Siswa', 'icon' => 'fas fa-user-plus', 'href' => 'students_add.php'],
        'bulk-student' => ['label' => 'Tambah Bulk', 'icon' => 'fas fa-file-upload', 'href' => 'students_bulk.php'],
        'student-photo' => ['label' => 'Upload Foto Siswa', 'icon' => 'fas fa-camera', 'href' => 'student_photos.php'],
        'logo' => ['label' => 'Pengaturan Logo', 'icon' => 'fas fa-image', 'href' => 'logo_settings.php'],
        'password' => ['label' => 'Kata Sandi', 'icon' => 'fas fa-key', 'href' => 'password_settings.php'],
        'students' => ['label' => 'Data Siswa', 'icon' => 'fas fa-table', 'href' => 'students.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Aplikasi Kelulusan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        :root {
            --admin-footer-space: 96px;
            --admin-footer-gap: 16px;
        }
        html, body {
            height: auto;
            min-height: 100%;
        }
        body.hold-transition.sidebar-mini.layout-fixed {
            height: auto !important;
            min-height: 100vh;
            overflow-y: auto !important;
            overflow-x: hidden;
        }
        .wrapper {
            min-height: 100vh;
            background: #f4f6f9;
            overflow: visible;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper {
            background: #f4f6f9;
            padding-bottom: calc(var(--admin-footer-space) + var(--admin-footer-gap));
            min-height: calc(100vh - 114px);
            overflow: visible;
            flex: 1 0 auto;
        }
        .content {
            padding: .75rem .5rem 1.5rem;
        }
        .main-footer {
            border-top: 1px solid rgba(0,0,0,.1);
            margin-top: auto;
            flex-shrink: 0;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1035;
            background: #fff;
        }
        .card {
            overflow: hidden;
            transition: transform .2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-1px);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .dashboard-link-card {
            display: block;
            padding: 18px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #eef4ff 100%);
            border: 1px solid #dbe6ff;
            color: #1d3557;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(39, 64, 112, 0.08);
        }
        .dashboard-link-card:hover {
            color: #10284b;
            text-decoration: none;
            box-shadow: 0 12px 26px rgba(39, 64, 112, 0.14);
        }
        .dashboard-link-card i {
            font-size: 1.2rem;
            margin-bottom: 12px;
            display: inline-block;
        }
        .dashboard-link-card-title {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .dashboard-link-card-text {
            display: block;
            color: #5d6b82;
            font-size: .92rem;
            line-height: 1.45;
        }
        .dashboard-content-width {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }
        .dashboard-table-width {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }
        .student-table-container {
            width: 100%;
            overflow-x: auto;
            position: relative;
        }
        .student-table-container table {
            width: 100% !important;
            table-layout: auto;
            min-width: 1200px;
        }
        .table-action-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: .75rem;
        }
        .announcement-time-form {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            width: 100%;
        }
        .announcement-time-form .form-group {
            flex: 1 1 320px;
            margin-bottom: 0;
        }
        .announcement-time-form .btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .icon-delete-btn {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: .95rem;
        }
        .bulk-textarea {
            width: 100%;
            min-height: 300px;
            resize: vertical;
        }
        .table th, .table td {
            color: #000 !important;
        }
        .mobile-menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 768px) {
            html, body {
                height: auto !important;
                min-height: 100%;
            }
            body.hold-transition.sidebar-mini.layout-fixed {
                overflow-y: auto !important;
                overflow-x: hidden !important;
            }
            .content-wrapper {
                min-height: auto;
                overflow: visible !important;
            }
            .main-footer {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                white-space: normal;
                text-align: center;
                padding: 12px 14px;
                line-height: 1.5;
            }
            .main-footer .float-right {
                float: none !important;
                display: block;
                margin-top: 6px;
                text-align: center;
            }
            .card-body {
                padding: .75rem;
            }
            .row .form-group {
                margin-bottom: .75rem;
            }
            .mobile-menu-toggle {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                background: #f2f6ff;
                color: #1b3f72 !important;
            }
        }
        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .announcement-time-form {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .announcement-time-form .form-group,
            .announcement-time-form .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link mobile-menu-toggle" data-widget="pushmenu" href="#" role="button" aria-label="Buka menu samping"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="dashboard.php" class="brand-link">
                <?php $logoPath = dirname(__DIR__, 2) . '/assets/' . basename($logo); ?>
                <?php if (file_exists($logoPath)): ?>
                    <img src="../assets/<?php echo htmlspecialchars($logo); ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
                <?php else: ?>
                    <span class="brand-text font-weight-light">Logo</span>
                <?php endif; ?>
                <span class="brand-text font-weight-light">Kelulusan</span>
            </a>

            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <i class="fas fa-user-circle fa-2x text-white"></i>
                    </div>
                    <div class="info">
                        <a href="#" class="d-block">Administrator</a>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <?php foreach ($menuItems as $key => $item): ?>
                            <li class="nav-item">
                                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="nav-link<?php echo $activeMenu === $key ? ' active' : ''; ?>">
                                    <i class="nav-icon <?php echo htmlspecialchars($item['icon']); ?>"></i>
                                    <p><?php echo htmlspecialchars($item['label']); ?></p>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <?php if ($message !== ''): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true" aria-label="Close">&times;</button>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
<?php
}

function admin_render_page_end($extraScript = '') {
    ?>
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <strong>&copy; 2024 <a href="#">Aplikasi Kelulusan</a>.</strong> All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.0.0
            </div>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <?php if ($extraScript !== ''): ?>
<?php echo $extraScript; ?>
    <?php endif; ?>
</body>
</html>
<?php
}
