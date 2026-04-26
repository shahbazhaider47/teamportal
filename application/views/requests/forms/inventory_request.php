<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
.request-group{
    font-size: 12px !important
}    
</style>
<!-- Inventory Request Form Fields -->
<div class="card-body app-form">
    <div class="app-divider-v primary mb-4">
        <span class="badge text-bg-primary py-2"><i class="ti ti-tools me-1"></i> New Inventory Request</span>
    </div>
  <!-- JSON: purchase_title -->
  <div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">
      Purchase Title <span class="text-danger">*</span>
    </label>
    <input type="text"
           name="payload[purchase_title]"
           class="form-control"
           maxlength="150"
           placeholder="e.g. New Laptop for Developer"
           required>
  </div>


    <!-- JSON: required_quantity -->
    <div class="col-md-3 mb-3">
      <label class="form-label">
        Required Quantity <span class="text-danger">*</span>
      </label>
      <input type="number"
             name="payload[required_quantity]"
             id="required_quantity"
             class="form-control"
             min="1"
             step="1"
             required>
    </div>

    <!-- JSON: cost_per_item -->
    <div class="col-md-3 mb-3">
        <label class="form-label">Cost Per Item <span class="text-danger">*</span></label>
        <div class="input-group">
        <span class="input-group-text request-group"><?= html_escape(get_base_currency_symbol()) ?></span>
      <input type="number"
             name="payload[cost_per_item]"
             id="cost_per_item"
             class="form-control"
             min="1"
             step="1"
             required>
    </div>
    </div>

  </div>
  
  <!-- JSON: description -->
  <div class="mb-3">
    <label class="form-label">
      Description <span class="text-danger">*</span>
    </label>
    <textarea name="payload[description]"
              class="form-control"
              rows="3"
              maxlength="1000"
              placeholder="Describe the item and business need"
              required></textarea>
  </div>
  
  <!-- HEADER FIELDS (requests table, NOT JSON) -->
  <div class="row">

    <!-- JSON: date_required -->
    <div class="col-md-4 mb-3">
      <label class="form-label">
        Date Required <span class="text-danger">*</span>
      </label>
      <input type="date"
             name="payload[date_required]"
             class="form-control basic-date"
             required>
    </div>
    
    <div class="col-md-4 mb-3">
      <label class="form-label">Priority</label>
      <select name="priority" class="form-select">
        <option value="normal">Normal</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <div class="col-md-4 mb-3">
      <label class="form-label">Department</label>
      <select name="department_id" class="form-select">
        <?= department_dropdown_options(); ?>
      </select>
    </div>

  </div>

  <!-- ATTACHMENTS -->
  <div class="mb-3">
    <label class="form-label">Attachments <span class="small text-muted">(Optional)</span></label>
    <input type="file"
           name="attachments[]"
           class="form-control"
           multiple>
    <small class="text-muted">
      PDF, images, or supporting documents.
    </small>
  </div>

</div>