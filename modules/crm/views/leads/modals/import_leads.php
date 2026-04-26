<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="leadImportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <div class="modal-content">

      <div class="modal-header bg-primary py-2">
        <h6 class="mb-0 text-white"><i class="ti ti-upload"></i> Import Leads (CSV)</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="post" action="<?= site_url('crm_leads/import'); ?>" enctype="multipart/form-data" class="app-form">
        <div class="modal-body">

          <div class="card border-0 card-body bg-light-primary small mb-4">
            CSV must contain a header row. Minimum required column practice_name. You can also include any column names matching your table (e.g. contact_email, contact_phone, lead_status, lead_quality, city, state, etc.).
          </div>

          <label class="form-label">Select CSV File</label>
          <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>

        </div>

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-light-secondary btn-header" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-header"><i class="ti ti-upload"></i> Import</button>
        </div>
      </form>

    </div>
  </div>
</div>