<!-- Header -->
<div class="card-header bg-light-primary mb-2">
  <div class="d-flex align-items-center justify-content-between">
    <h6 class="card-title text-primary mb-0">
      <i class="ti ti-building me-2" style="font-size:18px;"></i>
      Staff Roles
    </h6>

    <?php if (staff_can('manage', 'company')): ?>
      <button type="button"
              class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#addRoleModal"
        <i class="ti ti-plus"></i> Add New Role
      </button>
    <?php endif; ?>
  </div>
</div>

<?php
  $canEdit   = staff_can('edit',   'users');
  $canDelete = staff_can('delete', 'users');
?>

<table class="table table-bottom-border small table-hover align-middle mb-2">
  <thead class="bg-light-primary">
    <tr>
      <th width="5%"  class="text-center">#</th>
      <th width="15%" class="text-start">Role Name</th>
      <th width="40%" class="text-start">Description</th>
      <th width="25%" class="text-start">
        Current Users <small class="text-muted">(Including In-Active)</small>
      </th>
      <th width="15%" class="text-end">
        Actions
        <i class="ti ti-question-circle"
           data-bs-toggle="tooltip"
           title="Roles with users cannot be deleted."></i>
      </th>
    </tr>
  </thead>

  <tbody>
  <?php if (!empty($roles)): ?>
    <?php foreach ($roles as $i => $role): ?>
      <?php
        $rawName      = (string)($role['role_name'] ?? '');
        $keyName      = strtolower($rawName);
        $prettyName   = ucwords(str_replace('_', ' ', $rawName));
        $descRaw      = (string)($role['description'] ?? '');
        $userCount    = (int)($role['user_count'] ?? 0);
        $canEditRow   = role_policy('edit', $role);
        $canDeleteRow = role_policy('delete', $role);

        // users for this role (already resolved in controller)
        $usersForRole = $avatarsByRole[$keyName] ?? [];
        $MAX          = 5;
        $visibleUsers = array_slice($usersForRole, 0, $MAX);
        $remaining    = max(0, $userCount - count($visibleUsers));
      ?>

      <tr>
        <td class="text-center"><?= $i + 1 ?></td>

        <td>
          <?= e($prettyName) ?>
          <span class="badge bg-light-primary ms-2">
            <b><?= $userCount ?></b> Total
          </span>
        </td>

        <td class="text-muted">
          <?= $descRaw !== '' ? e($descRaw) : '<span class="text-muted">—</span>' ?>
        </td>

        <!-- USERS (via helper only) -->
        <td>
          <div class="d-flex align-items-center gap-1 flex-wrap">
            <?php foreach ($visibleUsers as $u): ?>
              <?= user_profile($u['id'], 'xs') ?>
            <?php endforeach; ?>

            <?php if ($remaining > 0): ?>
              <span class="badge bg-secondary small">
                +<?= $remaining ?>
              </span>
            <?php endif; ?>
          </div>
        </td>

        <!-- ACTIONS -->
        <td class="text-end">

          <!-- Edit -->
          <button type="button"
                  class="btn btn-ssm btn-outline-primary me-1"
                  <?= $canEditRow ? 'data-bs-toggle="modal" data-bs-target="#editRoleModal"' : 'disabled' ?>
                  data-original-name="<?= e($rawName) ?>"
                  data-role-name="<?= e($rawName) ?>"
                  data-description="<?= e($descRaw) ?>">
            Edit
          </button>

          <!-- Delete -->
          <?php if ($canDeleteRow): ?>
            <form method="post"
                  action="<?= site_url('admin/setup/company/delete_role') ?>"
                  class="d-inline"
                  onsubmit="return confirm('Delete role “<?= e($rawName) ?>”?');">
              <input type="hidden" name="role_name" value="<?= e($rawName) ?>">
              <button type="submit" class="btn btn-ssm btn-outline-danger">
                Delete
              </button>
            </form>
          <?php else: ?>
            <button type="button"
                    class="btn btn-ssm btn-outline-danger"
                    disabled
                    title="Cannot delete">
              Delete
            </button>
          <?php endif; ?>

        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr>
      <td colspan="5" class="text-center text-muted py-4">
        No roles found.
      </td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>

<?php $CI =& get_instance(); ?>
<?= $CI->load->view('admin/setup/company/modals/role_add_modal', true); ?>
<?= $CI->load->view('admin/setup/company/modals/role_edit_modal', true); ?>



<script>
document.addEventListener('DOMContentLoaded', function () {
  // Live counter for Add modal
  const addDesc = document.getElementById('role_description');
  const addCounter = document.getElementById('descCount');
  if (addDesc && addCounter) {
    addDesc.addEventListener('input', function(){ addCounter.textContent = this.value.length; });
  }

  // Wire up Edit modal population
  const editModal = document.getElementById('editRoleModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (event) {
      const btn = event.relatedTarget;
      if (!btn) return;

      const original = btn.getAttribute('data-original-name') || '';
      const roleName = btn.getAttribute('data-role-name') || '';
      const desc     = btn.getAttribute('data-description') || '';

      const inputOriginal = editModal.querySelector('#original_name');
      const inputName     = editModal.querySelector('#edit_role_name');
      const inputDesc     = editModal.querySelector('#edit_role_description');
      const editCounter   = editModal.querySelector('#editDescCount');

      if (inputOriginal) inputOriginal.value = original;
      if (inputName)     inputName.value     = roleName;
      if (inputDesc)     inputDesc.value     = desc;
      if (editCounter)   editCounter.textContent = (inputDesc.value || '').length;
    });

    // Keep edit description counter updated
    const editDesc = document.getElementById('edit_role_description');
    const editCounter = document.getElementById('editDescCount');
    if (editDesc && editCounter) {
      editDesc.addEventListener('input', function(){ editCounter.textContent = this.value.length; });
    }
  }
});
</script>