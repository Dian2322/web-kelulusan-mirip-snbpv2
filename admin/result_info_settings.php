<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);
$message = admin_take_flash_message('admin_result_info_message');
$settings = admin_load_settings($pdo);

if (isset($_POST['action']) && $_POST['action'] === 'add_result_info_item') {
    $message = admin_add_result_info_item($pdo, $context['hasSettings'], $settings, $_POST);
    admin_redirect_with_message('result_info_settings.php', $message, 'admin_result_info_message');
}
if (isset($_POST['action']) && $_POST['action'] === 'delete_result_info_item') {
    $message = admin_delete_result_info_item($pdo, $context['hasSettings'], $settings, $_POST['item_index'] ?? -1);
    admin_redirect_with_message('result_info_settings.php', $message, 'admin_result_info_message');
}

$settings = admin_load_settings($pdo);
$logo = $settings['logo'];
$resultInfoColor = $settings['result_info_note_color'] ?? '#f5f8ff';
$resultInfoIcon = $settings['result_info_note_icon'] ?? 'fas fa-circle-info';
$resultInfoItems = admin_load_result_info_items($settings);

admin_render_page_start('Informasi Tambahan Hasil', 'result-info', $logo, $message);
$summernoteScript = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.css">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.js"></script>
<script>
$(function () {
    var imagePicker = $('<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">');
    $('body').append(imagePicker);

    function uploadResultInfoImage(file) {
        var formData = new FormData();
        formData.append('image', file);

        return $.ajax({
            url: 'upload_result_info_image.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        });
    }

    function insertUploadedImages(editor, files) {
        Array.from(files).forEach(function(file) {
            uploadResultInfoImage(file)
                .done(function(response) {
                    if (response && response.url) {
                        editor.summernote('insertImage', response.url, response.filename || 'gambar');
                        return;
                    }
                    alert((response && response.error) ? response.error : 'Upload gambar gagal.');
                })
                .fail(function(xhr) {
                    var message = 'Upload gambar gagal.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    }
                    alert(message);
                });
        });
    }

    imagePicker.on('change', function() {
        var input = this;
        if (!input.files || !input.files.length) {
            return;
        }

        var editor = $('#result_info_note');
        insertUploadedImages(editor, input.files);

        input.value = '';
    });

    $('#result_info_note').summernote({
        height: 220,
        minHeight: 180,
        maxHeight: 320,
        placeholder: 'Contoh: Silakan hubungi panitia untuk informasi daftar ulang.',
        dialogsInBody: true,
        disableDragAndDrop: true,
        buttons: {
            resultInfoImage: function(context) {
                var ui = $.summernote.ui;
                return ui.button({
                    contents: '<i class="note-icon-picture"></i>',
                    tooltip: 'Upload Gambar',
                    click: function() {
                        imagePicker.trigger('click');
                    }
                }).render();
            }
        },
        toolbar: [
            ['style', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'resultInfoImage']],
            ['view', ['codeview']]
        ],
        callbacks: {
            onImageUpload: function(files) {
                insertUploadedImages($(this), files);
            }
        }
    });
});
</script>
HTML;
?>
<style>
    .result-info-settings-scroll {
        max-height: calc(100vh - 150px);
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        padding-bottom: calc(var(--admin-footer-space) + 16px);
    }
    .note-editor.note-frame {
        background: #fff;
    }
    .note-editor .note-editing-area .note-editable {
        min-height: 180px;
        max-height: 320px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .result-info-style-row {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .result-info-style-field {
        flex: 1 1 220px;
    }
    .result-info-style-color {
        flex: 0 0 160px;
    }
    .result-info-style-action {
        flex: 0 0 auto;
    }
    .result-info-preview-row {
        margin-top: 24px;
    }
    .result-info-preview {
        padding: 12px 14px;
        border-radius: 10px;
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.14);
        display: flex;
        align-items: flex-start;
        gap: 10px;
        max-width: 520px;
    }
    .result-info-preview-icon {
        flex: 0 0 auto;
        margin-top: 2px;
        font-size: 0.95rem;
    }
    .result-info-preview-text {
        flex: 1 1 auto;
        min-width: 0;
        font-size: 0.92rem;
        line-height: 1.55;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .result-info-item-list {
        display: grid;
        gap: 14px;
        margin-top: 22px;
    }
    .result-info-item-card {
        border: 1px solid rgba(27, 63, 114, 0.12);
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }
    .result-info-item-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }
    .result-info-item-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1b3f72;
    }
    @media (max-width: 768px) {
        .result-info-settings-scroll {
            max-height: calc(100vh - 170px);
        }
    }
    @media (max-width: 480px) {
        .result-info-settings-scroll {
            max-height: calc(100vh - 182px);
        }
        .result-info-style-row,
        .result-info-item-head {
            flex-direction: column;
            align-items: stretch;
        }
        .result-info-style-field,
        .result-info-style-color,
        .result-info-style-action {
            flex: 1 1 100%;
        }
    }
</style>
<div class="result-info-settings-scroll">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Tambahan Hasil Pengumuman</h3>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="add_result_info_item">
                <div class="form-group">
                    <label for="result_info_note">Teks informasi di bawah kartu hasil</label>
                    <textarea id="result_info_note" name="result_info_note" class="form-control" rows="4"></textarea>
                </div>
                <div class="result-info-style-row">
                    <div class="form-group result-info-style-color">
                        <label for="result_info_note_color">Warna teks informasi</label>
                        <input type="color" id="result_info_note_color" name="result_info_note_color" class="form-control" value="<?php echo htmlspecialchars($resultInfoColor); ?>" style="max-width: 140px;">
                    </div>
                    <div class="form-group result-info-style-field">
                        <label for="result_info_note_icon">Ikon kecil di samping teks</label>
                        <select id="result_info_note_icon" name="result_info_note_icon" class="form-control">
                            <option value="fas fa-circle-info" <?php echo $resultInfoIcon === 'fas fa-circle-info' ? 'selected' : ''; ?>>Info</option>
                            <option value="fas fa-bullhorn" <?php echo $resultInfoIcon === 'fas fa-bullhorn' ? 'selected' : ''; ?>>Pengumuman</option>
                            <option value="fas fa-triangle-exclamation" <?php echo $resultInfoIcon === 'fas fa-triangle-exclamation' ? 'selected' : ''; ?>>Peringatan</option>
                            <option value="fas fa-bell" <?php echo $resultInfoIcon === 'fas fa-bell' ? 'selected' : ''; ?>>Bel</option>
                            <option value="fas fa-circle-check" <?php echo $resultInfoIcon === 'fas fa-circle-check' ? 'selected' : ''; ?>>Cek</option>
                            <option value="fas fa-book-open" <?php echo $resultInfoIcon === 'fas fa-book-open' ? 'selected' : ''; ?>>Buku</option>
                            <option value="fas fa-clipboard-list" <?php echo $resultInfoIcon === 'fas fa-clipboard-list' ? 'selected' : ''; ?>>Daftar</option>
                            <option value="fas fa-graduation-cap" <?php echo $resultInfoIcon === 'fas fa-graduation-cap' ? 'selected' : ''; ?>>Kelulusan</option>
                        </select>
                    </div>
                    <div class="form-group result-info-style-action">
                        <button type="submit" class="btn btn-primary">Tambah Informasi</button>
                    </div>
                </div>
                <div class="result-info-preview-row">
                    <div class="result-info-preview">
                        <span class="result-info-preview-icon" style="color: <?php echo htmlspecialchars($resultInfoColor); ?>;">
                            <i class="<?php echo htmlspecialchars($resultInfoIcon); ?>" aria-hidden="true"></i>
                        </span>
                        <div class="result-info-preview-text" style="color: <?php echo htmlspecialchars($resultInfoColor); ?>;">
                            Teks sample informasi tambahan hasil pengumuman akan tampil seperti ini.
                        </div>
                    </div>
                </div>
            </form>

            <?php if (!empty($resultInfoItems)): ?>
                <div class="result-info-item-list">
                    <?php foreach ($resultInfoItems as $index => $item): ?>
                        <div class="result-info-item-card">
                            <div class="result-info-item-head">
                                <div class="result-info-item-title">Informasi <?php echo (int)($index + 1); ?></div>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_result_info_item">
                                    <input type="hidden" name="item_index" value="<?php echo (int)$index; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                            <div class="result-info-preview" style="margin-top:0;">
                                <span class="result-info-preview-icon" style="color: <?php echo htmlspecialchars($item['color']); ?>;">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i>
                                </span>
                                <div class="result-info-preview-text" style="color: <?php echo htmlspecialchars($item['color']); ?>; white-space: normal; overflow: visible; text-overflow: initial;">
                                    <?php echo $item['text']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
admin_render_page_end($summernoteScript);
