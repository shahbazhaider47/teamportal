<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$groupId = (int)($group['id'] ?? 0);
$status  = (string)($group['status'] ?? 'inactive');
?>

<div class="modal-content app-form">

  <div class="modal-header bg-primary">
    <h5 class="modal-title text-white">
      <i class="ti ti-users text-primary me-1"></i> Edit Client Group
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>

  <?= form_open(site_url('crm/group_update/' . $groupId), ['id' => 'crm-group-edit-form']); ?>

  <div class="modal-body">

    <!-- Group Basics -->
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="group_name" class="form-label">Group Name <span class="text-danger">*</span></label>
        <input type="text" name="group_name" id="group_name" class="form-control" required
               value="<?= html_escape($group['group_name'] ?? ''); ?>"
               placeholder="e.g., Alpha Partner Network">
      </div>

      <div class="col-md-6 mb-3">
        <label for="company_name" class="form-label">Company Name</label>
        <input type="text" name="company_name" id="company_name" class="form-control"
               value="<?= html_escape($group['company_name'] ?? ''); ?>"
               placeholder="Legal / registered company name">
      </div>
    </div>

    <!-- Contact -->
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="contact_person" class="form-label">Contact Person</label>
        <input type="text" name="contact_person" id="contact_person" class="form-control"
               value="<?= html_escape($group['contact_person'] ?? ''); ?>"
               placeholder="Primary point of contact">
      </div>

      <div class="col-md-6 mb-3">
        <label for="contract_date" class="form-label">Contract Date</label>
        <input type="date" name="contract_date" id="contract_date" class="form-control basic-date"
               value="<?= html_escape($group['contract_date'] ?? ''); ?>">
      </div>
    </div>

    <!-- Email / Phone -->
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="contact_email" class="form-label">Contact Email</label>
        <input type="email" name="contact_email" id="contact_email" class="form-control"
               value="<?= html_escape($group['contact_email'] ?? ''); ?>"
               placeholder="name@company.com">
      </div>

      <div class="col-md-6 mb-3">
        <label for="contact_phone" class="form-label">Contact Phone</label>
        <input type="text" name="contact_phone" id="contact_phone" class="form-control"
               value="<?= html_escape($group['contact_phone'] ?? ''); ?>"
               placeholder="Phone number with country code">
      </div>
    </div>

    <!-- Website / Fax -->
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="website" class="form-label">Website</label>
        <input type="text" name="website" id="website" class="form-control"
               value="<?= html_escape($group['website'] ?? ''); ?>"
               placeholder="https://example.com">
      </div>

      <div class="col-md-6 mb-3">
        <label for="fax_number" class="form-label">Fax Number</label>
        <input type="text" name="fax_number" id="fax_number" class="form-control"
               value="<?= html_escape($group['fax_number'] ?? ''); ?>"
               placeholder="Fax (if applicable)">
      </div>
    </div>

    <!-- Status -->
    <div class="mb-0">
      <label for="status" class="form-label">Group Status</label>
      <select name="status" id="status" class="form-select">
        <option value="active"   <?= ($status === 'active') ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?= ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
      </select>
      <div class="form-text">
        Inactive groups remain in the system but won’t be selectable for new clients.
      </div>
    </div>

  </div>

  <div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
      Close
    </button>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="ti ti-device-floppy"></i> Update Group
    </button>
  </div>

  <?= form_close(); ?>

</div>