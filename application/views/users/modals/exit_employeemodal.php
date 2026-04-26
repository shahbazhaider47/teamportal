<div class="modal fade" id="exitEmployeeModal" tabindex="-1" aria-labelledby="exitEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="exitEmployeeForm" method="post" action="<?= site_url('users/exit_employee') ?>" class="app-form">
      <input type="hidden" name="user_id" id="exit_user_id">
      <input type="hidden" name="exit_id" id="exit_id">
      <div class="modal-content">
        <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="exitEmployeeModalLabel">
          <i class="ti ti-logout"></i> Exit Employee >
          <?= user_profile($user) ?><span class="ms-2" id="exitEmployeeName"></span>
        </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3 pa-30 app-form">
          
          <div class="col-md-4">
            <label class="form-label">Exit Type <span class="text-danger">*</span></label>
            <select class="form-select" name="exit_type" id="exit_type" required>
              <option value="">Select</option>
              <option value="Resigned">Resigned</option>
              <option value="Retired">Retired</option>
              <option value="Terminated">Terminated</option>
              <option value="Lay Off">Lay Off</option>
              <option value="Contract End">Contract End</option>
              <option value="Death in Service">Death in Service</option>
              <option value="Mutual Separation">Mutual Separation</option>
              <option value="Medical Condition">Medical Condition</option>
              <option value="No Show">No Show</option>
              <option value="Other">Other</option>
            </select>
          </div>
          
          <div class="col-md-4">
            <label class="form-label">Exit Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control basic-date flatpickr-input" placeholder="YYYY-MM-DD" name="exit_date" id="exit_date" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Last Working Day</label>
            <input type="date"class="form-control basic-date flatpickr-input" placeholder="YYYY-MM-DD" name="last_working_date" id="last_working_date">
          </div>
          <div class="col-md-12">
            <label class="form-label">Exit Reason / Details</label>
            <textarea class="form-control" name="reason" id="exit_reason" rows="2"></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Exit Interview Date</label>
            <input type="date" class="form-control basic-date flatpickr-input" placeholder="YYYY-MM-DD" name="exit_interview_date" id="exit_interview_date">
          </div>
            
            <div class="col-md-4">
              <label class="form-label">Exit Interview By</label>
                <select class="form-select" name="exit_interview_conducted_by" id="exit_interview_conducted_by">
                    <option value="">Select interviewer</option>
                
                    <?php if (!empty($interviewers) && is_array($interviewers)): ?>
                        <?php foreach ($interviewers as $emp): ?>
                            <?php
                                $id = (int) ($emp['id'] ?? 0);
                
                                // Resolve display name
                                $name = trim($emp['fullname'] ?? '');
                
                                if ($name === '') {
                                    $fn = trim($emp['firstname'] ?? '');
                                    $ln = trim($emp['lastname'] ?? '');
                                    $name = trim($fn . ' ' . $ln);
                                }
                
                                if ($name === '') {
                                    $name = $emp['username']
                                        ?? $emp['email']
                                        ?? ('User #' . $id);
                                }
                
                                // Role badge
                                $role = !empty($emp['user_role'])
                                    ? ' — ' . ucfirst($emp['user_role'])
                                    : '';
                            ?>
                            <option value="<?= $id ?>">
                                <?= html_escape($name . $role) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
          <div class="col-md-3 mb-3">
          <div class="form-check form-switch d-flex align-items-center">
            <input class="form-check-input" type="checkbox" name="checklist_completed" id="checklist_completed">
            <label class="form-check-label ms-2" for="checklist_completed">Checklist Completed</label>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="form-check form-switch d-flex align-items-center">
            <input class="form-check-input" type="checkbox" name="assets_returned" id="assets_returned">
            <label class="form-check-label ms-2" for="assets_returned">Assets Returned</label>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="form-check form-switch d-flex align-items-center">
            <input class="form-check-input" type="checkbox" name="nda_signed" id="nda_signed">
            <label class="form-check-label ms-2" for="nda_signed">NDA Signed</label>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="form-check form-switch d-flex align-items-center">
            <input class="form-check-input" type="checkbox" name="notice_period_served" id="notice_period_served">
            <label class="form-check-label ms-2" for="notice_period_served">Notice Period Served</label>
          </div>
        </div>
          <div class="col-md-4">
            <label class="form-label">Final Settlement Amount</label>
            <input type="number" class="form-control" name="final_settlement_amount" id="final_settlement_amount" step="0.01" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Final Settlement Date</label>
            <input type="date" class="form-control basic-date flatpickr-input" placeholder="YYYY-MM-DD" name="final_settlement_date" id="final_settlement_date">
          </div>
          <div class="col-md-4">
            <label class="form-label">Exit Status</label>
            <select class="form-select" name="exit_status" id="exit_status">
              <option value="Pending" selected>Pending</option>
              <option value="Completed">Completed</option>
              <option value="On Hold">On Hold</option>
            </select>
          </div> 

          <div class="col-md-12">
            <label class="form-label">HR Remarks</label>
            <textarea class="form-control" name="remarks" id="exit_remarks" rows="2"></textarea>
          </div>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Exit Employee</button>
        </div>
      </div>
    </form>
  </div>
</div>

