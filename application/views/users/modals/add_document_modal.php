<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Document Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="documentForm" method="post" action="<?= site_url('users/save_document') ?>" enctype="multipart/form-data" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="documentModalLabel">Add New Document</h5>
          <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="doc_id">
          
        <div class="mb-3">
          <label class="form-label">Document For <span class="text-danger">*</span></label>
          <select class="form-select" name="doc_scope" id="doc_scope" required onchange="toggleEmployeeSelect(this.value)">
            <option value="employee" selected>Employee</option>
            <option value="company">Company</option>
          </select>
        </div>
        <div class="mb-3" id="employeeSelectBox">
          <label class="form-label">Employee <span class="text-danger">*</span></label>
          <select name="user_id" class="form-select" id="user_id">
            <option value="">Select Employee</option>
            <?php foreach($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= html_escape($u['firstname'].' '.$u['lastname']) ?></option>
            <?php endforeach ?>
          </select>
        </div>

          <div class="mb-3">
            <label class="form-label">Document Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" id="doc_title" placeholder="Signed Contract, Warning Letter, etc" required>
          </div>
            <div class="mb-3">
              <label class="form-label">Document Type <span class="text-danger">*</span></label>
              <select class="form-select" name="doc_type" id="doc_type" required>
                <option value="">Select Document Type</option>
                <option value="Appointment Letter">Appointment Letter</option>
                <option value="CNIC">CNIC</option>
                <option value="Experience Letter">Experience Letter</option>
                <option value="Degree">Degree</option>
                <option value="Offer Letter">Offer Letter</option>
                <option value="Resume">Resume</option>
                <option value="Reference Letter">Reference Letter</option>
                <option value="Medical Certificate">Medical Certificate</option>
                <option value="Payslip">Payslip</option>
                <option value="Contract">Contract</option>
                <option value="Warning Letter">Warning Letter</option>
                <option value="Relieving Letter">Relieving Letter</option>
                <option value="Company Policy">Company Policy</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea
                class="form-control"
                name="description"
                id="doc_description"
                rows="2"
                placeholder="Enter a brief description (max 250 characters)"
              ><?= html_escape($doc['description'] ?? '') ?></textarea>
            </div>

          <div class="mb-3">
            <label class="form-label">Expiry Date (if applicable)</label>
            <input type="date" class="form-control basic-date" name="expiry_date" id="doc_expiry_date" placeholder="YYYY-MM-DD">
          </div>
          <div class="mb-3">
            <label class="form-label">Document File <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <div class="form-text">Max file size: 5MB. Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Document</button>
        </div>
      </div>
    </form>
  </div>
</div>