<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$files      = is_array(isset($files) ? $files : null) ? $files : [];
$leadId     = (int)(isset($lead['id']) ? $lead['id'] : 0);
$canEdit    = !empty($can['edit']);
$canDelete  = !empty($can['delete']);
$canView    = !empty($can['view']);

if (!function_exists('crm_file_ext')) {
    function crm_file_ext($path, $name = '') {
        $target = $name ? $name : $path;
        $ext    = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        return $ext !== '' ? $ext : 'file';
    }
}

if (!function_exists('crm_file_icon_meta')) {
    function crm_file_icon_meta($ext) {
        $map = [
            'pdf'  => ['icon' => 'fa-solid fa-file-pdf fa-fw',         'color' => '#dc2626', 'bg' => '#fef2f2', 'label' => 'PDF'],
            'doc'  => ['icon' => 'fa-solid fa-file-word fa-fw',        'color' => '#1d4ed8', 'bg' => '#eff6ff', 'label' => 'DOC'],
            'docx' => ['icon' => 'fa-solid fa-file-word fa-fw',        'color' => '#1d4ed8', 'bg' => '#eff6ff', 'label' => 'DOCX'],
            'xls'  => ['icon' => 'fa-solid fa-file-excel fa-fw',       'color' => '#16a34a', 'bg' => '#f0fdf4', 'label' => 'XLS'],
            'xlsx' => ['icon' => 'fa-solid fa-file-excel fa-fw',       'color' => '#16a34a', 'bg' => '#f0fdf4', 'label' => 'XLSX'],
            'csv'  => ['icon' => 'fa-solid fa-file-csv fa-fw',         'color' => '#16a34a', 'bg' => '#f0fdf4', 'label' => 'CSV'],
            'png'  => ['icon' => 'fa-solid fa-file-image fa-fw',       'color' => '#db2777', 'bg' => '#fdf2f8', 'label' => 'PNG'],
            'jpg'  => ['icon' => 'fa-solid fa-file-image fa-fw',       'color' => '#db2777', 'bg' => '#fdf2f8', 'label' => 'JPG'],
            'jpeg' => ['icon' => 'fa-solid fa-file-image fa-fw',       'color' => '#db2777', 'bg' => '#fdf2f8', 'label' => 'JPEG'],
            'gif'  => ['icon' => 'fa-solid fa-file-image fa-fw',       'color' => '#db2777', 'bg' => '#fdf2f8', 'label' => 'GIF'],
            'webp' => ['icon' => 'fa-solid fa-file-image fa-fw',       'color' => '#db2777', 'bg' => '#fdf2f8', 'label' => 'WEBP'],
            'txt'  => ['icon' => 'fa-solid fa-file-lines fa-fw',       'color' => '#475569', 'bg' => '#f8fafc', 'label' => 'TXT'],
            'zip'  => ['icon' => 'fa-solid fa-file-zipper fa-fw',      'color' => '#7c3aed', 'bg' => '#f5f3ff', 'label' => 'ZIP'],
            'rar'  => ['icon' => 'fa-solid fa-file-zipper fa-fw',      'color' => '#7c3aed', 'bg' => '#f5f3ff', 'label' => 'RAR'],
            'file' => ['icon' => 'fa-solid fa-file fa-fw',             'color' => '#475569', 'bg' => '#f8fafc', 'label' => 'FILE'],
        ];
        return isset($map[$ext]) ? $map[$ext] : $map['file'];
    }
}
?>

<style>

.lf-ext-badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 2px 8px; border-radius: 5px;
    font-size: 8px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.3px;
    line-height: 1.6;
}

.lf-tfoot td {
    background: #f8fafc !important;
    border-top: 2px solid #e2e8f0 !important;
    font-size: 12px; color: #64748b; font-weight: 600;
    padding: 10px 14px !important;
    letter-spacing: 0.3px;
}

.lf-empty {
    border: 2px dashed #e2e8f0; border-radius: 12px;
    padding: 44px 24px; text-align: center; background: #f8fafc;
}
.lf-empty-icon {
    width: 60px; height: 60px; border-radius: 16px;
    background: #e2e8f0; color: #94a3b8;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 26px; margin-bottom: 14px;
}
.lf-empty-icon i { display: flex; align-items: center; justify-content: center; }

