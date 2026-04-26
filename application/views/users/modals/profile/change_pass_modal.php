<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="changePassModal" tabindex="-1" aria-labelledby="changePassModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <form class="app-form" method="post" action="<?= site_url('profile_editor/change_password/' . (int)($user_id ?? ($user['id'] ?? 0))) ?>" autocomplete="off">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="changePassModalLabel">
            <i class="ti ti-lock me-2"></i>Change Password
          </h5>
          <button type="button" class="btn btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">

          <!-- Current password (non-retrievable placeholder) -->
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <div class="input-group">
              <input type="password" class="form-control" value="********" disabled aria-describedby="currPassHelp">
              <span class="input-group-text" title="Passwords are hashed and not retrievable">
                <i class="ti ti-shield-lock text-muted"></i>
              </span>
            </div>
            <div id="currPassHelp" class="form-text">
              For security, the current password is not stored in plain text and cannot be viewed.
            </div>
          </div>

          <!-- New password with show/hide -->
          <div class="mb-3">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="new_password" id="new_password" minlength="8" required>
              <button class="btn btn-outline-secondary toggle-visibility" type="button" data-target="#new_password" title="Show/Hide">
                <i class="ti ti-eye"></i>
              </button>
              <button class="btn btn-outline-secondary" type="button" id="btn-generate" title="Generate strong password">
                <i class="ti ti-refresh"></i>
              </button>
              <button class="btn btn-outline-secondary" type="button" id="btn-copy" title="Copy password">
                <i class="ti ti-copy"></i>
              </button>
            </div>
            <div class="form-text">Minimum 8 characters. Use letters, numbers, and symbols.</div>
          </div>

          <!-- Confirm password with show/hide -->
          <div class="mb-1">
            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
              <button class="btn btn-outline-secondary toggle-visibility" type="button" data-target="#confirm_password" title="Show/Hide">
                <i class="ti ti-eye"></i>
              </button>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  // Toggle visibility for password fields
  document.querySelectorAll('#changePassModal .toggle-visibility').forEach(function(btn){
    btn.addEventListener('click', function(){
      const targetSel = btn.getAttribute('data-target');
      const input = document.querySelector(targetSel);
      if (!input) return;
      input.type = (input.type === 'password') ? 'text' : 'password';
      const icon = btn.querySelector('i');
      if (icon) icon.className = (input.type === 'password') ? 'ti ti-eye' : 'ti ti-eye-off';
    });
  });

  // Strong password generator
  function generatePassword(len) {
    // Includes upper, lower, digits, and symbols; avoids lookalikes
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower = 'abcdefghijkmnopqrstuvwxyz';
    const digits = '23456789';
    const symbols = '!@#$%^&*()-_=+[]{}';
    const all = upper + lower + digits + symbols;

    function pick(str){ return str[Math.floor(Math.random()*str.length)]; }

    let pwd = pick(upper) + pick(lower) + pick(digits) + pick(symbols);
    for (let i = pwd.length; i < len; i++) {
      pwd += pick(all);
    }
    // simple shuffle
    return pwd.split('').sort(() => 0.5 - Math.random()).join('');
  }

  const genBtn = document.getElementById('btn-generate');
  const copyBtn = document.getElementById('btn-copy');
  const newField = document.getElementById('new_password');
  const confField = document.getElementById('confirm_password');

  if (genBtn) {
    genBtn.addEventListener('click', function(){
      const pwd = generatePassword(14);
      newField.value = pwd;
      confField.value = pwd;
      // reveal for quick confirmation
      newField.type = 'text';
      confField.type = 'text';
      // flip icons if present
      document.querySelectorAll('.toggle-visibility').forEach((b)=>{
        const targetSel = b.getAttribute('data-target');
        const input = document.querySelector(targetSel);
        const icon = b.querySelector('i');
        if (input && icon && input.type === 'text') icon.className = 'ti ti-eye-off';
      });
    });
  }

  if (copyBtn) {
    copyBtn.addEventListener('click', async function(){
      const val = newField.value || '';
      if (!val) return;
      try {
        await navigator.clipboard.writeText(val);
        // Optional: toast if you have toastr; otherwise silent success
        if (window.toastr) toastr.success('Password copied to clipboard.');
      } catch (e) {
        if (window.toastr) toastr.error('Unable to copy to clipboard.');
      }
    });
  }
})();
</script>
