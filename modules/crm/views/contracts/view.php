<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- ── Page Header ── -->
  <div class="crm-page-header mb-3">
    <div class="crm-page-icon me-3"><i class="ti ti-file-text"></i></div>
    <div class="flex-grow-1">
      <div class="crm-page-title"><?= html_escape($contract['contract_code']) ?></div>
      <div class="crm-page-sub">
        <?= html_escape($contract['client_name'] ?? '') ?>
        &mdash; <?= ucfirst(html_escape($contract['contract_type'])) ?>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
      <?php if (!empty($can['edit']) && !in_array($contract['status'], ['terminated', 'cancelled'])): ?>
        <a href="<?= site_url('crm/contracts/edit/' . $contract['id']) ?>" class="btn btn-light-primary btn-header">
          <i class="ti ti-edit me-1"></i> Edit
        </a>
      <?php endif; ?>
      <?php if (!empty($can['create']) && $contract['status'] === 'active'): ?>
        <button type="button" class="btn btn-light-success btn-header" data-bs-toggle="modal" data-bs-target="#renewModal">
          <i class="ti ti-refresh me-1"></i> Renew
        </button>
      <?php endif; ?>
      <?php if (!empty($can['edit']) && in_array($contract['status'], ['draft', 'pending_signature'])): ?>
        <button type="button" class="btn btn-success btn-header" data-bs-toggle="modal" data-bs-target="#activateModal">
          <i class="ti ti-circle-check me-1"></i> Activate
        </button>
      <?php endif; ?>
      <?php if (!empty($can['edit']) && $contract['status'] === 'active'): ?>
        <button type="button" class="btn btn-light-danger btn-header" data-bs-toggle="modal" data-bs-target="#terminateModal">
          <i class="ti ti-ban me-1"></i> Terminate
        </button>
      <?php endif; ?>
      <a href="<?= site_url('crm/contracts') ?>" class="btn btn-light-secondary btn-header">
        <i class="ti ti-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  <div class="row g-3">

    <!-- ════ LEFT ════ -->
    <div class="col-xl-8 col-lg-7">

      <!-- Identity card -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-file-description"></i><span>Contract Overview</span>
          <span class="ms-auto"><?= contract_status_badge($contract['status']) ?></span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="crm-detail-label">Contract Number</div>
              <div class="crm-detail-value fw-semibold"><?= html_escape($contract['contract_code']) ?></div>
            </div>
            <div class="col-md-4">
              <div class="crm-detail-label">Type</div>
              <div class="crm-detail-value"><?= ucfirst(html_escape($contract['contract_type'])) ?></div>
            </div>
            <div class="col-md-4">
              <div class="crm-detail-label">Version</div>
              <div class="crm-detail-value">v998
                <?php if (!empty($contract['parent_contract_id'])): ?>
                  <a href="<?= site_url('crm/contracts/view/' . $contract['parent_contract_id']) ?>"
                     class="small text-muted ms-1">(parent #<?= (int)$contract['parent_contract_id'] ?>)</a>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-12">
              <div class="crm-detail-label">Title</div>
              <div class="crm-detail-value fw-semibold"><?= html_escape($contract['contract_title']) ?></div>
            </div>
            <?php if (!empty($contract['description'])): ?>
            <div class="col-md-12">
              <div class="crm-detail-label">Description</div>
              <div class="crm-detail-value text-muted"><?= nl2br(html_escape($contract['description'])) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Dates -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-calendar"></i><span>Dates &amp; Renewal</span></div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="crm-detail-label">Start Date</div>
              <div class="crm-detail-value"><?= $contract['start_date'] ? date('M j, Y', strtotime($contract['start_date'])) : '—' ?></div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">End Date</div>
              <?php
                $daysLeft = $contract['end_date'] ? (int)ceil((strtotime($contract['end_date']) - time()) / 86400) : null;
                $expColor = ($daysLeft !== null && $daysLeft <= 30 && $contract['status'] === 'active') ? 'color:#dc2626;font-weight:600' : '';
              ?>
              <div class="crm-detail-value" style="<?= $expColor ?>">
                <?= $contract['end_date'] ? date('M j, Y', strtotime($contract['end_date'])) : '<span class="text-muted">Open-ended</span>' ?>
                <?php if ($daysLeft !== null && $daysLeft > 0 && $daysLeft <= 60 && $contract['status'] === 'active'): ?>
                  <div class="small text-warning"><?= $daysLeft ?> days remaining</div>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">Signed Date</div>
              <div class="crm-detail-value"><?= $contract['signed_date'] ? date('M j, Y', strtotime($contract['signed_date'])) : '—' ?></div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">Next Review</div>
              <div class="crm-detail-value">NA</div>
            </div>
            <div class="col-md-2">
              <div class="crm-detail-label">Auto Renew</div>
              <div class="crm-detail-value">
                <?= $contract['auto_renew'] ? '<span class="badge bg-success-subtle text-success">Yes</span>' : '<span class="badge bg-secondary-subtle text-secondary">No</span>' ?>
              </div>
            </div>
            <div class="col-md-2">
              <div class="crm-detail-label">Renewal Term</div>
              <div class="crm-detail-value"><?= $contract['renewal_period'] ? (int)$contract['renewal_period'] . ' months' : '—' ?></div>
            </div>
            <div class="col-md-2">
              <div class="crm-detail-label">Renewal Notice</div>
              <div class="crm-detail-value"><?= $contract['renewal_period'] ? (int)$contract['renewal_period'] . ' days' : '—' ?></div>
            </div>
            <div class="col-md-2">
              <div class="crm-detail-label">Notice Period</div>
              <div class="crm-detail-value"><?= $contract['notice_period_days'] ? (int)$contract['notice_period_days'] . ' days' : '—' ?></div>
            </div>
            <div class="col-md-2">
              <div class="crm-detail-label">Times Renewed</div>
              <div class="crm-detail-value"><?= (int)($contract['renewal_count'] ?? 0) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Billing -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-receipt-2"></i><span>Billing Snapshot</span></div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="crm-detail-label">Billing Model</div>
              <div class="crm-detail-value"><?= ucfirst(str_replace('_', ' ', $contract['billing_model'])) ?></div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">Rate</div>
              <div class="crm-detail-value fw-semibold">
                <?php if (!empty($contract['rate_value'])): ?>
                  <?= $contract['billing_model'] === 'percentage'
                      ? number_format($contract['rate_value'], 2) . '%'
                      : ($contract['rate_currency'] ?? 'USD') . ' ' . number_format($contract['rate_value'], 2) ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">Invoice Frequency</div>
              <div class="crm-detail-value"><?= ucfirst(str_replace('-', '-', $contract['invoice_frequency'])) ?></div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">Payment Terms</div>
              <div class="crm-detail-value">Net 15</div>
            </div>
            <?php if (!empty($contract['minimum_monthly_fee'])): ?>
            <div class="col-md-3">
              <div class="crm-detail-label">Minimum Monthly Fee</div>
              <div class="crm-detail-value">$<?= number_format($contract['minimum_monthly_fee'], 2) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Scope -->
      <?php if (!empty($contract['services_included']) || !empty($contract['sla_terms'])): ?>
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-briefcase"></i><span>Services &amp; Scope</span></div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <?php if (!empty($contract['services_included'])): ?>
            <div class="col-md-6">
              <div class="crm-detail-label">Services Included</div>
              <div class="crm-detail-value text-muted"><?= nl2br(html_escape($contract['services_included'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($contract['services_excluded'])): ?>
            <div class="col-md-6">
              <div class="crm-detail-label">Services Excluded</div>
              <div class="crm-detail-value text-muted"><?= nl2br(html_escape($contract['services_excluded'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($contract['specialties_covered'])): ?>
            <div class="col-md-4">
              <div class="crm-detail-label">Specialties</div>
              <div class="crm-detail-value"><?= html_escape($contract['specialties_covered']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($contract['sla_terms'])): ?>
            <div class="col-md-8">
              <div class="crm-detail-label">SLA Terms</div>
              <div class="crm-detail-value"><?= html_escape($contract['sla_terms']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Signing -->
      <?php if (!empty($contract['signed_by_client']) || !empty($contract['external_ref'])): ?>
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-signature"></i><span>Signing Details</span></div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="crm-detail-label">Signed By (Client)</div>
              <div class="crm-detail-value"><?= html_escape($contract['signed_by_client'] ?? '—') ?></div>
            </div>
            <div class="col-md-4">
              <div class="crm-detail-label">Signed By (RCM)</div>
              <div class="crm-detail-value"><?= html_escape($contract['signed_by_rcm'] ?? '—') ?></div>
            </div>
            <div class="col-md-2">
              <div class="crm-detail-label">Method</div>
              <div class="crm-detail-value"><?= ucfirst($contract['signature_method'] ?? '—') ?></div>
            </div>
            <?php if (!empty($contract['external_ref'])): ?>
            <div class="col-md-2">
              <div class="crm-detail-label">External Ref</div>
              <div class="crm-detail-value small text-muted"><?= html_escape($contract['external_ref']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Termination info (if applicable) -->
      <?php if ($contract['status'] === 'terminated'): ?>
      <div class="crm-form-card mb-3" style="border-color:#fecaca">
        <div class="crm-form-card-header" style="background:#fef2f2;border-color:#fecaca">
          <i class="ti ti-ban" style="color:#dc2626"></i>
          <span style="color:#b91c1c">Termination Details</span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="crm-detail-label">Terminated Date</div>
              <div class="crm-detail-value text-danger fw-semibold">
                <?= $contract['terminated_date'] ? date('M j, Y', strtotime($contract['terminated_date'])) : '—' ?>
              </div>
            </div>
            <div class="col-md-3">
              <div class="crm-detail-label">Initiated By</div>
              <div class="crm-detail-value"><?= ucfirst($contract['termination_initiated_by'] ?? '—') ?></div>
            </div>
            <div class="col-md-6">
              <div class="crm-detail-label">Reason</div>
              <div class="crm-detail-value text-muted"><?= nl2br(html_escape($contract['termination_reason'] ?? '—')) ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Files -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-paperclip"></i><span>Attached Files</span>
          <span class="ms-auto badge bg-secondary-subtle text-secondary"><?= count($files ?? []) ?></span>
        </div>
        <div class="crm-form-card-body <?= empty($files) ? 'text-center py-4' : 'p-0' ?>">
          <?php if (!empty($files)): ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0 crm-table-light">
                <thead><tr><th>File Name</th><th>Type</th><th>Uploaded</th><th>By</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($files as $f): ?>
                  <tr>
                    <td><i class="ti ti-file me-1 text-muted"></i><?= html_escape($f['file_name'] ?? $f['original_name'] ?? '') ?></td>
                    <td><span class="badge bg-light text-dark border small"><?= strtoupper(html_escape($f['file_type'] ?? '')) ?></span></td>
                    <td class="small text-muted"><?= isset($f['created_at']) ? date('M j, Y', strtotime($f['created_at'])) : '' ?></td>
                    <td class="small text-muted"><?= html_escape($f['uploader_name'] ?? '') ?></td>
                    <td>
                      <a href="<?= site_url('crm/files/download/' . $f['id']) ?>"
                         class="btn btn-icon btn-xs btn-light-primary" title="Download">
                        <i class="ti ti-download"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <i class="ti ti-paperclip d-block mb-2" style="font-size:2rem;opacity:.3"></i>
            <div class="text-muted small">No files attached to this contract yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Activity Log -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-activity"></i><span>Activity Log</span>
          <span class="ms-auto badge bg-secondary-subtle text-secondary"><?= count($activities ?? []) ?></span>
        </div>
        <div class="crm-form-card-body p-0">
          <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $act): ?>
            <div class="d-flex align-items-start gap-3 px-3 py-2 border-bottom" style="border-color:#f1f5f9!important">
              <div style="width:30px;height:30px;border-radius:6px;background:#f0fdfa;color:#056464;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                <i class="ti ti-<?= $act['action'] === 'created' ? 'plus' : ($act['action'] === 'terminated' ? 'ban' : 'edit') ?>"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="small fw-semibold text-dark"><?= html_escape($act['description'] ?? $act['action']) ?></div>
                <div class="small text-muted"><?= isset($act['created_at']) ? date('M j, Y g:ia', strtotime($act['created_at'])) : '' ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center py-4 text-muted small">No activity logged yet.</div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /col-xl-8 -->

    <!-- ════ RIGHT SIDEBAR ════ -->
    <div class="col-xl-4 col-lg-5">

      <!-- Quick actions -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-bolt"></i><span>Quick Actions</span></div>
        <div class="crm-form-card-body d-flex flex-column gap-2">
          <?php if (!empty($can['edit']) && !in_array($contract['status'], ['terminated', 'cancelled'])): ?>
            <a href="<?= site_url('crm/contracts/edit/' . $contract['id']) ?>" class="btn btn-light-primary w-100">
              <i class="ti ti-edit me-1"></i> Edit Contract
            </a>
          <?php endif; ?>
          <?php if (!empty($can['create']) && $contract['status'] === 'active'): ?>
            <button class="btn btn-light-success w-100" data-bs-toggle="modal" data-bs-target="#renewModal">
              <i class="ti ti-refresh me-1"></i> Create Renewal
            </button>
          <?php endif; ?>
          <?php if (!empty($can['edit']) && in_array($contract['status'], ['draft', 'pending_signature'])): ?>
            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#activateModal">
              <i class="ti ti-circle-check me-1"></i> Activate Contract
            </button>
          <?php endif; ?>
          <?php if (!empty($can['edit']) && $contract['status'] === 'active'): ?>
            <button class="btn btn-light-danger w-100" data-bs-toggle="modal" data-bs-target="#terminateModal">
              <i class="ti ti-ban me-1"></i> Terminate Contract
            </button>
          <?php endif; ?>
          <?php if (!empty($can['delete']) && $contract['status'] !== 'active'): ?>
            <form method="post" action="<?= site_url('crm/contracts/delete/' . $contract['id']) ?>"
                  onsubmit="return confirm('Permanently delete this contract?')">
              <button type="submit" class="btn btn-light-danger w-100">
                <i class="ti ti-trash me-1"></i> Delete Contract
              </button>
            </form>
          <?php endif; ?>
          <a href="<?= site_url('crm/contracts') ?>" class="btn btn-light-secondary w-100">
            <i class="ti ti-arrow-left me-1"></i> Back to Contracts
          </a>
        </div>
      </div>

      <!-- Version history -->
      <?php if (!empty($versions) && count($versions) > 1): ?>
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-versions"></i><span>Version History</span></div>
        <div class="crm-form-card-body p-0">
          <?php foreach ($versions as $v): ?>
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom <?= $v['id'] === $contract['id'] ? 'bg-primary-subtle' : '' ?>"
                 style="border-color:#f1f5f9!important">
              <div class="flex-grow-1 min-w-0">
                <div class="small fw-semibold <?= $v['id'] === $contract['id'] ? 'text-primary' : 'text-dark' ?>">
                  <?= html_escape($v['contract_code']) ?>
                </div>
                <div class="small text-muted"><?= ucfirst($v['contract_type']) ?> &middot; v<?= (int)$v['contract_version'] ?></div>
              </div>
              <div>
                <?= contract_status_badge($v['status']) ?>
              </div>
              <?php if ($v['id'] !== $contract['id']): ?>
                <a href="<?= site_url('crm/contracts/view/' . $v['id']) ?>"
                   class="btn btn-icon btn-xs btn-light-secondary" title="View">
                  <i class="ti ti-eye"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Internal Notes -->
      <?php if (!empty($contract['internal_notes'])): ?>
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-notes"></i><span>Internal Notes</span></div>
        <div class="crm-form-card-body">
          <div class="small text-muted" style="white-space:pre-line"><?= html_escape($contract['internal_notes']) ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Audit -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header"><i class="ti ti-info-circle"></i><span>Record Info</span></div>
        <div class="crm-form-card-body">
          <table class="table table-sm mb-0" style="font-size:12px">
            <tr><td class="text-muted">Created</td><td class="fw-semibold"><?= date('M j, Y', strtotime($contract['created_at'])) ?></td></tr>
            <tr><td class="text-muted">Updated</td><td class="fw-semibold"><?= date('M j, Y g:ia', strtotime($contract['updated_at'])) ?></td></tr>
            <?php if (!empty($contract['signed_date'])): ?>
            <tr><td class="text-muted">Signed</td><td class="fw-semibold"><?= date('M j, Y', strtotime($contract['signed_date'])) ?></td></tr>
            <?php endif; ?>
          </table>
        </div>
      </div>

    </div><!-- /sidebar -->

  </div><!-- /row -->

</div>

<!-- ── Terminate Modal ── -->
<div class="modal fade" id="terminateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none">
      <form method="post" action="<?= site_url('crm/contracts/terminate/' . $contract['id']) ?>">
        <div class="app-modal-header">
          <div class="app-modal-header-left">
            <div class="app-modal-icon app-modal-icon-danger"><i class="ti ti-ban"></i></div>
            <div><div class="app-modal-title">Terminate Contract</div><div class="app-modal-subtitle"><?= html_escape($contract['contract_code']) ?></div></div>
          </div>
          <button type="button" class="app-modal-close" data-bs-dismiss="modal"><i class="ti ti-x"></i></button>
        </div>
        <div class="app-modal-body">
          <div class="mb-3">
            <label class="crm-label">Termination Date <span class="crm-req">*</span></label>
            <input type="date" name="terminated_date" class="form-control crm-input" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="crm-label">Initiated By</label>
            <select name="termination_initiated_by" class="form-select crm-input">
              <option value="mutual">Mutual Agreement</option>
              <option value="client">Client</option>
              <option value="rcm">RCM</option>
            </select>
          </div>
          <div>
            <label class="crm-label">Reason</label>
            <textarea name="termination_reason" class="form-control crm-input" rows="3"
                      placeholder="Provide reason for termination..."></textarea>
          </div>
        </div>
        <div class="app-modal-footer">
          <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="app-btn-submit app-btn-submit-danger">
            <i class="ti ti-ban"></i> Terminate
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Activate Modal ── -->
<div class="modal fade" id="activateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none">
      <form method="post" action="<?= site_url('crm/contracts/activate/' . $contract['id']) ?>">
        <div class="app-modal-header">
          <div class="app-modal-header-left">
            <div class="app-modal-icon app-modal-icon-success"><i class="ti ti-circle-check"></i></div>
            <div><div class="app-modal-title">Activate Contract</div></div>
          </div>
          <button type="button" class="app-modal-close" data-bs-dismiss="modal"><i class="ti ti-x"></i></button>
        </div>
        <div class="app-modal-body">
          <label class="crm-label">Signed Date</label>
          <input type="date" name="signed_date" class="form-control crm-input" value="<?= date('Y-m-d') ?>">
          <div class="crm-hint mt-1">Leave as today if signed today. Contract status will be set to Active.</div>
        </div>
        <div class="app-modal-footer">
          <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="app-btn-submit"><i class="ti ti-circle-check"></i> Activate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Renew Modal ── -->
<div class="modal fade" id="renewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none">
      <form method="post" action="<?= site_url('crm/contracts/renew/' . $contract['id']) ?>">
        <div class="app-modal-header">
          <div class="app-modal-header-left">
            <div class="app-modal-icon app-modal-icon-teal"><i class="ti ti-refresh"></i></div>
            <div><div class="app-modal-title">Create Renewal</div><div class="app-modal-subtitle">Term: <?= (int)($contract['renewal_term_months'] ?? 12) ?> months</div></div>
          </div>
          <button type="button" class="app-modal-close" data-bs-dismiss="modal"><i class="ti ti-x"></i></button>
        </div>
        <div class="app-modal-body">
          <label class="crm-label">New Start Date</label>
          <input type="date" name="new_start_date" class="form-control crm-input"
                 value="<?= html_escape($contract['end_date'] ?? date('Y-m-d')) ?>">
          <div class="crm-hint mt-1">A new draft contract will be created. Review and activate it separately.</div>
        </div>
        <div class="app-modal-footer">
          <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="app-btn-submit"><i class="ti ti-refresh"></i> Create Renewal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.crm-detail-label{font-size:10.5px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}
.crm-detail-value{font-size:13px;color:#1e293b}
</style>