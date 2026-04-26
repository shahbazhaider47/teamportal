<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Defensive defaults (modal views are isolated)
$users   = $users   ?? [];
$modules = $modules ?? [];
?>

<div class="modal fade" id="bulkPermissionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <form method="post"
            action="<?= site_url('settings/bulk_user_permissions') ?>"
            class="app-form">

        <div class="modal-header">
          <h5 class="modal-title">
            <i class="ti ti-users me-1"></i>
            Assign Permissions to Multiple Users
          </h5>
          <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"></button>
        </div>

        <!-- Scrollable body -->
        <div class="modal-body" style="max-height:70vh; overflow-y:auto;">

          <!-- USER SEARCH -->
          <div class="mb-3 position-relative">
            <label class="form-label fw-semibold">Search Users</label>

            <input type="text"
                   id="bulkUserSearch"
                   class="form-control"
                   placeholder="Search by name or employee ID">

            <!-- Search dropdown -->
            <div id="bulkUserResults"
                 class="list-group position-absolute w-100 shadow-sm"
                 style="z-index:1056; display:none; max-height:260px; overflow-y:auto;">
            </div>

            <small class="text-muted">
              Type to search. Up to 10 results shown.
            </small>
          </div>

          <!-- SELECTED USERS -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Selected Users</label>

            <div id="selectedUsersBox"
                 class="border rounded p-2 d-flex flex-wrap gap-2"
                 style="min-height:48px;">
              <span class="text-muted small" id="noUsersSelected">
                No users selected
              </span>
            </div>

            <!-- Hidden inputs go here -->
            <div id="selectedUsersInputs"></div>
          </div>

          <hr>

          <!-- PERMISSIONS -->
          <?php if (!empty($modules)): ?>
            <?php foreach ($modules as $moduleKey => $actions): ?>
              <section class="mod">
                <header class="mod-hd bg-light-primary">
                  <p class="mod-title">
                    <?= (is_array($actions) && isset($actions['name']))
                        ? e($actions['name'])
                        : ucfirst($moduleKey); ?>
                  </p>
                </header>

                <div class="perm-grid">
                  <div class="perm-head">Permission</div>
                  <div class="perm-head text-center">Allow</div>
                  <div class="perm-head text-center">Deny</div>

                  <?php
                  $actionArr = $actions['actions'] ?? $actions;
                  foreach ($actionArr as $actionKey => $actionMeta):
                    $label = is_array($actionMeta)
                      ? ($actionMeta['label'] ?? ucfirst($actionKey))
                      : $actionMeta;
                    $permKey = $moduleKey . ':' . $actionKey;
                  ?>
                    <div class="perm-row">
                      <div class="perm-name">
                        <?= e($label) ?>
                        <span class="perm-key"><?= e($permKey) ?></span>
                      </div>

                      <div class="perm-toggle text-center">
                        <input type="checkbox"
                               class="form-check-input"
                               name="grants[]"
                               value="<?= e($permKey) ?>">
                      </div>

                      <div class="perm-toggle text-center">
                        <input type="checkbox"
                               class="form-check-input"
                               name="denies[]"
                               value="<?= e($permKey) ?>">
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="alert alert-warning mb-0">
              No permissions available.
            </div>
          <?php endif; ?>

        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal">
            Cancel
          </button>

          <button type="submit"
                  class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            Apply Permissions
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script>
(function () {
  const USERS = <?= json_encode($users) ?>;

  const searchInput   = document.getElementById('bulkUserSearch');
  const resultsBox   = document.getElementById('bulkUserResults');
  const selectedBox  = document.getElementById('selectedUsersBox');
  const hiddenBox    = document.getElementById('selectedUsersInputs');
  const emptyHint    = document.getElementById('noUsersSelected');

  let selectedUsers = {};

  function renderSelectedUsers() {
    selectedBox.innerHTML = '';
    hiddenBox.innerHTML   = '';

    const ids = Object.keys(selectedUsers);

    if (ids.length === 0) {
      selectedBox.appendChild(emptyHint);
      emptyHint.style.display = 'inline';
      return;
    }

    emptyHint.style.display = 'none';

    ids.forEach(id => {
      const u = selectedUsers[id];

      const badge = document.createElement('span');
      badge.className = 'badge bg-light text-dark border d-flex align-items-center gap-2 px-2 py-1';

      badge.innerHTML = `
        <strong>${u.fullname || 'User #' + u.id}</strong>
        <small class="text-muted">${u.emp_id || ''}</small>
        <i class="ti ti-x cursor-pointer"></i>
      `;

      badge.querySelector('i').onclick = () => {
        delete selectedUsers[id];
        renderSelectedUsers();
      };

      selectedBox.appendChild(badge);

      const input = document.createElement('input');
      input.type  = 'hidden';
      input.name  = 'user_ids[]';
      input.value = u.id;
      hiddenBox.appendChild(input);
    });
  }

  searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    resultsBox.innerHTML = '';

    if (!q) {
      resultsBox.style.display = 'none';
      return;
    }

    const matches = USERS.filter(u =>
      (u.fullname || '').toLowerCase().includes(q) ||
      (u.emp_id || '').toLowerCase().includes(q)
    ).slice(0, 10);

    if (matches.length === 0) {
      resultsBox.style.display = 'none';
      return;
    }

    matches.forEach(u => {
      if (selectedUsers[u.id]) return;

      const item = document.createElement('a');
      item.href = 'javascript:void(0)';
      item.className = 'list-group-item list-group-item-action';

      item.innerHTML = `
        <strong>${u.fullname || 'User #' + u.id}</strong><br>
        <small class="text-muted">${u.emp_id || ''}</small>
      `;

      item.onclick = () => {
        selectedUsers[u.id] = u;
        renderSelectedUsers();
        resultsBox.style.display = 'none';
        searchInput.value = '';
      };

      resultsBox.appendChild(item);
    });

    resultsBox.style.display = 'block';
  });

  document.addEventListener('click', function (e) {
    if (!resultsBox.contains(e.target) && e.target !== searchInput) {
      resultsBox.style.display = 'none';
    }
  });
})();
</script>
