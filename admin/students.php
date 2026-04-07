<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';

$context = admin_get_base_context($pdo);

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalStudents = admin_count_students($pdo);
$totalPages = ceil($totalStudents / $itemsPerPage);

if (isset($_GET['action']) && $_GET['action'] === 'get_student' && isset($_GET['id'])) {
    $studentId = (int)$_GET['id'];
    if ($studentId <= 0) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid student ID']);
        exit;
    }
    
    $student = admin_get_student_json($pdo, $context['regCol'], $context['dobCol'], $studentId);
    if ($student) {
        header('Content-Type: application/json');
        echo json_encode($student);
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Student not found']);
    exit;
}

$message = admin_take_flash_message();
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['ids'])) {
    $message = admin_delete_students($pdo, $_POST['ids']);
    admin_redirect_with_message('students.php', $message);
}
if (isset($_POST['action']) && $_POST['action'] === 'edit_student') {
    $message = admin_update_student($pdo, $context['regCol'], $context['dobCol'], $_POST);
    admin_redirect_with_message('students.php', $message);
}

$settings = admin_load_settings($pdo);
$students = admin_load_students_paginated($pdo, $context['regCol'], $context['dobCol'], $currentPage, $itemsPerPage);
$predicates = admin_load_predicates($pdo);
$idLabel = $context['regCol'] === 'registration_number' ? 'No. Pendaftaran' : 'NISN';

$extraScript = <<<HTML
<script>
var lastEditStudentFocus = null;

function confirmBulkDelete() {
    var checked = document.querySelectorAll('input[name="ids[]"]:checked').length;
    if (!checked) {
        alert('Pilih minimal satu siswa untuk dihapus.');
        return false;
    }
    return confirm('Yakin menghapus massal data siswa terpilih?');
}

