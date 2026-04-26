<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $leadId = (int)($lead['id'] ?? 0); ?>

<div class="modal fade app-modal" id="uploadLeadFilesModal" tabindex="-1" aria-labelledby="uploadLeadFilesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= site_url('crm/files/upload') ?>" method="post" enctype="multipart/form-data">

                <!-- ── Header ──────────────────────────────────────────── -->
                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-purple">
                            <i class="ti ti-upload"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="uploadLeadFilesModalLabel">Upload Lead Files</div>
                            <div class="app-modal-subtitle">Attach documents, contracts, or any related files to this lead</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <!-- ── Body ───────────────────────────────────────────── -->
                <div class="app-modal-body">

                    <input type="hidden" name="related_type" value="lead">
                    <input type="hidden" name="related_id" value="<?= $leadId ?>">

                    <!-- Section: File Upload -->
                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-files" style="font-size:12px;color:#5ebfbf;"></i>
                            File Selection
                        </div>

                        <div class="app-form-group">
                            <label class="app-form-label app-form-label-required" for="lead_files_input">Select Files</label>
                            <input type="file" name="files[]" id="lead_files_input" class="app-form-control" multiple required>
                            <div class="app-form-hint">You can select multiple files at once. Supported: PDF, DOCX, XLSX, JPG, PNG.</div>
                        </div>

                        <div class="app-form-group" style="margin-top:14px;">
                            <label class="app-form-label">Selected Files</label>
                            <div id="selectedLeadFilesList"
                                 style="min-height:58px;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px 14px;background:#f8fafc;">
                                <span style="font-size:12.5px;color:#94a3b8;">No files selected.</span>
                            </div>
                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <!-- Section: File Details -->
                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-section-label">
                            <i class="ti ti-info-circle" style="font-size:12px;color:#5ebfbf;"></i>
                            File Details
                        </div>
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="lead_file_title">Title</label>
                                    <input type="text" name="title" id="lead_file_title"
                                           class="app-form-control" placeholder="e.g. Signed Contract Q1">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label d-block">Visibility</label>
                                    <div style="display:flex;align-items:center;gap:10px;margin-top:6px;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;" onclick="document.getElementById('lead_file_public').click()">
                                        <input class="form-check-input m-0" type="checkbox" name="is_public" value="1" id="lead_file_public" style="cursor:pointer;">
                                        <div>
                                            <div style="font-size:13px;font-weight:500;color:#0f172a;">Mark as public / shared</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="lead_file_description">Description</label>
                                    <textarea name="description" id="lead_file_description"
                                              class="app-form-control" rows="3"
                                              placeholder="Add any notes or context about these files…"></textarea>
                                    <div class="app-form-hint">Visible to you only.</div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div><!-- /.app-modal-body -->

                <!-- ── Footer ─────────────────────────────────────────── -->
                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:14px;"></i>
                        Files are attached directly to this lead record.
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Save Files
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('lead_files_input');
    const list  = document.getElementById('selectedLeadFilesList');
    if (!input || !list) return;

    input.addEventListener('change', function () {
        if (!this.files.length) {
            list.innerHTML = '<span style="font-size:12.5px;color:#94a3b8;">No files selected.</span>';
            return;
        }
        list.innerHTML = Array.from(this.files).map(f => {
            const ext  = f.name.split('.').pop().toUpperCase();
            const size = f.size > 1048576
                ? (f.size / 1048576).toFixed(1) + ' MB'
                : (f.size / 1024).toFixed(0) + ' KB';
            return `<div style="display:flex;align-items:center;gap:10px;padding:5px 0;border-bottom:1px solid #f1f5f9;">
                        <span style="background:#ede9fe;color:#7c3aed;font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;flex-shrink:0;">${ext}</span>
                        <span style="font-size:13px;color:#0f172a;font-weight:500;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.name}</span>
                        <span style="font-size:11.5px;color:#94a3b8;flex-shrink:0;">${size}</span>
                    </div>`;
        }).join('');
    });
})();
</script>