@media (max-width: 767px) {
    .lf-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
</style>

    <div class="card-body p-3">
        
        <?php if ($canEdit): ?>
            <div class="lf-dropzone" id="leadFilesDropzone">
                <div class="lf-dropzone-icon">
                    <i class="ti ti-cloud-upload" style="display:flex;align-items:center;justify-content:center;font-size:20px;"></i>
                </div>
                <div style="flex:1;">
                    <div class="lf-dropzone-title">Drag &amp; drop files here</div>
                    <div class="lf-dropzone-sub">PDF, DOCX, XLSX, PNG, JPG, TXT, ZIP — complete file details in the popup</div>
                </div>
                <button type="button" class="lf-dropzone-btn">Browse Files</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($files)): ?>
            <div class="crm-table">
                <div class="table-responsive">
                    <table class="crm-table-light">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>File Name</th>
                                <th>File Title</th>
                                <th>Uploaded By</th>
                                <th>Date Time</th>
                                <th>Visibility</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $f):
                                $fileId   = (int)($f['id'] ?? 0);
                                $fileName = (string)($f['file_name'] ?? '');
                                $filePath = (string)($f['file_path'] ?? '');
                                $title    = (string)($f['title'] ?? '');
                                $creator  = (string)($f['created_by_name'] ?? 'Unknown');
                                $created  = (string)($f['created_at'] ?? '');
                                $isPublic = (int)($f['is_public'] ?? 0) === 1;
                                $ext      = crm_file_ext($filePath, $fileName);
                                $meta     = crm_file_icon_meta($ext);
                            ?>
                            <tr>
                                <td>
                                    <span class="text-muted x-small">#<?= $fileId ?></span>
                                </td>
                                
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;min-width:160px;">
                                        <div style="background:<?= html_escape($meta['bg']) ?>;color:<?= html_escape($meta['color']) ?>;">
                                            <i class="<?= html_escape($meta['icon']) ?>"></i>
                                        </div>
                                        <span class="small"><?= html_escape($fileName) ?></span>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="text-muted capital">
                                        <?= $title !== '' ? html_escape($title) : '<span style="color:#cbd5e1;">—</span>' ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?= user_profile_small($creator) ?></td>
                                <td>
                                    <span class="small">
                                        <?= $created ? date('M j, Y', strtotime($created)) : '—' ?>
                                        <?= $created ? date('g:i A', strtotime($created)) : '' ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($isPublic): ?>
                                        <span class="badge bg-primary small"><i class="ti ti-eye"></i> Public</span>
                                    <?php else: ?>
                                        <span class="badge bg-light-primary small"><i class="ti ti-eye-off"></i> Private</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="display:flex;align-items:center;gap:5px;">
                                        <?php if ($canView): ?>
                                            <a href="<?= site_url('crm/files/download/' . $fileId) ?>"
                                               class="btn btn-light-primary icon-btn" title="Download">
                                                <i class="ti ti-download"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($canDelete): ?>
                                            <form action="<?= site_url('crm/files/delete/' . $fileId) ?>"
                                                  method="post" class="d-inline"
                                                  onsubmit="return confirm('Delete this file?');">
                                                <button type="submit"
                                                        class="btn btn-light-danger icon-btn" title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="lf-empty">
                <div class="lf-empty-icon">
                    <i class="ti ti-paperclip" style="font-size:26px;display:flex;align-items:center;justify-content:center;"></i>
                </div>
                <h6 style="margin-bottom:6px;color:#334155;">No files uploaded yet</h6>
                <p style="color:#94a3b8;font-size:13px;margin:0;">
                    Upload contracts, proposals, PDFs, spreadsheets, and supporting documents for this lead.
                </p>
            </div>
        <?php endif; ?>

    </div>

<?php if ($canEdit): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dropzone    = document.getElementById('leadFilesDropzone');
    var modalInput  = document.getElementById('lead_files_input');
    var selectedList = document.getElementById('selectedLeadFilesList');
    var modalEl     = document.getElementById('uploadLeadFilesModal');
    if (!dropzone || !modalInput || !modalEl) return;

    function renderFileList(files) {
        if (!selectedList) return;
        if (!files || !files.length) {
            selectedList.innerHTML = '<span style="font-size:12.5px;color:#94a3b8;">No files selected.</span>';
            return;
        }
        var extColors = {
            pdf:'#dc2626',doc:'#1d4ed8',docx:'#1d4ed8',
            xls:'#16a34a',xlsx:'#16a34a',csv:'#16a34a',
            png:'#db2777',jpg:'#db2777',jpeg:'#db2777',
            zip:'#7c3aed',rar:'#7c3aed'
        };
        var extBgs = {
            pdf:'#fef2f2',doc:'#eff6ff',docx:'#eff6ff',
            xls:'#f0fdf4',xlsx:'#f0fdf4',csv:'#f0fdf4',
            png:'#fdf2f8',jpg:'#fdf2f8',jpeg:'#fdf2f8',
            zip:'#f5f3ff',rar:'#f5f3ff'
        };
        selectedList.innerHTML = Array.from(files).map(function(f) {
            var ext  = f.name.split('.').pop().toLowerCase();
            var size = f.size > 1048576 ? (f.size/1048576).toFixed(1)+' MB' : (f.size/1024).toFixed(0)+' KB';
            var bg   = extBgs[ext]    || '#f8fafc';
            var col  = extColors[ext] || '#475569';
            return '<div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f1f5f9;">'
                + '<span style="background:'+bg+';color:'+col+';font-size:10px;font-weight:800;padding:2px 7px;border-radius:5px;font-family:monospace;flex-shrink:0;text-transform:uppercase;">'+ext.toUpperCase()+'</span>'
                + '<span style="font-size:13px;color:#0f172a;font-weight:500;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+f.name+'</span>'
                + '<span style="font-size:11.5px;color:#94a3b8;flex-shrink:0;">'+size+'</span>'
                + '</div>';
        }).join('');
    }

    dropzone.addEventListener('click', function () { modalInput.click(); });

    ['dragenter','dragover'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });
    ['dragleave','drop'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
        modalInput.files = e.dataTransfer.files;
        renderFileList(modalInput.files);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    modalInput.addEventListener('change', function() {
        renderFileList(this.files);
        if (this.files && this.files.length) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });
});
</script>
<?php endif; ?>