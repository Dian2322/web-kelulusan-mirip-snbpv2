<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_admin();

if (!function_exists('columnExists')) {
    function columnExists($pdo, $table, $column) {
        try {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
            if ($table === '') {
                return false;
            }
            $sql = "SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote((string)$column);
            $stmt = $pdo->query($sql);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('tableExists')) {
    function tableExists($pdo, $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('ensureSettingsTableSafe')) {
    function ensureSettingsTableSafe(PDO $pdo) {
        if (function_exists('ensure_settings_table')) {
            return ensure_settings_table($pdo);
        }

        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `settings` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(50) NOT NULL UNIQUE,
                    `value` TEXT DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            try {
                $pdo->exec("ALTER TABLE `settings` MODIFY COLUMN `value` TEXT DEFAULT NULL");
            } catch (Throwable $e) {
                // Keep existing schema if ALTER is unsupported.
            }

            $defaults = [
                'announcement_time' => '',
                'logo' => 'logo.png',
                'background' => '',
                'skl_link' => '',
                'skl_label' => 'Download SKL.Pdf',
                'result_info_note' => '',
                'result_info_note_color' => '#f5f8ff',
                'result_info_note_icon' => 'fas fa-circle-info',
                'result_info_items' => '[]'
            ];

            $stmt = $pdo->prepare(
                "INSERT INTO settings (name, value)
                 VALUES (:name, :value)
                 ON DUPLICATE KEY UPDATE value = COALESCE(value, VALUES(value))"
            );

            foreach ($defaults as $name => $value) {
                $stmt->execute([
                    'name' => $name,
                    'value' => $value
                ]);
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

function admin_sanitize_result_info_note_html($html) {
    $html = (string)$html;
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s+style\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s+href\s*=\s*("javascript:.*?"|\'javascript:.*?\'|javascript:[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s+src\s*=\s*("javascript:.*?"|\'javascript:.*?\'|javascript:[^\s>]+)/i', '', $html);
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><span><img>');
    $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
    return trim($html);
}

function admin_allowed_result_info_icons() {
    return [
        'fas fa-circle-info',
        'fas fa-bullhorn',
        'fas fa-triangle-exclamation',
        'fas fa-bell',
        'fas fa-circle-check',
        'fas fa-book-open',
        'fas fa-clipboard-list',
        'fas fa-graduation-cap'
    ];
}

function admin_normalize_result_info_icon($icon) {
    $icon = trim((string)$icon);
    return in_array($icon, admin_allowed_result_info_icons(), true) ? $icon : 'fas fa-circle-info';
}

function admin_normalize_result_info_color($color) {
    $color = trim((string)$color);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#f5f8ff';
}

function admin_take_flash_message($key = 'admin_flash_message') {
    $message = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return $message;
}

function admin_redirect_with_message($location, $message, $key = 'admin_flash_message') {
    $_SESSION[$key] = (string)$message;
    header('Location: ' . $location);
    exit;
}

function admin_get_base_context(PDO $pdo) {
    $regCol = columnExists($pdo, 'students', 'nisn') ? 'nisn' : (columnExists($pdo, 'students', 'registration_number') ? 'registration_number' : null);
    $dobCol = columnExists($pdo, 'students', 'birth_date') ? 'birth_date' : (columnExists($pdo, 'students', 'date_of_birth') ? 'date_of_birth' : null);
    $photoCol = admin_ensure_student_photo_column($pdo) ? 'photo' : null;

    return [
        'regCol' => $regCol,
        'dobCol' => $dobCol,
        'photoCol' => $photoCol,
        'hasStatus' => columnExists($pdo, 'students', 'status'),
        'hasPredikat' => columnExists($pdo, 'students', 'predikat_id'),
        'hasSettings' => ensureSettingsTableSafe($pdo) || tableExists($pdo, 'settings'),
        'currentAdminId' => (int)($_SESSION['admin_id'] ?? 0),
    ];
}

function admin_ensure_student_photo_column(PDO $pdo) {
    if (columnExists($pdo, 'students', 'photo')) {
        return true;
    }

    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER predikat_id");
        return true;
    } catch (Throwable $e) {
        return columnExists($pdo, 'students', 'photo');
    }
}

function admin_load_settings(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT name, value FROM settings WHERE name IN ('announcement_time', 'logo', 'skl_link', 'skl_label', 'result_info_note', 'result_info_note_color', 'result_info_note_icon', 'result_info_items')");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $settings = [];
    }

    $logo = !empty($settings['logo']) ? $settings['logo'] : 'logo.png';
    $logoPath = dirname(__DIR__, 2) . '/assets/' . basename($logo);
    if (!file_exists($logoPath)) {
        $logo = 'logo.png';
    }

    $announcementTime = $settings['announcement_time'] ?? '';
    if ($announcementTime) {
        try {
            $dt = new DateTime($announcementTime);
            $announcementTime = $dt->format('Y-m-d\TH:i');
        } catch (Exception $e) {
            $announcementTime = '';
        }
    }

    return [
        'announcement_time' => $announcementTime,
        'logo' => $logo,
        'skl_link' => $settings['skl_link'] ?? '',
        'skl_label' => !empty($settings['skl_label']) ? $settings['skl_label'] : 'Download SKL.Pdf',
        'result_info_note' => $settings['result_info_note'] ?? '',
        'result_info_note_color' => !empty($settings['result_info_note_color']) ? $settings['result_info_note_color'] : '#f5f8ff',
        'result_info_note_icon' => !empty($settings['result_info_note_icon']) ? $settings['result_info_note_icon'] : 'fas fa-circle-info',
        'result_info_items' => $settings['result_info_items'] ?? '[]',
    ];
}

function admin_load_predicates(PDO $pdo) {
    try {
        return $pdo->query('SELECT id, name FROM predikat ORDER BY name')->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function admin_load_students(PDO $pdo, $regCol, $dobCol, $photoCol = null) {
    try {
        $selectFields = ['s.id', 's.name', 's.status', 's.predikat_id', 'p.name as predikat_name'];
        if (!empty($regCol)) {
            $selectFields[] = 's.' . $regCol;
        }
        if (!empty($dobCol)) {
            $selectFields[] = 's.' . $dobCol;
        }
        if (!empty($photoCol)) {
            $selectFields[] = 's.' . $photoCol;
        }
        $selectSql = implode(', ', $selectFields);
        return $pdo->query("SELECT $selectSql FROM students s LEFT JOIN predikat p ON s.predikat_id = p.id ORDER BY s.id")->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function admin_count_students(PDO $pdo) {
    try {
        $result = $pdo->query("SELECT COUNT(*) as total FROM students")->fetch();
        return (int)$result['total'];
    } catch (Exception $e) {
        return 0;
    }
}

function admin_load_students_paginated(PDO $pdo, $regCol, $dobCol, $page = 1, $perPage = 10, $photoCol = null) {
    try {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $selectFields = ['s.id', 's.name', 's.status', 's.predikat_id', 'p.name as predikat_name'];
        if (!empty($regCol)) {
            $selectFields[] = 's.' . $regCol;
        }
        if (!empty($dobCol)) {
            $selectFields[] = 's.' . $dobCol;
        }
        if (!empty($photoCol)) {
            $selectFields[] = 's.' . $photoCol;
        }
        $selectSql = implode(', ', $selectFields);
        $sql = "SELECT $selectSql FROM students s LEFT JOIN predikat p ON s.predikat_id = p.id ORDER BY s.id LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function admin_update_announcement_time(PDO $pdo, $hasSettings, $rawTime) {
    $time = str_replace('T', ' ', (string)$rawTime);
    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $stmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('announcement_time', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $stmt->execute(['v' => $time]);
    return 'Waktu pengumuman diperbarui.';
}

function admin_update_result_info_note(PDO $pdo, $hasSettings, $rawNote) {
    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $note = admin_sanitize_result_info_note_html($rawNote);
    $stmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('result_info_note', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $stmt->execute(['v' => $note]);
    return 'Informasi tambahan hasil pengumuman diperbarui.';
}

function admin_load_result_info_items(array $settings) {
    $items = json_decode((string)($settings['result_info_items'] ?? '[]'), true);
    $normalized = [];

    if (is_array($items)) {
        foreach ($items as $item) {
            $html = admin_sanitize_result_info_note_html($item['text'] ?? '');
            if ($html === '') {
                continue;
            }
            $normalized[] = [
                'text' => $html,
                'color' => admin_normalize_result_info_color($item['color'] ?? '#f5f8ff'),
                'icon' => admin_normalize_result_info_icon($item['icon'] ?? 'fas fa-circle-info'),
            ];
        }
    }

    if ($normalized === []) {
        $legacyHtml = admin_sanitize_result_info_note_html($settings['result_info_note'] ?? '');
        if ($legacyHtml !== '') {
            $normalized[] = [
                'text' => $legacyHtml,
                'color' => admin_normalize_result_info_color($settings['result_info_note_color'] ?? '#f5f8ff'),
                'icon' => admin_normalize_result_info_icon($settings['result_info_note_icon'] ?? 'fas fa-circle-info'),
            ];
        }
    }

    return $normalized;
}

function admin_save_result_info_items(PDO $pdo, $hasSettings, array $items) {
    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $payload = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('result_info_items', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $stmt->execute(['v' => $payload]);

    // Clear legacy single-item fallback once the JSON-based items are managed,
    // so old information does not keep reappearing as "Informasi 1".
    $clearLegacyStmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES (:name, :value)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $clearLegacyStmt->execute([
        'name' => 'result_info_note',
        'value' => ''
    ]);

    return 'Informasi tambahan hasil pengumuman diperbarui.';
}

function admin_add_result_info_item(PDO $pdo, $hasSettings, array $settings, array $post) {
    $items = admin_load_result_info_items($settings);
    $html = admin_sanitize_result_info_note_html($post['result_info_note'] ?? '');
    if ($html === '') {
        return 'Teks informasi tidak boleh kosong.';
    }

    $items[] = [
        'text' => $html,
        'color' => admin_normalize_result_info_color($post['result_info_note_color'] ?? '#f5f8ff'),
        'icon' => admin_normalize_result_info_icon($post['result_info_note_icon'] ?? 'fas fa-circle-info'),
    ];

    return admin_save_result_info_items($pdo, $hasSettings, $items);
}

function admin_delete_result_info_item(PDO $pdo, $hasSettings, array $settings, $index) {
    $items = admin_load_result_info_items($settings);
    $index = (int)$index;
    if (!isset($items[$index])) {
        return 'Item informasi tidak ditemukan.';
    }
    array_splice($items, $index, 1);
    return admin_save_result_info_items($pdo, $hasSettings, $items);
}

function admin_update_result_info_note_style(PDO $pdo, $hasSettings, $rawColor) {
    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $color = admin_normalize_result_info_color($rawColor);

    $stmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('result_info_note_color', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $stmt->execute(['v' => $color]);
    return 'Warna teks informasi hasil pengumuman diperbarui.';
}

function admin_update_result_info_note_icon(PDO $pdo, $hasSettings, $rawIcon) {
    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $icon = admin_normalize_result_info_icon($rawIcon);

    $stmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('result_info_note_icon', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $stmt->execute(['v' => $icon]);
    return 'Ikon informasi hasil pengumuman diperbarui.';
}

function admin_update_skl_settings(PDO $pdo, $hasSettings, $sklLink, $sklLabel) {
    $sklLink = trim((string)$sklLink);
    $sklLabel = trim((string)$sklLabel);
    if ($sklLabel === '') {
        $sklLabel = 'Download SKL.Pdf';
    }

    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $linkStmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('skl_link', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $labelStmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('skl_label', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $linkStmt->execute(['v' => $sklLink]);
    $labelStmt->execute(['v' => $sklLabel]);
    return 'Pengaturan Download SKL diperbarui.';
}

function admin_update_logo(PDO $pdo, $hasSettings, array $files, array $post) {
    if (isset($files['logofile']) && $files['logofile']['error'] === UPLOAD_ERR_OK) {
        $tmp = $files['logofile']['tmp_name'];
        $name = basename($files['logofile']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowedExt = 'png';
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($ext !== $allowedExt) {
            return 'Error: Hanya file PNG yang diizinkan.';
        }
        if ($files['logofile']['size'] > $maxSize) {
            return 'Error: Ukuran logo maksimal 2MB.';
        }

        $mime = mime_content_type($tmp);
        if ($mime !== 'image/png') {
            return 'Error: File harus berupa PNG yang valid.';
        }

        $name = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $name);
        $dest = dirname(__DIR__, 2) . '/assets/' . $name;
        if (!file_exists(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        if (!move_uploaded_file($tmp, $dest)) {
            return 'Error: Gagal mengupload file.';
        }

        if (!$hasSettings) {
            return 'File logo terupload, tapi tabel settings belum tersedia.';
        }

        $stmt = $pdo->prepare(
            "INSERT INTO settings (name, value)
             VALUES ('logo', :v)
             ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $stmt->execute(['v' => $name]);
        return 'Logo diperbarui.';
    }

    $name = trim((string)($post['logoname'] ?? ''));
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($name === '' || $ext !== 'png') {
        return 'Error: Nama file harus PNG dan tidak boleh kosong.';
    }

    $sanitized = basename($name);
    if ($sanitized !== $name || preg_match('/[^A-Za-z0-9_\-.]/', $name)) {
        return 'Error: Nama file tidak valid.';
    }

    $assetPath = dirname(__DIR__, 2) . '/assets/' . $name;
    if (!file_exists($assetPath)) {
        return 'Error: File logo tidak ditemukan di folder assets.';
    }

    if (!$hasSettings) {
        return 'Tabel settings belum tersedia.';
    }

    $stmt = $pdo->prepare(
        "INSERT INTO settings (name, value)
         VALUES ('logo', :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $stmt->execute(['v' => $name]);
    return 'Logo diperbarui.';
}

function admin_change_password(PDO $pdo, $currentAdminId, array $post) {
    $currentPassword = $post['current_password'] ?? '';
    $newPassword = $post['new_password'] ?? '';
    $confirmPassword = $post['confirm_password'] ?? '';

    if ($currentAdminId <= 0) {
        return 'Error: Sesi admin tidak valid.';
    }
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        return 'Error: Semua field kata sandi wajib diisi.';
    }
    if (strlen($newPassword) < 6) {
        return 'Error: Kata sandi baru minimal 6 karakter.';
    }
    if ($newPassword !== $confirmPassword) {
        return 'Error: Konfirmasi kata sandi baru tidak cocok.';
    }

    $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = :id');
    $stmt->execute(['id' => $currentAdminId]);
    $storedPassword = $stmt->fetchColumn();

    if ($storedPassword === false) {
        return 'Error: Data admin tidak ditemukan.';
    }
    if (!password_verify($currentPassword, $storedPassword) && $currentPassword !== $storedPassword) {
        return 'Error: Kata sandi saat ini salah.';
    }

    $update = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
    $update->execute([
        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        'id' => $currentAdminId
    ]);

    return 'Kata sandi admin berhasil diperbarui.';
}

function admin_add_manual_student(PDO $pdo, $regCol, $dobCol, array $post) {
    if ($regCol === null || $dobCol === null) {
        return 'Error: Struktur tabel students belum sesuai (kolom identitas/tanggal lahir tidak ditemukan).';
    }

    $reg = trim((string)($post['nisn'] ?? ''));
    $name = trim((string)($post['name'] ?? ''));
    $dob = $post['birth_date'] ?? '';
    $status = $post['status'] ?? '';
    $predikatId = !empty($post['predikat_id']) ? $post['predikat_id'] : null;

    $errors = [];
    if ($name === '') {
        $errors[] = 'Nama tidak boleh kosong.';
    }
    if ($reg === '') {
        $errors[] = 'NISN tidak boleh kosong.';
    }
    if ($dob === '') {
        $errors[] = 'Tanggal lahir tidak boleh kosong.';
    }
    if (!in_array($status, ['Lulus', 'Tidak Lulus'], true)) {
        $errors[] = 'Status kelulusan tidak valid.';
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE ' . $regCol . ' = ?');
    $stmt->execute([$reg]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'NISN sudah ada.';
    }

    if ($errors) {
        return 'Error: ' . implode(' ', $errors);
    }

    $insertData = [
        'name' => $name,
        $regCol => $reg,
        $dobCol => $dob,
        'status' => $status,
        'predikat_id' => $predikatId
    ];
    $insertFields = ['name', $regCol, $dobCol, 'status', 'predikat_id'];
    $placeholders = ':' . implode(', :', $insertFields);

    $stmt = $pdo->prepare('INSERT INTO students (' . implode(', ', $insertFields) . ') VALUES (' . $placeholders . ')');
    $stmt->execute($insertData);

    return 'Siswa berhasil ditambahkan.';
}

function admin_add_bulk_students(PDO $pdo, $regCol, $dobCol, $bulkData) {
    if ($regCol === null || $dobCol === null) {
        return 'Error: Struktur tabel students belum sesuai (kolom identitas/tanggal lahir tidak ditemukan).';
    }

    $bulkData = trim((string)$bulkData);
    if ($bulkData === '') {
        return 'Error: Data bulk kosong.';
    }

    $lines = array_filter(array_map('trim', explode("\n", $bulkData)));
    $errors = [];
    $inserted = 0;

    foreach ($lines as $index => $line) {
        $parts = array_map('trim', str_getcsv($line));
        if (count($parts) < 5) {
            $errors[] = 'Baris ' . ($index + 1) . ' salah format.';
            continue;
        }

        list($reg, $name, $birthDate, $status, $predikatId) = $parts;
        if ($reg === '' || $name === '' || $birthDate === '' || $status === '') {
            $errors[] = 'Baris ' . ($index + 1) . ': semua kolom wajib terisi.';
            continue;
        }
        if (!in_array($status, ['Lulus', 'Tidak Lulus'], true)) {
            $errors[] = 'Baris ' . ($index + 1) . ': status harus Lulus/Tidak Lulus.';
            continue;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE ' . $regCol . ' = ?');
        $stmt->execute([$reg]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Baris ' . ($index + 1) . ': NISN/No. Pendaftaran ' . htmlspecialchars($reg) . ' sudah ada.';
            continue;
        }

        $stmt = $pdo->prepare('INSERT INTO students (' . $regCol . ', name, ' . $dobCol . ', status, predikat_id) VALUES (:reg, :name, :birth_date, :status, :predikat_id)');
        $stmt->execute([
            'reg' => $reg,
            'name' => $name,
            'birth_date' => $birthDate,
            'status' => $status,
            'predikat_id' => ($predikatId === '' ? null : (int)$predikatId)
        ]);
        $inserted++;
    }

    $message = 'Bulk add selesai: ' . $inserted . ' siswa ditambahkan.';
    if ($errors) {
        $message .= ' Errors: ' . implode(' ', $errors);
    }
    return $message;
}

function admin_update_student(PDO $pdo, $regCol, $dobCol, array $post) {
    if ($regCol === null || $dobCol === null) {
        return 'Error: Struktur tabel students belum sesuai (kolom identitas/tanggal lahir tidak ditemukan).';
    }

    $id = $post['edit_id'] ?? '';
    $nisn = trim((string)($post['nisn'] ?? ''));
    $name = trim((string)($post['name'] ?? ''));
    $birthDate = $post['birth_date'] ?? '';
    $status = $post['status'] ?? '';
    $predikatId = !empty($post['predikat_id']) ? $post['predikat_id'] : null;

    $errors = [];
    if ($name === '') {
        $errors[] = 'Nama tidak boleh kosong.';
    }
    if ($nisn === '') {
        $errors[] = 'NISN tidak boleh kosong.';
    }
    if ($birthDate === '') {
        $errors[] = 'Tanggal lahir tidak boleh kosong.';
    }
    if (!in_array($status, ['Lulus', 'Tidak Lulus'], true)) {
        $errors[] = 'Status kelulusan tidak valid.';
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE ' . $regCol . ' = ? AND id != ?');
    $stmt->execute([$nisn, $id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'NISN sudah ada.';
    }

    if ($errors) {
        return 'Error: ' . implode(' ', $errors);
    }

    $stmt = $pdo->prepare('UPDATE students SET ' . $regCol . ' = ?, name = ?, ' . $dobCol . ' = ?, status = ?, predikat_id = ? WHERE id = ?');
    $stmt->execute([$nisn, $name, $birthDate, $status, $predikatId, $id]);
    return 'Siswa berhasil diperbarui.';
}

function admin_delete_students(PDO $pdo, array $ids) {
    if (!$ids) {
        return 'Tidak ada data yang dipilih.';
    }
    $in = implode(',', array_map('intval', $ids));
    $pdo->query("DELETE FROM students WHERE id IN ($in)");
    return 'Data siswa terpilih telah dihapus.';
}

function admin_get_student_json(PDO $pdo, $regCol, $dobCol, $id) {
    if ($regCol === null || $dobCol === null) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id, ' . $regCol . ' AS nisn, name, ' . $dobCol . ' AS birth_date, status, predikat_id FROM students WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function admin_update_student_photo(PDO $pdo, $photoCol, array $post, array $files) {
    if ($photoCol === null) {
        return 'Error: Kolom foto siswa belum tersedia.';
    }

    $studentId = (int)($post['student_id'] ?? 0);
    if ($studentId <= 0) {
        return 'Error: Siswa tidak valid.';
    }
    if (!isset($files['student_photo']) || $files['student_photo']['error'] !== UPLOAD_ERR_OK) {
        return 'Error: File foto belum dipilih atau upload gagal.';
    }

    $tmp = $files['student_photo']['tmp_name'];
    $originalName = $files['student_photo']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowedExtensions, true)) {
        return 'Error: Format foto harus JPG, JPEG, PNG, atau WEBP.';
    }

    if (@getimagesize($tmp) === false) {
        return 'Error: File yang diupload bukan gambar yang valid.';
    }

    $stmt = $pdo->prepare('SELECT ' . $photoCol . ' FROM students WHERE id = ?');
    $stmt->execute([$studentId]);
    $oldPhoto = $stmt->fetchColumn();
    if ($oldPhoto === false) {
        return 'Error: Data siswa tidak ditemukan.';
    }

    $uploadDir = dirname(__DIR__, 2) . '/assets/students';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = 'student_' . $studentId . '_' . time() . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;
    if (!move_uploaded_file($tmp, $destination)) {
        return 'Error: Gagal menyimpan foto siswa.';
    }

    $stmt = $pdo->prepare('UPDATE students SET ' . $photoCol . ' = ? WHERE id = ?');
    $stmt->execute([$fileName, $studentId]);

    if (!empty($oldPhoto)) {
        $oldPath = $uploadDir . '/' . basename($oldPhoto);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return 'Foto siswa berhasil diperbarui.';
}