document.addEventListener('DOMContentLoaded', function() {
    var tableHeader = document.querySelector('.students-table-header');
    var tableBody = document.querySelector('.students-table-scroll');
    var headerTable = document.querySelector('.students-table-header table');
    var bodyTable = document.querySelector('.students-table-scroll table');
    var headerCols = document.querySelectorAll('.students-table-header colgroup col');
    var bodyCols = document.querySelectorAll('.students-table-scroll colgroup col');
    function syncStudentTableLayout() {
        if (!tableHeader || !tableBody || !headerTable || !bodyTable) {
            return;
        }

        var scrollbarWidth = tableBody.offsetWidth - tableBody.clientWidth;
        tableHeader.style.paddingRight = scrollbarWidth > 0 ? scrollbarWidth + 'px' : '0px';
        tableHeader.scrollLeft = tableBody.scrollLeft;

        var headerCells = headerTable.querySelectorAll('thead th');
        var bodyFirstRow = bodyTable.querySelector('tbody tr');
        if (!bodyFirstRow || headerCells.length === 0) {
            return;
        }

        var bodyCells = bodyFirstRow.children;
        if (bodyCells.length !== headerCells.length) {
            return;
        }

        var totalWidth = bodyTable.getBoundingClientRect().width;
        headerTable.style.width = totalWidth + 'px';
        bodyTable.style.width = totalWidth + 'px';

        for (var i = 0; i < headerCells.length; i++) {
            var cellWidth = bodyCells[i].getBoundingClientRect().width;
            if (headerCols[i]) {
                headerCols[i].style.width = cellWidth + 'px';
            }
            if (bodyCols[i]) {
                bodyCols[i].style.width = cellWidth + 'px';
            }
            headerCells[i].style.width = cellWidth + 'px';
        }
    }

    if (tableHeader && tableBody) {
        tableBody.addEventListener('scroll', function() {
            tableHeader.scrollLeft = tableBody.scrollLeft;
        }, { passive: true });
        syncStudentTableLayout();
        window.addEventListener('resize', syncStudentTableLayout);
        window.addEventListener('load', syncStudentTableLayout);
    }

    // Checkbox master untuk memilih semua baris yang sedang ditampilkan.
    var updateSelectAllStateFn = null;
    var selectAll = document.getElementById('selectAllStudents');
    if (selectAll) {
        var rowCheckboxes = document.querySelectorAll('.student-table-container input.student-select-checkbox');

        function updateSelectAllState() {
            var enabled = 0;
            var checked = 0;
            rowCheckboxes.forEach(function(cb) {
                if (cb.disabled) return;
                var tr = cb.closest('tr');
                if (tr && tr.style.display === 'none') return;
                enabled += 1;
                if (cb.checked) checked += 1;
            });

            if (enabled === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                return;
            }

            if (checked === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checked === enabled) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(function(cb) {
                if (cb.disabled) return;
                var tr = cb.closest('tr');
                if (tr && tr.style.display === 'none') return;
                cb.checked = selectAll.checked;
            });
            updateSelectAllState();
        });

        rowCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', updateSelectAllState);
        });

        updateSelectAllStateFn = updateSelectAllState;
        updateSelectAllState();
    }

    // Kolom pencarian berdasarkan NISN dan Nama (filter baris pada halaman yang ditampilkan).
    var searchKeyword = document.getElementById('searchKeyword');
    var dataRows = document.querySelectorAll('.student-table-container tbody tr');

    function applyStudentSearch() {
        var keywordVal = (searchKeyword && typeof searchKeyword.value === 'string') ? searchKeyword.value.trim().toLowerCase() : '';

        dataRows.forEach(function(row) {
            var nisnCell = row.cells && row.cells[1] ? row.cells[1] : null; // cell[0] = checkbox
            var nameCell = row.cells && row.cells[2] ? row.cells[2] : null;
            var nisnText = nisnCell ? nisnCell.innerText.trim().toLowerCase() : '';
            var nameText = nameCell ? nameCell.innerText.trim().toLowerCase() : '';

            var matches = !keywordVal || nisnText.includes(keywordVal) || nameText.includes(keywordVal);
            row.style.display = matches ? '' : 'none';
        });

        // Refresh state checkbox master setelah filter diterapkan.
        if (typeof updateSelectAllStateFn === 'function') {
            updateSelectAllStateFn();
        }
    }

    if (searchKeyword) {
        searchKeyword.addEventListener('input', applyStudentSearch);
        applyStudentSearch();
    }

    // Setup cancel button to close the edit modal cleanly.
    var editModalEl = document.getElementById('editStudentModal');
    var cancelBtn = document.getElementById('cancelEditStudentBtn');
    if (editModalEl && cancelBtn) {
        // When the modal is fully hidden, revert any forced state and restore focus.
        if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            $('#editStudentModal').on('hidden.bs.modal', function() {
                // Cleanup forced visibility/backdrop.
                editModalEl.style.display = 'none';
                editModalEl.classList.remove('show');
                editModalEl.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');

                var forcedBackdrop = document.getElementById('editStudentModalBackdrop');
                if (forcedBackdrop) {
                    forcedBackdrop.remove();
                }

                if (lastEditStudentFocus && typeof lastEditStudentFocus.focus === 'function') {
                    lastEditStudentFocus.focus();
                }
                lastEditStudentFocus = null;
            });

            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                $('#editStudentModal').modal('hide');
            });
        } else {
            // Fallback if Bootstrap's modal plugin isn't available.
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                editModalEl.style.display = 'none';
                editModalEl.classList.remove('show');
                editModalEl.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');

                var forcedBackdrop = document.getElementById('editStudentModalBackdrop');
                if (forcedBackdrop) {
                    forcedBackdrop.remove();
                }

                if (lastEditStudentFocus && typeof lastEditStudentFocus.focus === 'function') {
                    lastEditStudentFocus.focus();
                }
                lastEditStudentFocus = null;
            });
        }
    }
});

