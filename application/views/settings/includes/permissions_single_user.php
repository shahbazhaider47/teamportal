<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php

// ---------- Modules map ----------
$coreModules = include APPPATH . 'config/core_permissions.php';

// Allow modules to extend via hook
$modulePermissions = hooks()->apply_filters('user_permissions', []);
$modules = array_merge($coreModules, $modulePermissions);

// ---------- Data contract ----------
$users            = $users            ?? [];
$selected_user_id = (int)($selected_user_id ?? 0);
$user_grants      = $user_grants      ?? [];
$user_denies      = $user_denies      ?? [];

// Fallback (safety)
if (empty($users)) {
    $CI = &get_instance();
    $users = $CI->db
        ->select('id, TRIM(COALESCE(fullname, CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,"")))) AS fullname, emp_id', false)
        ->from('users')
        ->order_by('fullname','asc')
        ->get()
        ->result_array();
}
?>

<style>
.up-wrap { display:block; }
.up-title { font-weight:600; font-size:18px; margin:0; }

.up-note {
  margin:12px 0 16px;
  padding:8px 12px;
  border:1px solid #e9ecef;
  background:#f8f9fa;
  border-radius:6px;
  font-size:13px;
}

.mod {
  border:1px solid #f0f0f0;
  border-radius:8px;
  margin-bottom:14px;
}
.mod-hd {
  padding:10px 14px;
  border-bottom:1px solid #f0f0f0;
  background:#fcfcfd;
}
.mod-title {
  margin:0;
  font-weight:700;
  font-size:14px;
}

.perm-grid {
  display:grid;
  grid-template-columns: 1fr 120px 120px;
  gap:8px 12px;
  padding:12px 16px;
  align-items:center;
}

.perm-head {
  font-size:12px;
  font-weight:600;
  color:#6c757d;
  padding-bottom:4px;
  border-bottom:1px solid #f1f3f5;
}

.perm-row { display:contents; }

.perm-name { font-size:12px; }
.perm-key {
  margin-left:8px;
  font-size:10px;
  color:#94a3b8;
}

.perm-toggle { text-align:center; }
.perm-toggle .form-check-input { cursor:pointer; }

@media (max-width:768px){
  .perm-grid { grid-template-columns:1fr 80px 80px; }
}
@media (max-width:520px){
  .perm-grid { grid-template-columns:1fr; }
  .perm-head { display:none; }
  .perm-toggle { text-align:left; }
  .perm-toggle::before {
    content: attr(data-label) " ";
    font-size:12px;
    color:#6c757d;
    margin-right:8px;
  }
}

.finance-group {
    font-size: 13px !important
}

</style>

<form method="post" action="<?= site_url('settings?group=permissions_single_user') ?>" class="app-form">
<div class="up-wrap">

<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="up-title">Single User Permissions</h5>
</div>

<div class="mb-3 app-form">
<div class="input-group">
    <span class="input-group-text finance-group">Select User to Manage Permissions <span class="text-danger px-1">*</span></span>  
    <select id="userSelect" class="form-select" name="user_id">
      <option value="">Select a user…</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= $u['id']===$selected_user_id?'selected':'' ?>>
          <?= e((!empty($u['emp_id']) ? $u['emp_id'].' — ' : '') . ($u['fullname'] ?: 'User #'.$u['id'])) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="button" id="btnReloadUser" class="btn btn-primary btn-header" <?= $selected_user_id ? '' : 'disabled' ?>>
      Load Permissions
    </button>
</div>
</div>

<?php foreach ($modules as $moduleKey => $actions): ?>
<section class="mod">
  <header class="mod-hd">
    <p class="mod-title"><?= e($actions['name'] ?? ucfirst($moduleKey)) ?></p>
  </header>

  <div class="perm-grid">
    <div class="perm-head">Permission</div>
    <div class="perm-head text-center">Allow</div>
    <div class="perm-head text-center">Deny</div>

    <?php
    $actionArr = $actions['actions'] ?? $actions;
    foreach ($actionArr as $actionKey => $meta):
      $label = is_array($meta) ? ($meta['label'] ?? ucfirst($actionKey)) : $meta;
      $perm  = $moduleKey . ':' . $actionKey;
      $gid   = 'g-' . md5($perm);
      $did   = 'd-' . md5($perm);
    ?>
    <div class="perm-row">
      <div class="perm-name">
        <?= e($label) ?>
        <span class="perm-key"><?= e($perm) ?></span>
      </div>

      <div class="perm-toggle" data-label="Allow">
        <input type="checkbox" class="form-check-input grant-cb"
               id="<?= $gid ?>" data-pair="#<?= $did ?>"
               name="settings[grants][]" value="<?= e($perm) ?>"
               <?= in_array($perm, $user_grants, true) ? 'checked' : '' ?>>
      </div>

      <div class="perm-toggle" data-label="Deny">
        <input type="checkbox" class="form-check-input deny-cb"
               id="<?= $did ?>" data-pair="#<?= $gid ?>"
               name="settings[denies][]" value="<?= e($perm) ?>"
               <?= in_array($perm, $user_denies, true) ? 'checked' : '' ?>>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const userSelect = document.getElementById('userSelect');
  const reloadBtn  = document.getElementById('btnReloadUser');

  userSelect?.addEventListener('change', () => {
    reloadBtn.disabled = !userSelect.value;
  });

  reloadBtn?.addEventListener('click', () => {
    if (!userSelect.value) return;
    const p = new URLSearchParams(window.location.search);
    p.set('group','permissions_single_user');
    p.set('uid', userSelect.value);
    window.location.search = p.toString();
  });

  document.querySelectorAll('.grant-cb').forEach(cb=>{
    cb.addEventListener('change',()=>{
      const pair=document.querySelector(cb.dataset.pair);
      if(cb.checked && pair) pair.checked=false;
    });
  });
  document.querySelectorAll('.deny-cb').forEach(cb=>{
    cb.addEventListener('change',()=>{
      const pair=document.querySelector(cb.dataset.pair);
      if(cb.checked && pair) pair.checked=false;
    });
  });

});
</script>
