<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
/** @var array $vaults */
$vaults = is_array($vaults ?? null) ? $vaults : [];
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex align-items-center small gap-1">
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canCreate    = staff_can('create', 'vault');
          $canExport    = staff_can('export', 'general');
          $canPrint     = staff_can('print', 'general');
          $canView      = staff_can('view_own', 'vault') || staff_can('view_global', 'vault');
          $canEdit      = staff_can('edit', 'vault');
          $canDelete    = staff_can('delete', 'vault');
          
        ?>
                
        <!-- Add Vault -->
        <button type="button"
                id="btn-add-vault"
                class="btn <?= $canCreate ? 'btn-primary' : 'btn-light-secondary' ?> btn-header"
                <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#addVaultModal"' : 'disabled' ?>
                title="Add New Vault Entry">
          <i class="fas fa-key me-1"></i> Add New Vault
        </button>
        
        <div class="btn-divider"></div>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'vaultsTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
    
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>
    
      <div class="card">
      <div class="card-body">
        <?php if (empty($vaults)): ?>
          <div class="p-4 text-center text-muted">
            <i class="ti ti-lock mb-2" style="font-size: 2rem;"></i>
            <p class="mb-0">No logins vault added yet. Use "Add New Vault" to create your first entry.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="vaultsTable">
              <thead class="bg-light-primary">
                <tr>
                  <th style="width: 20%;">Login Title</th>
                  <th style="width: 10%;">Login Type</th>
                  <th style="width: 10%;">Username</th>
                  <th style="width: 10%;">Email Address</th>
                  <th style="width: 10%;">TFA</th>
                  <th style="width: 10%;">Shares</th>
                  <th style="width: 10%;">Permissions</th>
                  <th style="width: 20%;" class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($vaults as $row): ?>
                <?php
                  $title       = $row['title'] ?? '';
                  $type        = $row['type'] ?? '';
                  $loginUrl    = $row['login_url'] ?? '';
                  $username    = $row['username'] ?? '';
                  $loginEmail  = $row['login_email'] ?? '';
                  $loginPhone  = $row['login_phone'] ?? '';
                  $isTfa       = !empty($row['is_tfa']);
                  $permissions = $row['permissions'] ?? 'read';
                  $shareCount  = (int)($row['share_count'] ?? 0);

                  $loginId = $username ?: ($loginEmail ?: $loginPhone);
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= html_escape($title); ?></div>
                    <?php if (!empty($row['description'])): ?>
                      <div class="text-muted small text-truncate" style="max-width: 260px;">
                        <?= html_escape($row['description']); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <span class="badge bg-light-primary">
                      <?= html_escape(ucwords(str_replace('_', ' ', $type))); ?>
                    </span>
                  </td>
                  
                  <td>
                    <?php if (!empty($row['username'])): ?>
                      <div class="text-muted text-truncate" style="max-width: 100px;">
                        <?= html_escape($row['username']); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <?php if (!empty($row['login_email'])): ?>
                      <div class="text-muted text-truncate" style="max-width: 150px;">
                        <?= html_escape($row['login_email']); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <?php if ($isTfa): ?>
                      <span class="badge bg-light-success">
                        <i class="ti ti-shield-check"></i> Enabled
                      </span>
                    <?php else: ?>
                      <span class="badge bg-light-danger">
                        <i class="ti ti-shield-off"></i> Disabled
                      </span>
                    <?php endif; ?>
                  </td>

                    <td>
                      <?php if ($shareCount > 0): ?>
                        <span class="badge bg-info-subtle text-info">
                          <i class="ti ti-share me-1"></i> Shared
                        </span>
                      <?php else: ?>
                        <span class="badge bg-light-danger">
                          <i class="ti ti-lock-open-off me-1"></i> Private
                        </span>
                      <?php endif; ?>
                    </td>

                  <td>
                    <?php
                      $permLabel = ucfirst($permissions);
                      $permClass = 'bg-secondary-subtle text-muted';
                      if ($permissions === 'read')  $permClass = 'bg-primary-subtle text-primary';
                      if ($permissions === 'write') $permClass = 'bg-warning-subtle text-warning';
                      if ($permissions === 'admin') $permClass = 'bg-danger-subtle text-danger';
                    ?>
                    <span class="badge <?= $permClass; ?>">
                      <?= html_escape($permLabel); ?>
                    </span>
                  </td>

                <td class="text-end">
                    <div class="btn-group">
                        <?php if ($canView): ?>
                            <button type="button" class="btn btn-light-primary btn-ssm btn-view-vault" data-bs-toggle="modal" data-bs-target="#viewVaultModal" data-vault='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>' title="View"><i class="ti ti-eye"></i></button>
                        <?php endif; ?>
                        
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-light-primary btn-ssm btn-edit-vault" data-bs-toggle="modal" data-bs-target="#editVaultModal" data-vault='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>' title="Edit"><i class="ti ti-edit"></i></button>
                        <?php endif; ?>
                        
                        <?php if (!empty($loginUrl)): ?>
                            <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-light-primary btn-ssm" title="Open Login Page"><i class="ti ti-external-link"></i></a>
                            <button type="button" class="btn btn-light-primary btn-ssm btn-copy-link" data-link="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>" title="Copy Login URL"><i class="ti ti-copy"></i></button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-light-primary btn-ssm btn-share-vault" data-bs-toggle="modal" 
                            data-bs-target="#shareVaultModal" data-id="<?= (int)$row['id']; ?>" title="Share">
                            <i class="ti ti-share"></i>
                        </button>
        
                        <!-- Delete Button -->
                        <?php if (staff_can('delete', 'vault')): ?>
                            <?= delete_link([
                            'url' => 'login_vault/delete/' . (int)$row['id'],
                            'label' => '',
                            'class' => 'btn btn-light-danger btn-ssm btn-delete-vault',
                            'message' => '',                                             
                            ]) ?>
                        <?php endif; ?>
            
                    </div>
                </td>
        
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php
$CI =& get_instance();
$CI->load->view('login_vault/modals/add_vault_modal');
$CI->load->view('login_vault/modals/view_vault_modal');
$CI->load->view('login_vault/modals/edit_vault_modal');
$CI->load->view('login_vault/modals/share_vault_modal');
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
(function ($, window, document) {
  'use strict';

  /* ==============================================================
   * GUARD
   * ============================================================ */
  if (typeof $ === 'undefined') {
    console.error('jQuery is required for Login Vault page');
    return;
  }

  /* ==============================================================
   * HELPERS
   * ============================================================ */
  function safeJsonParse(str) {
    try {
      return JSON.parse(str || '{}');
    } catch (e) {
      console.error('Invalid JSON payload', e);
      return {};
    }
  }

function formatLabel(value) {
  if (!value || typeof value !== 'string') return '';
  return value
    .replace(/_/g, ' ')
    .replace(/\b\w/g, function (c) {
      return c.toUpperCase();
    });
}

  /* ==============================================================
   * DOCUMENT READY (jQuery)
   * ============================================================ */
  $(function () {

    /* --------------------------------------------------------------
     * VIEW VAULT
     * ------------------------------------------------------------ */
    $(document).on('click', '.btn-view-vault', function () {
      let data = {};
      try {
        data = JSON.parse(this.dataset.vault || '{}');
      } catch (e) {
        console.error('Invalid vault JSON', e);
        return;
      }
    
      // Store vault ID for password reveal
      $('#view-vault-id').val(data.id || '');
    
      // Populate fields
        $('#viewVaultModal [data-field]').each(function () {
          const key = $(this).data('field');
          let value = data[key] ?? '—';
        
          if (key === 'type') {
            value = formatLabel(value);
          }
        
          $(this).text(value);
        });
    
      $('#viewVaultModal [data-field="is_tfa"]').text(
        String(data.is_tfa) === '1' ? 'Enabled' : 'Disabled'
      );
    });

    /* --------------------------------------------------------------
     * EDIT VAULT (EXPLICIT + SAFE)
     * ------------------------------------------------------------ */
    $(document).on('click', '.btn-edit-vault', function () {
      const data = safeJsonParse(this.dataset.vault);

      if (!data.id) {
        console.error('Missing vault ID for edit');
        return;
      }

      // Form action
      $('#editVaultForm').attr(
        'action',
        '<?= site_url('login_vault/update/') ?>' + data.id
      );

      // Explicit field mapping
      $('#edit-vault-id').val(data.id);
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

      // Never preload password
      $('#edit-vault-password').val('');
    });

    /* --------------------------------------------------------------
     * EDIT PASSWORD TOGGLE
     * ------------------------------------------------------------ */
    $(document).on('click', '#edit-btn-toggle-password', function () {
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

    /* --------------------------------------------------------------
     * DELETE VAULT
     * ------------------------------------------------------------ */
    $(document).on('click', '.btn-delete-vault', function () {
      const id = $(this).data('id');
      if (!id) return;

      if (confirm('Are you sure you want to delete this vault entry?')) {
        window.location.href =
          '<?= site_url('login_vault/delete/') ?>' + id;
      }
    });

    /* --------------------------------------------------------------
     * COPY LOGIN URL
     * ------------------------------------------------------------ */
    $(document).on('click', '.btn-copy-link', function () {
      const btn  = this;
      const link = btn.dataset.link;

      if (!link || !navigator.clipboard) return;

      navigator.clipboard.writeText(link).then(function () {
        btn.innerHTML = '<i class="ti ti-check"></i>';
        btn.classList.remove('btn-light-primary');
        btn.classList.add('btn-success');

        setTimeout(function () {
          btn.innerHTML = '<i class="ti ti-copy"></i>';
          btn.classList.remove('btn-success');
          btn.classList.add('btn-light-primary');
        }, 1500);
      });
    });

    /* --------------------------------------------------------------
     * SHARE VAULT – SET VAULT ID
     * ------------------------------------------------------------ */
    $('#shareVaultModal').on('show.bs.modal', function (e) {
      const button  = e.relatedTarget;
      const vaultId = button ? $(button).data('id') : null;
      $('#share-vault-id').val(vaultId || '');
    });

    /* --------------------------------------------------------------
     * SHARE VAULT – TARGET HANDLING (SINGLE IMPLEMENTATION)
     * ------------------------------------------------------------ */
    const $shareModal  = $('#shareVaultModal');
    const $scopeSelect = $('#share_type');
    const $targets     = $('#share_ids');
    const $wrapper     = $('#share-target-wrapper');

    function resetShareTargets() {
      if ($targets.hasClass('select2-hidden-accessible')) {
        $targets.select2('destroy');
      }
      $targets.empty();
      $wrapper.addClass('d-none');
    }

    $shareModal.on('shown.bs.modal', function () {
      $scopeSelect.val('All');
      resetShareTargets();
    });

    $(document).on('change', '#share_type', function () {
      const type = this.value;
      resetShareTargets();

      if (!type || type === 'All') return;

      $wrapper.removeClass('d-none');

      $.ajax({
        url: '<?= site_url('login_vault/get_share_scope_items') ?>',
        method: 'GET',
        data: { type: type },
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },

        success: function (items) {
          if (!Array.isArray(items)) return;

          items.forEach(function (item) {
            if (item && item.id !== undefined) {
              $targets.append(
                $('<option>', { value: item.id, text: item.name })
              );
            }
          });

          if ($.fn.select2) {
            $targets.select2({
              width: '100%',
              placeholder: 'Select ' + type,
              allowClear: true,
              dropdownParent: $shareModal
            });
          }
        },

        error: function (xhr) {
          console.error('Failed loading share scope:', xhr.responseText);
        }
      });
    });

  });

  /* ==============================================================
   * VANILLA JS (NO JQUERY DEPENDENCY)
   * ============================================================ */
  document.addEventListener('DOMContentLoaded', function () {

    /* --------------------------------------------------------------
     * ADD MODAL PASSWORD TOGGLE
     * ------------------------------------------------------------ */
    const pwdInput  = document.getElementById('vault-password');
    const toggleBtn = document.getElementById('btn-toggle-password');
    const icon      = document.getElementById('vault-password-icon');

    if (pwdInput && toggleBtn && icon) {
      toggleBtn.addEventListener('click', function () {
        const isPassword = pwdInput.type === 'password';
        pwdInput.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('ti-eye-off', !isPassword);
        icon.classList.toggle('ti-eye', isPassword);
      });
    }

    /* --------------------------------------------------------------
     * RESET ADD MODAL ON CLOSE
     * ------------------------------------------------------------ */
    const addModal = document.getElementById('addVaultModal');
    if (addModal) {
      addModal.addEventListener('hidden.bs.modal', function () {
        const form = document.getElementById('vaultForm');
        if (form) form.reset();

        if (pwdInput) pwdInput.type = 'password';
        if (icon) {
          icon.classList.remove('ti-eye');
          icon.classList.add('ti-eye-off');
        }
      });
    }

/* --------------------------------------------------------------
 * VIEW PASSWORD (ON-DEMAND, CONTROLLER ONLY)
 * ------------------------------------------------------------ */
(function () {
  let passwordVisible = false;

  const passwordEl = document.getElementById('vault-password-mask');
  const viewBtn = document.getElementById('btn-toggle-view-password');
  const copyBtn = document.getElementById('btn-copy-password');
  const idInput = document.getElementById('view-vault-id');

  if (!passwordEl || !viewBtn || !idInput) return;

  // Original masked value (stores the dots)
  const originalMask = passwordEl.textContent;

  function resetPassword() {
    passwordEl.textContent = originalMask;
    passwordEl.classList.remove('text-danger');
    passwordVisible = false;
    
    // Update button icon
    const icon = viewBtn.querySelector('i');
    if (icon) {
      icon.className = 'ti ti-eye';
    }
  }

  viewBtn.addEventListener('click', function () {
    const vaultId = idInput.value;

    if (!vaultId) {
      alert('Invalid vault reference.');
      return;
    }

    // Toggle visibility
    if (passwordVisible) {
      resetPassword();
      return;
    }

    // Show loading state
    passwordEl.textContent = 'Loading...';
    passwordEl.classList.add('text-muted');

    fetch(`<?= site_url('login_vault/reveal_password/') ?>${vaultId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(res => {
        if (!res.ok) {
          if (res.status === 403) {
            throw new Error('Access denied');
          } else if (res.status === 404) {
            throw new Error('Password not found');
          } else {
            throw new Error('HTTP ' + res.status);
          }
        }
        return res.json();
      })
      .then(res => {
        if (!res || !res.password) {
          alert('Unable to reveal password.');
          resetPassword();
          return;
        }

        // Show the password
        passwordEl.textContent = res.password;
        passwordEl.classList.remove('text-muted');
        passwordEl.classList.add('text-danger', 'fw-bold'); // Make it stand out
        
        // Update button icon to show "hide" state
        const icon = viewBtn.querySelector('i');
        if (icon) {
          icon.className = 'ti ti-eye-off';
        }
        
        passwordVisible = true;
      })
      .catch((error) => {
        alert(error.message || 'Failed to load password.');
        resetPassword();
      });
  });

  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      // Only copy if password is visible
      if (!passwordVisible) {
        alert('Please reveal the password first.');
        return;
      }

      const pwd = passwordEl.textContent;
      if (!pwd || pwd === originalMask || !navigator.clipboard) return;

      navigator.clipboard.writeText(pwd).then(() => {
        const icon = copyBtn.querySelector('i');
        if (icon) {
          const originalClass = icon.className;
          icon.className = 'ti ti-check';
          
          setTimeout(() => {
            icon.className = originalClass;
          }, 1200);
        }
      }).catch(err => {
        console.error('Failed to copy: ', err);
        alert('Failed to copy password.');
      });
    });
  }

  // Reset when modal closes
  const viewModal = document.getElementById('viewVaultModal');
  if (viewModal) {
    viewModal.addEventListener('hidden.bs.modal', resetPassword);
  }
})();


    /* --------------------------------------------------------------
     * Vault Data Copy Code
     * ------------------------------------------------------------ */
     
    $(document).on('click', '.btn-copy-field', function () {
      const field = $(this).data('copy-field');
      const value = $('[data-field="' + field + '"]').text().trim();
    
      if (!value || value === '—' || !navigator.clipboard) return;
    
      const btn = this;
    
      navigator.clipboard.writeText(value).then(() => {
        btn.innerHTML = '<i class="ti ti-check"></i>';
        setTimeout(() => {
          btn.innerHTML = '<i class="ti ti-copy"></i>';
        }, 1200);
      });
    });


    /* --------------------------------------------------------------
     * NUMERIC-ONLY PHONE INPUT
     * ------------------------------------------------------------ */
    const phoneInput = document.getElementById('vault-login-phone');
    if (phoneInput) {
      phoneInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
    }

  });

})(window.jQuery, window, document);
</script>
