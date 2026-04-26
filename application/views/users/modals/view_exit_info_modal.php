<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="exitDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-primary">
        <div class="w-100">
          <h5 class="modal-title text-white">
            <i class="ti ti-logout me-2"></i> Employee Exit Details
            <i class="ti ti-chevron-right me-1"></i>
            <small class="text-white" id="exitEmpName"></small>
          </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="card mb-3">
          <div class="card-header bg-light-primary fw-semibold p-3">
            Exit Information
          </div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="text-muted">Exit Type</label>
                <div class="fw-semibold" id="m_exit_type">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Exit Status</label>
                <div class="fw-semibold" id="m_exit_status">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Notice Period Served</label>
                <div id="m_notice_served">—</div>
              </div>

              <div class="col-md-4">
                <label class="text-muted">Exit Date</label>
                <div id="m_exit_date">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Last Working Day</label>
                <div id="m_last_working">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Interview Date</label>
                <div id="m_interview_date">—</div>
              </div>

              <div class="col-md-4">
                <label class="text-muted">Interviewed By</label>
                <div id="m_interview_by">—</div>
              </div>

              <div class="col-md-4">
                <label class="text-muted">Checklist Completed</label>
                <div id="m_checklist">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Assets Returned</label>
                <div id="m_assets_returned">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">NDA Signed</label>
                <div id="m_nda">—</div>
              </div>

              <div class="col-md-4">
                <label class="text-muted">Settlement Amount</label>
                <div class="fw-semibold" id="m_final_settlement">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Settlement Date</label>
                <div id="m_final_date">—</div>
              </div>

              <div class="col-md-4">
                <label class="text-muted">Created At</label>
                <div id="m_created_at">—</div>
              </div>
              <div class="col-md-4">
                <label class="text-muted">Last Updated</label>
                <div id="m_updated_at">—</div>
              </div>
              
            </div>
          </div>
        </div>
        
        <!-- SECTION 4: HR NOTES -->
        <div class="card mb-3">
          <div class="card-header bg-light-primary fw-semibold">
            HR Notes
          </div>
          <div class="card-body small">
            <div class="mb-3">
              <label class="text-muted">Exit Reason</label>
              <div id="m_reason">—</div>
            </div>
            <div>
              <label class="text-muted">HR Remarks</label>
              <div id="m_remarks">—</div>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Edit</button>        
      </div>

    </div>
  </div>
</div>
