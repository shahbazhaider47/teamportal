<?php
if (!function_exists('e')) {
  function e($str){ return html_escape($str ?? ''); }
}

$relationship_types = $relationship_types ?? [];
$blood_group_types  = $blood_group_types ?? [];

$currentRel = trim((string)($user['emergency_contact_relationship'] ?? ''));
$currentBgt = trim((string)($user['blood_group'] ?? ''));
?>

<div class="modal fade" id="editEmergencyModal" tabindex="-1" aria-labelledby="editEmergencyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="editEmergencyModalLabel">Edit Emergency Contact Information</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="<?= site_url('profile_editor/edit_emergency/' . $user['id']) ?>" method="POST" class="app-form">
        <div class="modal-body p-4">
          <div class="row">

            <div class="col-md-6">
              <div class="mb-3">
                <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                <select class="form-select" id="emergency_contact_relationship" name="emergency_contact_relationship">
                  <option value="">Select Relationship</option>

                  <?php foreach ($relationship_types as $opt): ?>
                    <?php $opt = trim((string)$opt); ?>
                    <option value="<?= e($opt) ?>" <?= ($currentRel === $opt) ? 'selected' : '' ?>>
                      <?= e($opt) ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <?php if (empty($relationship_types)): ?>
                  <div class="form-text text-muted">No Relationship Types configured in System Options.</div>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="blood_group" class="form-label">Blood Group</label>
                <select class="form-select" id="blood_group" name="blood_group">
                  <option value="">Select Blood Group</option>

                  <?php foreach ($blood_group_types as $bgt): ?>
                    <?php $bgt = trim((string)$bgt); ?>
                    <option value="<?= e($bgt) ?>" <?= ($currentBgt === $bgt) ? 'selected' : '' ?>>
                      <?= e($bgt) ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <?php if (empty($blood_group_types)): ?>
                  <div class="form-text text-muted">No Blood Group Types configured in System Options.</div>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"
                       value="<?= e($user['emergency_contact_name'] ?? '') ?>"
                       placeholder="Full name of emergency contact">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone"
                       value="<?= e($user['emergency_contact_phone'] ?? '') ?>"
                       placeholder="Phone number with country code">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="father_name" class="form-label">Father Name</label>
                <input type="text" class="form-control" id="father_name" name="father_name"
                       value="<?= e($user['father_name'] ?? '') ?>">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="mother_name" class="form-label">Mother Name</label>
                <input type="text" class="form-control" id="mother_name" name="mother_name"
                       value="<?= e($user['mother_name'] ?? '') ?>">
              </div>
            </div>

          </div>

          <div class="alert alert-info small mb-0">
            <i class="ti ti-info-circle me-1"></i>
            This information will be used in case of emergencies. Please ensure it's accurate and up-to-date.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>