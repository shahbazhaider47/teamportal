<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="editVaultModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <?= form_open('', ['id' => 'editVaultForm', 'autocomplete' => 'off']); ?>
      <input type="hidden" name="id" id="edit-vault-id">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-edit me-1"></i> Edit Login Vault Entry
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body app-form">
        <div class="row g-3">

          <!-- Login Title -->
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              Login Title <span class="text-danger">*</span>
            </label>
            <input type="text"
                   class="form-control"
                   id="edit-vault-title"
                   name="title"
                   maxlength="191"
                   required>
          </div>

<!-- Login Type -->
<div class="col-md-3">
    <label for="edit-vault-type" class="form-label fw-semibold">Login Type</label>

    <?php
        $vaultTypeOptions = vault_types_dropdown(true, '-- Select Login Type --');
    ?>

    <select class="form-select js-searchable-select" id="edit-vault-type" name="type">
        <?php foreach ($vaultTypeOptions as $value => $label): ?>
            <option value="<?= e($value) ?>">
                <?= e($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if (empty(vault_types())): ?>
        <div class="form-text text-muted">No vault types configured.</div>
    <?php endif; ?>
</div>

          <!-- Default Permission -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">
              Default Permission
              <i class="ti ti-question-circle"
                 title="Controls how this entry behaves when shared."></i>
            </label>
            <select class="form-select"
                    id="edit-vault-permissions"
                    name="permissions">
              <option value="private">Private</option>
              <option value="read">Shared - Read</option>
              <option value="write">Shared - Read &amp; Update</option>
            </select>
          </div>

          <!-- Login URL -->
          <div class="col-md-8">
            <label class="form-label fw-semibold">
              Login URL
              <i class="ti ti-question-circle"
                 title="Use the primary sign-in URL for this credential set."></i>
            </label>
            <input type="url"
                   class="form-control"
                   id="edit-vault-login-url"
                   name="login_url"
                   maxlength="255">
          </div>

          <!-- Username -->
          <div class="col-md-4">
            <label class="form-label fw-semibold">Username</label>
            <input type="text"
                   class="form-control"
                   id="edit-vault-username"
                   name="username"
                   maxlength="191">
          </div>

          <!-- Login Email -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Login Email</label>
            <input type="email"
                   class="form-control"
                   id="edit-vault-login-email"
                   name="login_email"
                   maxlength="191">
          </div>

          <!-- Login Phone (numeric only) -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Login Phone</label>
            <input type="text"
                   class="form-control"
                   id="edit-vault-login-phone"
                   name="login_phone"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   maxlength="15"
                   placeholder="Enter numbers only">
          </div>

          <!-- PIN -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">PIN</label>
            <input type="text"
                   class="form-control"
                   id="edit-vault-login-pin"
                   name="login_pin"
                   maxlength="20">
          </div>

          <!-- Password (optional) -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">
              Password
              <i class="ti ti-question-circle"
                 title="Leave blank to keep current password."></i>
            </label>
            <div class="input-group">
              <input type="password"
                     class="form-control"
                     id="edit-vault-password"
                     name="password_plain"
                     autocomplete="new-password">
              <button class="btn btn-outline-secondary btn-ssm"
                      type="button"
                      id="edit-btn-toggle-password">
                <i class="ti ti-eye-off"
                   id="edit-vault-password-icon"></i>
              </button>
            </div>
          </div>

          <!-- 2FA -->
          <div class="col-md-5">
            <label class="form-label fw-semibold d-block">2FA Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input"
                     type="checkbox"
                     id="edit-vault-is-tfa"
                     name="is_tfa"
                     value="1">
              <label class="form-check-label">
                2FA enabled on this account
              </label>
            </div>
          </div>

          <!-- TFA Secret -->
          <div class="col-md-7">
            <label class="form-label fw-semibold">
              TFA Secret / Backup Codes
            </label>
            <textarea class="form-control"
                      id="edit-vault-tfa-secret"
                      name="tfa_secret"
                      rows="2"></textarea>
          </div>

          <!-- Description -->
          <div class="col-md-12">
            <label class="form-label fw-semibold">Description / Notes</label>
            <textarea class="form-control"
                      id="edit-vault-description"
                      name="description"
                      rows="3"></textarea>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button"
                class="btn btn-light-primary btn-sm"
                data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="submit"
                class="btn btn-primary btn-sm">
          <i class="ti ti-device-floppy me-1"></i> Update Vault Entry
        </button>
      </div>

      <?= form_close(); ?>
    </div>
  </div>
</div>


<script>

/* --------------------------------------------------------------
 * EDIT VAULT (FIXED)
 * ------------------------------------------------------------ */
$(document).on('click', '.btn-edit-vault', function () {
  let data = {};
  try {
    data = JSON.parse(this.dataset.vault || '{}');
  } catch (e) {
    console.error('Invalid vault JSON', e);
    return;
  }

  $('#editVaultForm').attr('action', '<?= site_url('login_vault/update/') ?>' + (data.id || ''));

  $('#edit-vault-id').val(data.id || '');
  $('#edit-vault-title').val(data.title || '');
  $('#edit-vault-type').val(data.type || '').trigger('change');
  $('#edit-vault-permissions').val(data.permissions || 'read');

  $('#edit-vault-login-url').val(data.login_url || '');
  $('#edit-vault-username').val(data.username || '');
  $('#edit-vault-login-email').val(data.login_email || '');
  $('#edit-vault-login-phone').val(data.login_phone || '');
  $('#edit-vault-login-pin').val(data.login_pin || '');

  $('#edit-vault-tfa-secret').val(data.tfa_secret || '');
  $('#edit-vault-description').val(data.description || '');

  $('#edit-vault-is-tfa').prop('checked', String(data.is_tfa) === '1');
  $('#edit-vault-password').val('');
});

$('#edit-btn-toggle-password').on('click', function () {
  const $input = $('#edit-vault-password');
  const $icon  = $('#edit-vault-password-icon');

  if ($input.attr('type') === 'password') {
    $input.attr('type', 'text');
    $icon.removeClass('ti-eye-off').addClass('ti-eye');
  } else {
    $input.attr('type', 'password');
    $icon.removeClass('ti-eye').addClass('ti-eye-off');
  }
});
    
</script>