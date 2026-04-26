<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="addVaultModal" tabindex="-1" aria-labelledby="addVaultModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <?= form_open('login_vault/store', ['id' => 'vaultForm', 'autocomplete' => 'off']); ?>
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="addVaultModalLabel">
          <i class="ti ti-lock me-1"></i> Add New Login Vault Entry
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body app-form">
        <div class="row g-3">

          <div class="col-md-6">
            <label for="vault-title" class="form-label fw-semibold">Login Title <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control"
                   id="vault-title"
                   name="title"
                   maxlength="191"
                   required
                   placeholder="e.g. Insurance portal, web portal, billing, claims">
          </div>

            <div class="col-md-3">
                <label for="vault-type" class="form-label fw-semibold">Login Type</label>
            
                <?php
                    $vaultTypeOptions = vault_types_dropdown(true, '-- Select Login Type --');
                    $selType = isset($vault) ? ($vault['type'] ?? '') : 'website';
                ?>
            
                <select class="form-select js-searchable-select" id="vault-type" name="type">
                    <?php foreach ($vaultTypeOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($selType === $value) ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            
                <?php if (empty(vault_types())): ?>
                    <div class="form-text text-muted">No vault types configured.</div>
                <?php endif; ?>
            </div>

          <div class="col-md-3">
            <label for="vault-permissions" class="form-label fw-semibold">Default Permission <i class="ti ti-question-circle" title="Controls how this entry behaves when shared."></i></label>
            <select class="form-select" id="vault-permissions" name="permissions">
              <option value="private">Private</option>
              <option value="read">Shared - Read Only</option>
              <option value="write">Shared - Read &amp; Update</option>
            </select>
          </div>
          
          <div class="col-md-8">
            <label for="vault-login-url" class="form-label fw-semibold">Login URL <span class="text-danger">*</span> <i class="ti ti-question-circle" title="Use the primary sign-in URL for this credential set."></i></label>
            <input type="url"
                   class="form-control"
                   id="vault-login-url"
                   name="login_url"
                   maxlength="255"
                   required
                   placeholder="https://portal.example.com/login">
          </div>

          <div class="col-md-4">
            <label for="vault-username" class="form-label fw-semibold">Username</label>
            <input type="text"
                   class="form-control"
                   id="vault-username"
                   name="username"
                   maxlength="191"
                   placeholder="Username / Account ID">
          </div>

          <div class="col-md-3">
            <label for="vault-login-email" class="form-label fw-semibold">Login Email</label>
            <input type="email"
                   class="form-control"
                   id="vault-login-email"
                   name="login_email"
                   maxlength="191"
                   placeholder="name@company.com">
          </div>

            <div class="col-md-3">
              <label for="vault-login-phone" class="form-label fw-semibold">Login Phone</label>
              <input type="text"
                     class="form-control"
                     id="vault-login-phone"
                     name="login_phone"
                     inputmode="numeric"
                     pattern="[0-9]*"
                     maxlength="15"
                     placeholder="Enter numbers only">
            </div>

          <div class="col-md-3">
            <label for="vault-login-pin" class="form-label fw-semibold">PIN</label>
            <input type="text"
                   class="form-control"
                   id="vault-login-pin"
                   name="login_pin"
                   maxlength="20"
                   placeholder="short PIN code">
          </div>

          <div class="col-md-3">
            <label for="vault-password" class="form-label fw-semibold">Password <span class="text-danger">*</span>
            <i class="ti ti-question-circle" title="Password will be encrypted before being stored. Avoid reusing personal passwords."></i>
            </label>
            <div class="input-group">
              <input type="password"
                     class="form-control"
                     id="vault-password"
                     name="password_plain"
                     required
                     autocomplete="new-password"
                     placeholder="Enter or paste password">
              <button class="btn btn-outline-secondary btn-ssm" type="button" id="btn-toggle-password">
                <i class="ti ti-eye-off" id="vault-password-icon"></i>
              </button>
            </div>
          </div>

          <div class="col-md-5">
            <label class="form-label fw-semibold d-block">2FA Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input"
                     type="checkbox"
                     id="vault-is-tfa"
                     name="is_tfa"
                     value="1">
              <label class="form-check-label" for="vault-is-tfa">
                2FA enabled on this account
              </label>
            </div>
          </div>

          <div class="col-md-7">
            <label for="vault-tfa-secret" class="form-label fw-semibold">TFA Secret / Backup Codes</label>
            <textarea class="form-control"
                      id="vault-tfa-secret"
                      name="tfa_secret"
                      rows="2"
                      placeholder="Store TOTP secret key or backup codes securely (if required)."></textarea>
          </div>

          <div class="col-md-12">
            <label for="vault-description" class="form-label fw-semibold">Description / Notes</label>
            <textarea class="form-control"
                      id="vault-description"
                      name="description"
                      rows="3"
                      placeholder="Usage notes, environment, scope of access, escalation contact, etc."></textarea>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="ti ti-device-floppy me-1"></i> Save Vault Entry
        </button>
      </div>

      <?= form_close(); ?>
    </div>
  </div>
</div>