function editStudent(id) {
    console.log('editStudent called with id:', id);
    // Remember the element that opened the modal to restore focus after close.
    lastEditStudentFocus = document.activeElement;
    
    fetch('students.php?action=get_student&id=' + id)
        .then(function(response) {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error, status=' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Data loaded:', data);
            
            // Check if all required elements exist
            var editId = document.getElementById('edit_id');
            var editNisn = document.getElementById('edit_nisn');
            var editName = document.getElementById('edit_name');
            var editBirthDate = document.getElementById('edit_birth_date');
            var editStatus = document.getElementById('edit_status');
            var editPredikat = document.getElementById('edit_predikat');
            var modal = document.getElementById('editStudentModal');
            
            if (!editId || !editNisn || !editName || !editBirthDate || !editStatus || !editPredikat || !modal) {
                console.error('Required form elements not found!', {
                    editId: !!editId,
                    editNisn: !!editNisn,
                    editName: !!editName,
                    editBirthDate: !!editBirthDate,
                    editStatus: !!editStatus,
                    editPredikat: !!editPredikat,
                    modal: !!modal
                });
                alert('Error: Form elements tidak ditemukan');
                return;
            }
            
            // Fill form with data
            editId.value = data.id || '';
            editNisn.value = data.nisn || '';
            editName.value = data.name || '';
            editBirthDate.value = data.birth_date || '';
            editStatus.value = data.status || 'Lulus';
            editPredikat.value = data.predikat_id || '';
            
            console.log('Form filled successfully');
            
            // Show modal with Bootstrap, but also force visibility to avoid cases
            // where the Bootstrap plugin doesn't toggle CSS correctly.
            try {
                if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    $('#editStudentModal').modal('show');
                }
            } catch (e) {
                console.warn('Bootstrap modal show failed, will force display.', e);
            }

            // Force visible (works even if Bootstrap didn't toggle classes).
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            // Keep focus inside the modal for better UX/accessibility.
            setTimeout(function() {
                try { editNisn.focus(); } catch (e) { /* ignore */ }
            }, 0);

            // Ensure a single backdrop exists.
            document.querySelectorAll('.modal-backdrop').forEach(function(el) {
                el.remove();
            });
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'editStudentModalBackdrop';
            document.body.appendChild(backdrop);
        })
        .catch(function(error) {
            console.error('Error loading student:', error);
            alert('Gagal memuat data siswa: ' + error.message);
        });
}
</script>
HTML;

admin_render_page_start('Data Siswa', 'students', $settings['logo'], $message);
?>
<style>
    /* Modal styling to ensure it displays properly */
    .modal {
        position: fixed !important;
        z-index: 9999 !important;
    }
    .modal-backdrop {
        position: fixed !important;
        z-index: 9998 !important;
    }
    .modal.show {
        display: block !important;
        padding-right: 17px;
    }
    .modal-backdrop.show {
        opacity: 0.5 !important;
    }
    
    .students-table-shell {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        margin-top: 0 !important;
    }
    .students-table-header {
        overflow-x: auto;
        overflow-y: hidden;
        background: #f8fbff;
        box-sizing: border-box;
        margin-bottom: 0;
        border-bottom: 0;
    }
    .students-table-scroll {
        max-height: calc(100vh - 300px - var(--admin-footer-space) - var(--admin-footer-gap));
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 0;
        margin-top: 0;
        display: block;
        position: relative;
    }
    .students-table-scroll::after {
        content: "";
        display: block;
        height: calc(var(--admin-footer-space) + 28px);
        width: 100%;
    }
    .students-table-header table,
    .students-table-scroll table {
        min-width: 1162px !important;
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-bottom: 0;
        box-sizing: border-box;
    }
    .students-table-scroll table {
        margin-top: 0 !important;
    }
    .students-table-header th {
        background: #f8fbff;
        white-space: nowrap;
        vertical-align: middle;
        border-top: 0;
        border-bottom: 0;
        box-sizing: border-box;
        padding: 0.5rem 0.75rem;
    }
    .students-table-scroll td {
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-sizing: border-box;
        padding: 0.5rem 0.75rem;
    }
    .students-table-scroll tbody tr:first-child td {
        border-top: 0;
        padding-top: 0.5rem;
    }
    @media (max-width: 768px) {
        .students-table-scroll {
            max-height: calc(100vh - 250px - var(--admin-footer-space) - var(--admin-footer-gap));
        }
    }
