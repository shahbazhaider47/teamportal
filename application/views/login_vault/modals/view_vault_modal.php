<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!--
  View Vault Modal
  ─────────────────────────────────────────────────────────────
  All JS (field population, password reveal/copy) lives in
  index.php. This file is pure HTML.
-->
<div class="modal fade" id="viewVaultModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title text-white mb-0">
          <i class="ti ti-lock me-1"></i>
          Vault <i class="ti ti-chevron-right mx-1 opacity-75"></i>
          <span data-field="title">Vault Entry</span>
        </h6>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
      </div>

      <!-- Hidden vault ID for AJAX password reveal -->
      <input type="hidden" id="view-vault-id" value="">

      <div class="modal-body px-3 py-3">

        <!-- Type + Permission badges -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge bg-light-primary text-capitalize" data-field="type">—</span>
          <span class="badge bg-secondary-subtle text-muted text-capitalize"
                data-field="permissions">—</span>
        </div>

        <hr class="my-2">

        <!-- Username -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-alphabet-latin text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Username:</span>
            <span class="vault-info-value flex-grow-1" data-field="username">—</span>
            <button type="button"
                    class="btn btn-light-primary btn-ssm btn-copy-field ms-auto"
                    data-copy-field="username"
                    title="Copy Username">
              <i class="ti ti-copy"></i>
            </button>
          </div>
        </div>

        <!-- Email -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-mail text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Email:</span>
            <span class="vault-info-value flex-grow-1 text-break" data-field="login_email">—</span>
            <button type="button"
                    class="btn btn-light-primary btn-ssm btn-copy-field ms-auto"
                    data-copy-field="login_email"
                    title="Copy Email">
              <i class="ti ti-copy"></i>
            </button>
          </div>
        </div>

        <!-- Phone -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-phone text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Phone:</span>
            <span class="vault-info-value flex-grow-1" data-field="login_phone">—</span>
            <button type="button"
                    class="btn btn-light-primary btn-ssm btn-copy-field ms-auto"
                    data-copy-field="login_phone"
                    title="Copy Phone">
              <i class="ti ti-copy"></i>
            </button>
          </div>
        </div>

        <!-- URL -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-world text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">URL:</span>
            <span class="vault-info-value flex-grow-1 text-break" data-field="login_url">—</span>
            <button type="button"
                    class="btn btn-light-primary btn-ssm btn-copy-field ms-auto"
                    data-copy-field="login_url"
                    title="Copy URL">
              <i class="ti ti-copy"></i>
            </button>
          </div>
        </div>

        <!-- PIN -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-code-asterix text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">PIN:</span>
            <span class="vault-info-value flex-grow-1" data-field="login_pin">—</span>
            <button type="button"
                    class="btn btn-light-primary btn-ssm btn-copy-field ms-auto"
                    data-copy-field="login_pin"
                    title="Copy PIN">
              <i class="ti ti-copy"></i>
            </button>
          </div>
        </div>

        <!-- Password (masked, reveal on demand) -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-lock text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Password:</span>
            <code id="vault-password-mask" class="flex-grow-1 text-muted">••••••••••</code>
            <button type="button"
                    class="btn btn-light-primary btn-ssm"
                    id="btn-toggle-view-password"
                    title="Reveal / Hide Password">
              <i class="ti ti-eye"></i>
            </button>
            <button type="button"
                    class="btn btn-light-primary btn-ssm"
                    id="btn-copy-password"
                    title="Copy Password">
              <i class="ti ti-copy"></i>
            </button>
          </div>
        </div>

        <hr class="my-2">

        <!-- 2FA Status -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-2fa text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">TFA Status:</span>
            <span class="badge bg-light-primary vault-info-value" data-field="is_tfa">—</span>
          </div>
        </div>

        <!-- TFA Secret -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-list-details text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">TFA Secret / Backup:</span>
            <span class="small text-muted text-break" data-field="tfa_secret">—</span>
          </div>
        </div>

        <hr class="my-2">

        <!-- Owner -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-user text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Owner:</span>
            <span class="small text-muted" data-field="owner_name">—</span>
          </div>
        </div>

        <!-- Created At -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-calendar text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Created At:</span>
            <span class="small text-muted" data-field="created_at">—</span>
          </div>
        </div>

        <!-- Updated At -->
        <div class="vault-info-box mb-2">
          <div class="vault-info-body small d-flex align-items-center gap-2">
            <i class="ti ti-calendar-time text-primary flex-shrink-0"></i>
            <span class="text-muted me-1">Updated At:</span>
            <span class="small text-muted" data-field="updated_at">—</span>
          </div>
        </div>

        <hr class="my-2">

        <!-- Notes -->
        <div class="vault-info-box mb-1">
          <div class="vault-info-body small">
            <i class="ti ti-note text-primary me-1"></i>
            <span class="text-muted">Notes:</span>
          </div>
          <div class="small text-muted text-break mt-1 ps-3"
               data-field="description">—</div>
        </div>

      </div>

      <div class="modal-footer py-2">
        <button type="button"
                class="btn btn-light-primary btn-sm"
                data-bs-dismiss="modal">
          Close
        </button>
      </div>

    </div>
  </div>
</div>