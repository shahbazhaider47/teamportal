<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Re-use permission for downloads in both inline view + modal
$canDownload = staff_can('edit', 'contracts');

// Precompute contract file context once
$contract_file_path = !empty($contract['contract_file'])
    ? base_url('uploads/users/contracts/' . $contract['contract_file'])
    : null;

$file_extension = !empty($contract['contract_file'])
    ? strtolower(pathinfo($contract['contract_file'], PATHINFO_EXTENSION))
    : null;

$is_pdf      = $file_extension === 'pdf';
$is_image    = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'], true);
$is_document = in_array($file_extension, ['doc', 'docx'], true);

// Expiry helpers
$endDateTs        = !empty($contract['end_date']) ? strtotime($contract['end_date']) : null;
$nowTs            = time();
$isExpired        = $endDateTs && $endDateTs < $nowTs;
$daysUntilEnd     = $endDateTs ? floor(($endDateTs - $nowTs) / (60 * 60 * 24)) : null;
$isCloseToExpire  = $endDateTs && $daysUntilEnd !== null && $daysUntilEnd >= 0 && $daysUntilEnd <= 30;
$showRenewButton  = $isExpired || $isCloseToExpire;

// Signed helper (either status = signed or signed_at is set)
$isSigned = !empty($contract['signed_at']) || (isset($contract['status']) && $contract['status'] === 'signed');

