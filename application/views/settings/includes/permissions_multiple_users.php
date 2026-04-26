<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
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

/* ==========================================================
 | MULTI USER SELECTION STYLES
 ========================================================== */
.multi-user-wrapper {
  position: relative;
  max-width: 600px;
}

.multi-user-select-wrapper {
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  padding: 0.375rem 0.75rem;
  min-height: 42px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  background: #fff;
  cursor: pointer;
}

.multi-user-select-wrapper:focus-within {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  outline: 0;
}

.multi-user-search {
  border: none;
  outline: none;
  flex: 1;
  min-width: 120px;
  font-size: 13px;
  padding: 4px 0;
  background: transparent;
  cursor: pointer;
}

.multi-user-placeholder {
  color: #6c757d;
  flex: 1;
  font-size: 13px;
  padding: 4px 0;
}

.multi-user-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  max-height: 250px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 2px;
  display: none;
}

.multi-user-dropdown.show {
  display: block;
}

.multi-user-option {
  padding: 8px 12px;
  cursor: pointer;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #f8f9fa;
}

.multi-user-option:hover {
  background: #f8f9fa;
}

.multi-user-option.selected {
  background: #e7f1ff;
  color: #0d6efd;
}

.multi-user-option-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
}

.no-users-message {
  padding: 12px;
  text-align: center;
  color: #6c757d;
  font-style: italic;
  font-size: 13px;
}

/* Selected users chips container */
.selected-users-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
  padding: 12px;
  border: 1px dashed #dee2e6;
  border-radius: 6px;
  background: #f8f9fa;
  min-height: 60px;
}

.selected-users-chips.empty {
  justify-content: center;
  align-items: center;
  color: #6c757d;
  font-style: italic;
}

.user-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 20px;
  padding: 6px 12px 6px 16px;
  font-size: 12px;
  line-height: 1;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.user-chip-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
}

.user-chip-remove {
  background: none;
  border: none;
  color: #6c757d;
  font-size: 18px;
  line-height: 1;
  padding: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s;
}

.user-chip-remove:hover {
  background: #dc3545;
  color: white;
}

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
<?php

$coreModules = include APPPATH . 'config/core_permissions.php';
$modulePermissions = hooks()->apply_filters('user_permissions', []);
$modules = array_merge($coreModules, $modulePermissions);

$users = $users ?? [];

if (empty($users)) {
    $CI = &get_instance();
    $users = $CI->db
        ->select('id, TRIM(COALESCE(fullname, CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,"")))) AS fullname, emp_id', false)
        ->from('users')
        ->order_by('fullname','asc')
        ->get()
        ->result_array();
}

// Build user data for JavaScript
$user_data_for_js = array_map(function($u) {
    return [
        'id' => (int)$u['id'],
        'name' => (!empty($u['emp_id']) ? $u['emp_id'].' — ' : '') . ($u['fullname'] ?: 'User #'.$u['id']),
        'emp_id' => $u['emp_id'] ?? '',
        'fullname' => $u['fullname'] ?? '',
        'avatar' => base_url('uploads/users/profile/default.png')
    ];
}, $users);
?>

<form method="post" action="<?= site_url('settings?group=user_permissions') ?>" class="app-form">

<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="up-title">Multiple Users Permissions</h5>
</div>

