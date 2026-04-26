<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade"
     id="statusModal"
     tabindex="-1"
     aria-hidden="true"
     data-reactivate-url="<?= site_url('profile_editor/reactivate') ?>">
    
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-lock-access"></i> Reactivate Employee
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        
        <div class="card mb-3">
          <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="text-muted small mb-0">Selected Employee</div>
                <div class="fw-bold" id="reactivate_emp_name">—</div>
              </div>
              <div class="text-end">
                <div class="text-muted small mb-0">EMP ID</div>
                <div class="badge bg-light-primary text-dark" id="reactivate_emp_id">—</div>
              </div>
            </div>
        
            <div class="text-muted small mt-2">
              <strong>Reactivating will:</strong>
              Set user status to <strong>Active</strong>, mark as <strong>Rejoined</strong>,
              delete exit record and log rejoin history.
            </div>
          </div>
        </div>
        
        <form method="post" id="reactivateForm" class="app-form">

          <input type="hidden" name="user_id" id="reactivate_user_id">

          <div class="mb-2">
            <label class="form-label small">Rejoin Date <span class="text-danger">*</span></label>
            <input type="date" name="rejoin_date" class="form-control basic-date flatpickr-input" placeholder="YYYY-MM-DD" required>
          </div>

          <div class="mb-2">
            <label class="form-label small">Rejoin Reason <span class="text-danger">*</span></label>
            <select name="rejoin_reson" id="rejoin_reson" class="form-select form-select-sm" required>
              <option value="">Select reason</option>
              <option value="Rehired">Rehired</option>
              <option value="Contract Renewed">Contract Renewed</option>
              <option value="Seasonal Return">Seasonal Return</option>
              <option value="Data Correction">Data Correction</option>
              <option value="Left by mistake">Left by mistake</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="mb-2 d-none" id="rejoin_custom_wrap">
            <label class="form-label small">Custom Reason</label>
            <input type="text" name="rejoin_reson_custom" class="form-control form-control-sm">
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="ti ti-check"></i> Reactivate
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>
</div>