</style>
<div class="card">
    <div class="card-body">
        <div class="dashboard-table-width">
            <form method="post" action="students.php" id="studentsBulkDeleteForm" onsubmit="return confirmBulkDelete();">
                <input type="hidden" name="action" value="bulk_delete">
                <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin: 0;">
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <div class="form-group" style="margin:0;">
                            <input
                                type="text"
                                id="searchKeyword"
                                class="form-control form-control-sm"
                                placeholder="Cari berdasarkan <?php echo htmlspecialchars($idLabel); ?> atau Nama"
                                autocomplete="off"
                                style="padding-top:0.15rem; padding-bottom:0.15rem;"
                                onkeydown="if(event.key==='Enter') event.preventDefault();"
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Hapus Massal
                    </button>
                </div>
                <div class="students-table-shell">
                    <div class="students-table-header">
                        <table class="table table-bordered mb-0" style="width:100%;">
                            <colgroup>
                                        <col style="width:50px;">
                                        <col style="width:180px;">
                                        <col style="width:270px;">
                                        <col style="width:170px;">
                                        <col style="width:180px;">
                                        <col style="width:150px;">
                                        <col style="width:160px;">
                            </colgroup>
                            <thead>
                                <tr>
                                            <th style="text-align:center;">
                                                <input
                                                    type="checkbox"
                                                    id="selectAllStudents"
                                                    aria-label="Pilih semua siswa"
                                                >
                                            </th>
                                    <th><?php echo htmlspecialchars($idLabel); ?></th>
                                    <th>Nama</th>
                                    <th>Tanggal Lahir</th>
                                    <th>Status Kelulusan</th>
                                    <th>Predikat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="student-table-container students-table-scroll">
                        <table class="table table-bordered table-striped mb-0" style="width:100%;">
                            <colgroup>
                                        <col style="width:50px;">
                                        <col style="width:180px;">
                                        <col style="width:270px;">
                                        <col style="width:170px;">
                                        <col style="width:180px;">
                                        <col style="width:150px;">
                                        <col style="width:160px;">
                            </colgroup>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                                <td>
                                                    <input
                                                        type="checkbox"
                                                        class="student-select-checkbox"
                                                        name="ids[]"
                                                        value="<?php echo (int)($student['id'] ?? 0); ?>"
                                                        <?php echo empty($student['id']) ? 'disabled' : ''; ?>
                                                    >
                                                </td>
                                                <td><?php echo htmlspecialchars($student[$context['regCol']] ?? $student['id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student[$context['dobCol']] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($student['status'] ?? '-'); ?></td>
                                        <td><?php echo !empty($student['predikat_name']) ? htmlspecialchars($student['predikat_name']) : '-'; ?></td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                onclick="editStudent(<?php echo (int)($student['id'] ?? 0); ?>)"
                                                <?php echo empty($student['id']) ? 'disabled' : ''; ?>
                                            >
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Pagination Controls -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding: 0 15px;">
                    <div style="font-size: 14px; color: #666;">
                        Menampilkan <?php echo count($students); ?> dari <?php echo $totalStudents; ?> siswa
                        <?php if ($totalPages > 1): ?>
                            | Halaman <?php echo $currentPage; ?> dari <?php echo $totalPages; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Pagination Navigation">
                            <ul class="pagination mb-0" style="margin: 0;">
                                <!-- Previous Button -->
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="students.php?page=1" title="Halaman Pertama">
                                            <i class="fas fa-step-backward"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="students.php?page=<?php echo $currentPage - 1; ?>" title="Halaman Sebelumnya">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-step-backward"></i></span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    
                                    if ($startPage > 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="students.php?page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($endPage < $totalPages): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>

                                <!-- Next Button -->
                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="students.php?page=<?php echo $currentPage + 1; ?>" title="Halaman Berikutnya">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="students.php?page=<?php echo $totalPages; ?>" title="Halaman Terakhir">
                                            <i class="fas fa-step-forward"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-step-forward"></i></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Siswa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="students.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_student">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_nisn"><?php echo htmlspecialchars($idLabel); ?></label>
                        <input type="text" id="edit_nisn" name="nisn" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Nama</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_birth_date">Tanggal Lahir</label>
                        <input type="date" id="edit_birth_date" name="birth_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status Kelulusan</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="Lulus">Lulus</option>
                            <option value="Tidak Lulus">Tidak Lulus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_predikat">Predikat</label>
                        <select id="edit_predikat" name="predikat_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($predicates as $predicate): ?>
                                <option value="<?php echo (int)$predicate['id']; ?>"><?php echo htmlspecialchars($predicate['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEditStudentBtn">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
admin_render_page_end($extraScript);
