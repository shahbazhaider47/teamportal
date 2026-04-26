<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="editExitEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form method="post" action="<?= site_url('users/update_exit_employee') ?>" class="app-form">
      <input type="hidden" name="exit_id" value="<?= (int)$exit['id'] ?>">
      <input type="hidden" name="user_id" value="<?= (int)$exit['user_id'] ?>">

      <div class="modal-content">

        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white">
            <i class="ti ti-edit me-2"></i>Edit Exit Information
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body row g-3 pa-30">

          <!-- Exit Type -->
          <div class="col-md-4">
            <label class="form-label">Exit Type</label>
            <select class="form-select" name="exit_type" required>
              <?php
                $types = [
                  'Resigned','Retired','Terminated','Lay Off','Contract End',
                  'Death in Service','Mutual Separation','Medical Condition','Other'
                ];
                foreach ($types as $t):
              ?>
                <option value="<?= $t ?>" <?= $exit['exit_type']===$t?'selected':'' ?>>
                  <?= $t ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Exit Dates -->
          <div class="col-md-4">
            <label class="form-label">Exit Date</label>
            <input type="date" class="form-control"
                   name="exit_date"
                   value="<?= html_escape($exit['exit_date']) ?>"
                   required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Last Working Day</label>
            <input type="date" class="form-control"
                   name="last_working_date"
                   value="<?= html_escape($exit['last_working_date']) ?>">
          </div>

          <!-- Reason -->
          <div class="col-md-12">
            <label class="form-label">Exit Reason / Details</label>
            <textarea class="form-control" name="reason" rows="2"><?= html_escape($exit['reason']) ?></textarea>
          </div>

          <!-- Interview -->
          <div class="col-md-4">
            <label class="form-label">Exit Interview Date</label>
            <input type="date" class="form-control"
                   name="exit_interview_date"
                   value="<?= html_escape($exit['exit_interview_date']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Exit Interview By</label>
            <select class="form-select" name="exit_interview_conducted_by">
              <option value="">Select</option>
              <?php foreach ($interviewers as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"
                  <?= ((int)$exit['exit_interview_conducted_by']===(int)$emp['id'])?'selected':'' ?>>
                  <?= html_escape($emp['fullname']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Switches -->
          <?php
            $switches = [
              'notice_period_served' => 'Notice Period Served',
              'checklist_completed'  => 'Checklist Completed',
              'assets_returned'      => 'Assets Returned',
              'nda_signed'           => 'NDA Signed',
            ];
            foreach ($switches as $key => $label):
          ?>
          <div class="col-md-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox"
                     name="<?= $key ?>" value="1"
                     <?= !empty($exit[$key])?'checked':'' ?>>
              <label class="form-check-label"><?= $label ?></label>
            </div>
          </div>
          <?php endforeach; ?>

          <!-- Settlement -->
          <div class="col-md-4">
            <label class="form-label">Final Settlement Amount</label>
            <input type="number" class="form-control" step="0.01" min="0"
                   name="final_settlement_amount"
                   value="<?= html_escape($exit['final_settlement_amount']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Final Settlement Date</label>
            <input type="date" class="form-control"
                   name="final_settlement_date"
                   value="<?= html_escape($exit['final_settlement_date']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Exit Status</label>
            <select class="form-select" name="exit_status">
              <?php foreach (['Pending','Completed','On Hold'] as $st): ?>
                <option value="<?= $st ?>" <?= $exit['exit_status']===$st?'selected':'' ?>>
                  <?= $st ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Remarks -->
          <div class="col-md-12">
            <label class="form-label">HR Remarks</label>
            <textarea class="form-control" name="remarks" rows="2"><?= html_escape($exit['remarks']) ?></textarea>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-1"></i> Update Exit
          </button>
        </div>

      </div>
    </form>
  </div>
</div>
