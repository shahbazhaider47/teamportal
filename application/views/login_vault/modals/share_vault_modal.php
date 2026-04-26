<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!--
  Share Vault Modal
  ─────────────────────────────────────────────────────────────
  All JS (scope loading, Select2 init, vault ID injection) is
  handled centrally in index.php.  This file contains ONLY the
  modal HTML — no inline <script> block.
-->
<div class="modal fade" id="shareVaultModal" tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="ti ti-share me-1"></i> Share Vault Entry
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Form -->
      <form method="post"
            action="<?= site_url('login_vault/share') ?>"
            class="app-form"
            autocomplete="off">

        <!-- Vault ID — injected by index.php on show.bs.modal -->
        <input type="hidden" name="vault_id" id="share-vault-id" value="">

        <div class="modal-body">

          <!-- Share Type -->
          <div class="mb-3">
            <label for="share_type" class="form-label fw-semibold">
              Share With <span class="text-danger">*</span>
            </label>
            <select name="share_type" id="share_type" class="form-select" required>
              <option value="Departments">Departments</option>
              <option value="Teams">Teams</option>
              <option value="Positions">Positions</option>
              <option value="Staff">Staff</option>
            </select>
            <div class="form-text text-muted">
              Changing this selection updates the targets list below.
            </div>
          </div>

          <!-- Target IDs (hidden until a type is chosen / loaded) -->
          <div class="mb-3" id="share-target-wrapper">
            <label for="share_ids" class="form-label fw-semibold">
              Select Targets <span class="text-danger">*</span>
            </label>
            <select name="share_ids[]"
                    id="share_ids"
                    class="form-select"
                    multiple
                    style="width:100%;">
              <!-- Options loaded via AJAX when share_type changes -->
            </select>
            <div class="form-text text-muted">
              You may select multiple entries. Hold <kbd>Ctrl</kbd> / <kbd>Cmd</kbd> to multi-select.
            </div>
          </div>

          <!-- Permissions -->
          <div class="mb-1">
            <label class="form-label fw-semibold">Permissions</label>
            <div class="d-flex gap-4 mt-1">

              <div class="form-check">
                <input class="form-check-input" type="radio"
                       name="permissions" id="perm-view" value="view" checked>
                <label class="form-check-label" for="perm-view">
                  <i class="ti ti-eye me-1 text-primary"></i> Can View
                </label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="radio"
                       name="permissions" id="perm-edit" value="edit">
                <label class="form-check-label" for="perm-edit">
                  <i class="ti ti-edit me-1 text-warning"></i> Can Edit
                </label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="radio"
                       name="permissions" id="perm-delete" value="delete">
                <label class="form-check-label" for="perm-delete">
                  <i class="ti ti-trash me-1 text-danger"></i> Can Delete
                </label>
              </div>

            </div>
          </div>

          <div class="alert alert-info small mt-3 mb-0 py-2">
            <i class="ti ti-info-circle me-1"></i>
            Re-sharing to the same scope type revokes the previous share and applies the new one.
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light-primary btn-sm"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-check me-1"></i> Share Vault
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
(function () {

  const modalId = '#shareVaultModal';

  function log(...args) {
    console.log('[ShareVault]', ...args);
  }

  function initShareModal() {

    const modal      = document.querySelector(modalId);
    const typeSelect = modal.querySelector('#share_type');
    const wrapper    = modal.querySelector('#share-target-wrapper');
    const $targets   = $(modal).find('#share_ids');

    if (!typeSelect || !$targets.length) {
      console.warn('[ShareVault] Modal elements not found');
      return;
    }

    /**
     * Reset ONLY the target selector UI
     * ⚠️ Never touch hidden inputs (vault_id)
     */
    function resetTargets() {
      if ($targets.hasClass('select2-hidden-accessible')) {
        $targets.select2('destroy');
      }
      $targets.empty();
      wrapper.classList.add('d-none');
    }

    /**
     * Handle Share Type change
     */
    typeSelect.addEventListener('change', function () {

      const type = this.value;
      log('Share type changed:', type);

      resetTargets();

      wrapper.classList.remove('d-none');

      // Loading placeholder
      $targets.append(new Option('Loading...', '', true, true));

      const url =
        '<?= site_url('login_vault/get_share_scope_items') ?>?type=' +
        encodeURIComponent(type);

      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then(items => {

          $targets.empty();

          if (!Array.isArray(items) || items.length === 0) {
            $targets.append(
              new Option('No records found', '', false, false)
            );
            return;
          }

          items.forEach(item => {
            if (item.id && item.name) {
              $targets.append(
                new Option(item.name, item.id)
              );
            }
          });

          // Initialize Select2 safely inside modal
          if ($.fn.select2) {
            $targets.select2({
              width: '100%',
              placeholder: 'Select ' + type,
              allowClear: true,
              dropdownParent: $(modal)
            });
          }
        })
        .catch(err => {
          console.error('[ShareVault] Failed loading scope:', err);
          $targets.empty();
          $targets.append(
            new Option('Failed to load data', '', false, false)
          );
        });
    });
  }

  // Init after DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector(modalId);
    if (!modal) {
      console.error('[ShareVault] Modal not found');
      return;
    }
    initShareModal();
  });

})();
</script>