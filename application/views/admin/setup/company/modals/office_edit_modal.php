<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade"
     id="editCompanyOfficeModal"
     tabindex="-1"
     aria-labelledby="editCompanyOfficeLabel"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">

      <form method="post"
            action="<?= site_url('admin/setup/company/update_office'); ?>"
            class="app-form">

        <input type="hidden" name="office_id" id="edit_office_id">

        <!-- HEADER -->
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="editCompanyOfficeLabel">
            <i class="ti ti-pencil me-2"></i> Edit Company Office
          </h5>
          <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body app-form">
          <div class="row g-3">

            <div class="col-md-4">
              <label class="form-label">Office Code <span class="text-danger">*</span></label>
              <input type="text" name="office_code" class="form-control" required>
            </div>

            <div class="col-md-8">
              <label class="form-label">Office Name <span class="text-danger">*</span></label>
              <input type="text" name="office_name" class="form-control" required>
            </div>

            <div class="col-6">
              <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
              <input type="text" name="address_line_1" class="form-control" required>
            </div>

            <div class="col-6">
              <label class="form-label">Address Line 2</label>
              <input type="text" name="address_line_2" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">City <span class="text-danger">*</span></label>
              <input type="text" name="city" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">State <span class="text-danger">*</span></label>
              <input type="text" name="state" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Postal Code <span class="text-danger">*</span></label>
              <input type="text" name="postal_code" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Country <span class="text-danger">*</span></label>
              <input type="text" name="country" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Office Phone <span class="text-danger">*</span></label>
              <input type="text" name="phone" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Office Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Timezone <span class="text-danger">*</span></label>
              <select name="timezone" class="form-select" required>
                <?php foreach (timezone_identifiers_list() as $tz): ?>
                  <option value="<?= html_escape($tz) ?>"><?= html_escape($tz) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Currency <span class="text-danger">*</span></label>
              <select name="currency" class="form-select" required>
                <option value="USD">USD</option>
                <option value="PKR">PKR</option>
                <option value="GBP">GBP</option>
                <option value="EUR">EUR</option>
                <option value="AED">AED</option>
              </select>
            </div>

            <div class="col-md-4 d-flex align-items-end gap-4">
              <div class="form-check">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_head_office"
                       value="1">
                <label class="form-check-label small">Head Office</label>
              </div>

              <div class="form-check">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_active"
                       value="1">
                <label class="form-check-label small">Active</label>
              </div>
            </div>

          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button type="button"
                  class="btn btn-light-primary btn-sm"
                  data-bs-dismiss="modal">
            Cancel
          </button>

          <button type="submit"
                  class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-1"></i> Update Office
          </button>
        </div>

      </form>

    </div>
  </div>
</div>