<div class="mb-3 col-md-12">
  <div class="input-group w-100">
    <span class="input-group-text finance-group rounded me-4">
      Select Users to Manage Their Permissions
      <span class="text-danger px-1">*</span>
    </span>
    <div class="multi-user-wrapper flex-grow-1">
      <div class="multi-user-select-wrapper w-100" id="multiUserSelectWrapper">
        <div class="multi-user-placeholder" id="multiUserPlaceholder">
          Click to search and select users…
        </div>
        <input type="text"
               class="form-control multi-user-search w-100"
               id="multiUserSearch"
               placeholder="Type to search users…"
               autocomplete="off"
               style="display:none;">
      </div>
      <div class="multi-user-dropdown w-100" id="multiUserDropdown"></div>
    </div>

  </div>
  <div class="selected-users-chips empty mt-2" id="selectedUsersChips">
    No users selected yet
  </div>
  <input type="hidden" name="user_ids" id="userIdsInput" value="" required>
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
               name="settings[grants][]" value="<?= e($perm) ?>">
      </div>

      <div class="perm-toggle" data-label="Deny">
        <input type="checkbox" class="form-check-input deny-cb"
               id="<?= $did ?>" data-pair="#<?= $gid ?>"
               name="settings[denies][]" value="<?= e($perm) ?>">
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const userData = <?= json_encode($user_data_for_js) ?>;
  let selectedUsers = new Set();
  
  // DOM Elements
  const multiUserWrapper = document.getElementById('multiUserSelectWrapper');
  const multiUserSearch = document.getElementById('multiUserSearch');
  const multiUserPlaceholder = document.getElementById('multiUserPlaceholder');
  const multiUserDropdown = document.getElementById('multiUserDropdown');
  const selectedUsersChips = document.getElementById('selectedUsersChips');
  const userIdsInput = document.getElementById('userIdsInput');
  
  // Initialize
  updateSelectedUsersUI();
  
  // Update the hidden input with selected user IDs
  function updateSelectedUsersInput() {
    userIdsInput.value = Array.from(selectedUsers).join(',');
  }
  
  // Render selected users as chips below the field
  function updateSelectedUsersUI() {
    selectedUsersChips.innerHTML = '';
    
    if (selectedUsers.size === 0) {
      selectedUsersChips.className = 'selected-users-chips empty';
      selectedUsersChips.textContent = 'No users selected yet';
      return;
    }
    
    selectedUsersChips.className = 'selected-users-chips';
    
    selectedUsers.forEach(userId => {
      const user = userData.find(u => u.id === userId);
      if (user) {
        const chip = document.createElement('div');
        chip.className = 'user-chip';
        chip.innerHTML = `
          <img src="${escapeHtml(user.avatar)}" alt="" class="user-chip-avatar">
          <span>${escapeHtml(user.name)}</span>
          <button type="button" class="user-chip-remove" data-user-id="${userId}">&times;</button>
        `;
        selectedUsersChips.appendChild(chip);
      }
    });
    
    // Add remove event listeners to chips
    selectedUsersChips.querySelectorAll('.user-chip-remove').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const userId = parseInt(this.getAttribute('data-user-id'));
        selectedUsers.delete(userId);
        updateSelectedUsersUI();
        updateSelectedUsersInput();
        renderUserOptions(multiUserSearch.value);
      });
    });
  }
  
  // Render user options in dropdown
  function renderUserOptions(searchTerm) {
    multiUserDropdown.innerHTML = '';
    
    const term = searchTerm.toLowerCase().trim();
    const filteredUsers = userData.filter(user => {
      if (selectedUsers.has(user.id)) return false;
      if (!term) return true;
      return user.name.toLowerCase().includes(term) || 
             user.emp_id.toLowerCase().includes(term) ||
             user.fullname.toLowerCase().includes(term);
    });
    
    if (filteredUsers.length === 0) {
      const noResults = document.createElement('div');
      noResults.className = 'no-users-message';
      noResults.textContent = term ? 'No users found' : 'No users available';
      multiUserDropdown.appendChild(noResults);
    } else {
      filteredUsers.forEach(user => {
        const option = document.createElement('div');
        option.className = 'multi-user-option';
        option.setAttribute('data-user-id', user.id);
        option.innerHTML = `
          <img src="${escapeHtml(user.avatar)}" alt="" class="multi-user-option-avatar">
          <span>${escapeHtml(user.name)}</span>
        `;
        
        option.addEventListener('click', function(e) {
          e.stopPropagation();
          const userId = parseInt(this.getAttribute('data-user-id'));
          selectedUsers.add(userId);
          updateSelectedUsersUI();
          updateSelectedUsersInput();
          multiUserSearch.value = '';
          multiUserPlaceholder.style.display = 'block';
          multiUserSearch.style.display = 'none';
          renderUserOptions('');
          multiUserDropdown.classList.remove('show');
        });
        
        multiUserDropdown.appendChild(option);
      });
    }
  }
  
  // Helper to escape HTML
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Toggle search input when clicking on the wrapper
  multiUserWrapper.addEventListener('click', function(e) {
    e.stopPropagation();
    multiUserPlaceholder.style.display = 'none';
    multiUserSearch.style.display = 'block';
    multiUserSearch.focus();
    renderUserOptions('');
    multiUserDropdown.classList.add('show');
  });
  
  // Search functionality
  multiUserSearch.addEventListener('input', function() {
    renderUserOptions(this.value);
  });
  
  multiUserSearch.addEventListener('focus', function() {
    renderUserOptions(this.value);
    multiUserDropdown.classList.add('show');
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    if (!multiUserWrapper.contains(event.target) && !multiUserDropdown.contains(event.target)) {
      multiUserDropdown.classList.remove('show');
      if (multiUserSearch.value === '') {
        multiUserPlaceholder.style.display = 'block';
        multiUserSearch.style.display = 'none';
      }
    }
  });
  
  // Keyboard navigation
  multiUserSearch.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      multiUserDropdown.classList.remove('show');
      if (this.value === '') {
        multiUserPlaceholder.style.display = 'block';
        this.style.display = 'none';
      }
    }
  });
  
  // Initial render of user options
  renderUserOptions('');
});
</script>

<script>
// Grant/Deny mutual exclusion (kept from original)
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
</script>