?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h1 class="h6 header-title"><?= $page_title ?><i class="ti ti-chevron-right"></i>
                <span class="text-muted small">ID #<?= (int)$contract['id']; ?></span>
                <span class="small badge bg-<?= $contract['status'] === 'signed'
                        ? 'success'
                        : ($contract['status'] === 'draft'
                            ? 'warning'
                            : ($contract['status'] === 'expired' ? 'danger' : 'secondary')); ?> ms-2">
                    <?= ucfirst(html_escape($contract['status'])); ?>
                </span>
            </h1>
        </div>

        <div class="d-flex gap-2 flex-wrap">

            <?php if (isset($is_self_view) && $is_self_view && !$isSigned && ($contract['status'] ?? '') === 'sent'): ?>
                <button type="button"
                        class="btn btn-primary btn-header"
                        data-bs-toggle="modal"
                        data-bs-target="#signContractModal">
                    <i class="ti ti-signature me-1"></i> Sign Contract
                </button>
            <?php endif; ?>

            <?php if (isset($is_self_view) && $is_self_view): ?>
            <a href="<?= site_url('users/profile'); ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-left me-1"></i> Back to Profile
            </a>
            <?php else: ?>
            <a href="<?= site_url('contracts'); ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-left me-1"></i> Back to List
            </a>
            <?php endif; ?>
            
            <?php if (staff_can('edit', 'contracts') && !$showRenewButton): ?>
                <a href="<?= site_url('contracts/renew/' . (int)$contract['id']); ?>"
                   class="btn btn-primary btn-header"
                   onclick="return confirm('Mark this contract as renewed? This will update the renewal date.');">
                    <i class="ti ti-refresh me-1"></i> Mark Renewed
                </a>
            <?php endif; ?>
        
            <?php if (staff_can('edit', 'contracts') && !$isSigned): ?>
                <a href="<?= site_url('contracts/send_for_sign/' . (int)$contract['id']); ?>"
                   class="btn btn-light-secondary btn-header"
                   onclick="return confirm('Send this contract for signature to the employee?');">
                    <i class="ti ti-send me-1"></i> Send for Sign
                </a>
            <?php endif; ?>
        
            <?php if (staff_can('edit', 'contracts') && $isSigned): ?>
                <a href="<?= site_url('contracts/mark_expired/' . (int)$contract['id']); ?>"
                   class="btn btn-danger btn-header"
                   onclick="return confirm('Mark this contract as expired? This cannot be undone.');">
                    <i class="ti ti-clock-off me-1"></i> Mark Expired
                </a>
            <?php endif; ?>

            <!-- Delete Button -->
            <?php if (staff_can('delete', 'contracts')): ?>
                <?= delete_link([
                'url' => 'contracts/delete/' . (int)$contract['id'],
                'label' => '',
                'class' => 'btn btn-light-danger btn-header',
                'message' => '',                                             
                ]) ?>
            <?php endif; ?>
            
        </div>

    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <!-- Staff Information Card -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-muted">
                        <?= user_profile_image($contract['fullname'] ?? 'N/A'); ?>
                    </strong>
                    <span class="badge bg-primary">Contract Holder</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Employee ID</dt>
                        <dd class="col-7">
                            <?= !empty($contract['emp_id']) ? emp_id_display($contract['emp_id']) : '—'; ?>
                        </dd>

                        <dt class="col-5">Job Title</dt>
                        <dd class="col-7">
                            <?= !empty($contract['emp_title']) ? resolve_emp_title($contract['emp_title']) : '—'; ?>
                        </dd>

                        <dt class="col-5">Department</dt>
                        <dd class="col-7">
                            <?= !empty($contract['department_name']) ? html_escape($contract['department_name']) : '—'; ?>
                        </dd>

                        <dt class="col-5">Contract Type</dt>
                        <dd class="col-7">
                            <?= !empty($contract['contract_type']) ? html_escape($contract['contract_type']) : '—'; ?>
                        </dd>

                        <dt class="col-5">Employment Type</dt>
                        <dd class="col-7">
                            <?= !empty($contract['employment_type']) ? html_escape($contract['employment_type']) : '—'; ?>
                        </dd>

                        <dt class="col-5">Joining Date</dt>
                        <dd class="col-7">
                            <?= format_date($contract['emp_joining']) ?: '—'; ?>
                        </dd>

                        <dt class="col-5">Current Salary</dt>
                        <dd class="col-7">
                            <?= !empty($contract['current_salary']) ? c_format((float)$contract['current_salary']) : '—'; ?>
                        </dd>

                        <dt class="col-5">Notice Period</dt>
                        <dd class="col-7">
                            <?= (int)$contract['notice_period_days']; ?> days
                        </dd>

                        <dt class="col-5">Is Renewable</dt>
                        <dd class="col-7">
                            <?php if (!empty($contract['is_renewable'])): ?>
                                <span class="badge bg-light-primary">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-5">Parent Contract</dt>
                        <dd class="col-7">
                            <?php if (!empty($contract['parent_contract_id'])): ?>
                                <a href="<?= site_url('contracts/view/' . $contract['parent_contract_id']); ?>"
                                   class="text-primary"
                                   target="_blank">
                                    View #<?= $contract['parent_contract_id']; ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Contract Timeline Card -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-muted"><i class="ti ti-timeline-event-minus me-2"></i>Contract Timeline</strong>
                    <span class="badge bg-<?= $contract['status'] === 'signed'
                            ? 'success'
                            : ($contract['status'] === 'draft'
                                ? 'warning'
                                : ($contract['status'] === 'expired' ? 'danger' : 'secondary')); ?> ms-2">
                        <?= ucfirst(html_escape($contract['status'])); ?>
                    </span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Start Date</dt>
                        <dd class="col-7">
                            <?= format_date($contract['start_date']) ?: '—'; ?>
                        </dd>

                        <?php if (!empty($contract['sent_at'])): ?>
                            <dt class="col-5">Sent for Signing</dt>
                            <dd class="col-7">
                                <?= format_datetime($contract['sent_at']) ?: '—'; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['signed_at'])): ?>
                            <dt class="col-5">Signed Date</dt>
                            <dd class="col-7">
                                <?= format_datetime($contract['signed_at']) ?: '—'; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['end_date'])): ?>
                            <dt class="col-5">End Date</dt>
                            <dd class="col-7">
                                <?= format_date($contract['end_date']) ?: '—'; ?>
                                <?php if (strtotime($contract['end_date']) < time()): ?>
                                    | <small class="text-danger">
                                        Expired <?= floor((time() - strtotime($contract['end_date'])) / (60 * 60 * 24)); ?> days ago
                                      </small>
                                <?php endif; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['renew_at'])): ?>
                            <dt class="col-5">Renewed At</dt>
                            <dd class="col-7">
                                <?= format_datetime($contract['renew_at']) ?: '—'; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['last_renew_at'])): ?>
                            <dt class="col-5">Last Renewed</dt>
                            <dd class="col-7">
                                <?= format_datetime($contract['last_renew_at']) ?: '—'; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['created_at'])): ?>
                            <dt class="col-5">Created At</dt>
                            <dd class="col-7">
                                <?= format_datetime($contract['created_at']) ?: '—'; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['updated_at'])): ?>
                            <dt class="col-5">Last Updated</dt>
                            <dd class="col-7">
                                <?= format_datetime($contract['updated_at']) ?: '—'; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($contract['updated_by'])): ?>
                            <dt class="col-5">Updated By</dt>
                            <dd class="col-7">
                            <?= user_profile_image((int)$contract['updated_by']); ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Internal Notes Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong class="text-muted"><i class="ti ti-notes me-2"></i>Internal Notes</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($contract['internal_notes'])): ?>
                        <div class="bg-light-primary p-3 rounded border small">
                            <?= nl2br(html_escape($contract['internal_notes'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="ti ti-notes-off mb-2" style="font-size: 2rem;"></i>
                            <div>No internal notes added for this contract.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>

        <div class="col-lg-8">
            <!-- Contract Details: Embedded Contract Viewer -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-muted"><i class="ti ti-file-text me-2"></i>Contract Details <i class="ti ti-dots-vertical"></i> <span class="small text-muted"><?= html_escape($contract['contract_file']); ?></span> 
                    <i class="ti ti-dots-vertical"></i>
                    <span class="badge bg-light-primary small">
                        Version <?= (int)$contract['version']; ?>
                    </span>
                    </strong>
                    
                        <?php if ($canDownload): ?>
                                <a href="<?= $contract_file_path; ?>"
                                   class="btn btn-light-primary btn-header"
                                   target="_blank"
                                   download>
                                    <i class="ti ti-download me-1"></i> Download
                                </a>
                        <?php endif; ?>                 
                </div>
                <div class="card-body">
                    <?php if (!empty($contract_file_path)): ?>
                    
                        <!-- Fixed-height, scrollable embedded viewer -->
                        <div class="border rounded overflow-hidden" style="height: 700px;">
                            <?php if ($is_pdf): ?>
                                <!-- Inline PDF viewer -->
                                <iframe src="<?= $contract_file_path; ?>#toolbar=0&navpanes=0&scrollbar=1"
                                        style="width:100%; height:100%; border:none;"
                                        frameborder="0"
                                        title="Contract PDF Viewer">
                                    Your browser does not support PDF viewing.
                                </iframe>

                            <?php elseif ($is_image): ?>
                                <!-- Scrollable image viewer -->
                                <div class="h-100 overflow-auto d-flex justify-content-center align-items-start p-3"
                                     style="background:#f8f9fa;">
                                    <img src="<?= $contract_file_path; ?>"
                                         alt="Contract Document"
                                         class="img-fluid rounded shadow-sm"
                                         style="max-width:100%; height:auto;">
                                </div>

                            <?php elseif ($is_document): ?>
                                <!-- Google Docs Viewer for Word docs -->
                                <iframe src="https://docs.google.com/gview?url=<?= urlencode($contract_file_path); ?>&embedded=true"
                                        style="width:100%; height:100%; border:none;"
                                        frameborder="0"
                                        title="Contract Document Viewer">
                                    Your browser does not support document viewing.
                                </iframe>

                            <?php else: ?>
                                <!-- Generic file type with no inline preview -->
                                <div class="text-center py-5">
                                    <i class="ti ti-file-text mb-3" style="font-size: 4rem; color: #6c757d;"></i>
                                    <h5>File Preview Not Available</h5>
                                    <p class="text-muted mb-0">
                                        This file type cannot be previewed directly in the browser.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="ti ti-alert-circle me-2"></i>
                            No contract document has been uploaded for this contract.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if (isset($is_self_view) && $is_self_view && !$isSigned && ($contract['status'] ?? '') === 'sent'): ?>
<div class="modal fade" id="signContractModal" tabindex="-1" aria-labelledby="signContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= site_url('contracts/sign/' . (int)$contract['id']); ?>" method="post" class="app-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="signContractModalLabel">
                        <i class="ti ti-signature me-1"></i> Sign Contract
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        You are about to digitally sign this contract. Please review the document carefully.
                        By typing your full name below and clicking <strong>Confirm Signature</strong>, you agree that
                        this constitutes your legal electronic signature on this agreement.
                    </p>

                    <div class="mb-3">
                        <label for="signature_text" class="form-label">Type your full legal name <span class="text-danger">*</span></label>
                        <input type="text"
                               name="signature_text"
                               id="signature_text"
                               class="form-control"
                               required
                               placeholder="e.g. <?= html_escape($contract['fullname'] ?? 'Your full name'); ?>">
                    </div>

                    <input type="hidden" name="sign_method" value="portal">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ti ti-check me-1"></i> Confirm Signature
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
