<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
/* Make the selected field smaller */
.form-select.js-searchable-select.form-select-sm {
  min-height: 20px !important;
  height: 20px !important;
  padding: 0.15rem 0.4rem !important;
  font-size: 10px !important;
  line-height: 1.0 !important;
}

/* Fix the arrow alignment */
.form-select.js-searchable-select.form-select-sm {
  background-position: right 0.6rem center !important;
  background-size: 12px 12px !important;
}
    
</style>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canManage  = staff_can('manage', 'users');
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
          $table_id     = $table_id ?? 'dataTable';
        ?>
    
        <button type="submit" 
                form="userRolesForm" 
                class="btn <?= $canManage ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                <?= $canManage ? '' : 'disabled' ?>
                title="Save role changes">
          <i class="fas fa-save me-1"></i> Save Changes
        </button>
        
        <div class="btn-divider"></div>

        <?php render_export_buttons([
            'filename' => $page_title ?? 'export'
        ]); ?> 
        
      </div>
    </div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => ['EMP ID', 'Designation'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

  <div class="card">
    <div class="card-header p-1">
      <form id="userRolesForm" method="post" action="<?= site_url('users') ?>" class="app-form">
        <div class="list-table-header app-scroll text-muted">
          <table class="table small table-sm table-bottom-border table-hover table-striped align-middle mb-2" id="<?= html_escape($table_id); ?>">
            <thead class="bg-light-primary">
              <tr class="app-sort">
                <th width="20%">Employee Name</th>
                <th>Designation</th>
                <th>Joining Salary</th>
                <th>Current Salary</th>
                <th>Last Increment</th>
              </tr>
            </thead>
            <tbody>


            <?php
            // Sort users by emp_id ascending (numeric-aware, ignores non-numeric prefixes)
            usort($users, function ($a, $b) {
                $aid = preg_replace('/[^0-9]/', '', $a['emp_id'] ?? '');
                $bid = preg_replace('/[^0-9]/', '', $b['emp_id'] ?? '');
            
                // Both have numeric parts — compare as integers
                if ($aid !== '' && $bid !== '') {
                    return (int)$aid - (int)$bid;
                }
            
                // Fall back to plain string comparison if no numeric part
                return strcmp($a['emp_id'] ?? '', $b['emp_id'] ?? '');
            });
            ?>

            <?php foreach ($users as $u): ?>
            <?php if (strtolower($u['user_role'] ?? '') === 'superadmin') continue; ?>
            
            <tr>
            
                <!-- Employee Name -->
                <td>
                    <div class="d-flex align-items-start gap-2">
                        <?= user_profile($u) ?>
                        <div class="d-flex flex-column lh-sm">
                            <span class="fw-medium">
                                <?= e($u['firstname'] . ' ' . $u['lastname']) ?>
                            </span>
                            <small class="text-muted">
                                <?= emp_id_display($u['emp_id']) ?>
                            </small>
                        </div>
                    </div>
                </td>
            
                <!-- Designation -->
                <td>
                    <select name="users[<?= $u['id'] ?>][emp_title]"
                            class="form-select form-select-sm js-searchable-select">
                        <option value="">— Select Designation —</option>
                        <?php foreach ($positions as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= $u['emp_title'] == $p['id'] ? 'selected' : '' ?>>
                                <?= html_escape($p['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            
                <!-- Joining Salary -->
                <td>
                    <input type="number"
                           step="0.01"
                           name="users[<?= $u['id'] ?>][joining_salary]"
                           class="form-control form-control-sm"
                           value="<?= html_escape($u['joining_salary'] ?? '') ?>">
                </td>
            
                <!-- Current Salary -->
                <td>
                    <input type="number"
                           step="0.01"
                           name="users[<?= $u['id'] ?>][current_salary]"
                           class="form-control form-control-sm"
                           placeholder="<?= c_format($u['current_salary'] ?? '') ?>"
                           value="<?= html_escape($u['current_salary'] ?? '') ?>">
                </td>
            
                <!-- Last Increment -->
                <td>
                    <input type="date"
                           name="users[<?= $u['id'] ?>][last_increment_date]"
                           class="form-control form-control-sm"
                           value="<?= html_escape($u['last_increment_date'] ?? '') ?>">
                </td>
            
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </form>
    </div>
  </div>
</div>

<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('users/modals/add_user_modal', [], true); ?>

<style>
  .filtered-out {
    display: none !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const roleFilter = document.getElementById('roleFilter');
  const teamFilter = document.getElementById('teamFilter');
  const reportingFilter = document.getElementById('reportingFilter');
  const searchFilter = document.getElementById('searchFilter');
  const clearFilters = document.getElementById('clearFilters');
  const userCount = document.getElementById('userCount');

  function applyFilters() {
    const roleValue = roleFilter.value.toLowerCase();
    const teamValue = teamFilter.value;
    const reportingValue = reportingFilter.value;
    const searchValue = searchFilter.value.toLowerCase();

    let visibleCount = 0;
    const rows = document.querySelectorAll('#usersmanageTable tbody tr');

    rows.forEach(row => {
      const rowRole = (row.getAttribute('data-role') || '').toLowerCase();
      const teamAttr = row.getAttribute('data-team') || '';
      const reportingAttr = row.getAttribute('data-reporting') || '';

      const roleMatch = !roleValue || rowRole === roleValue;
      const teamMatch = !teamValue || teamAttr === teamValue;
      const reportingMatch = !reportingValue || reportingAttr === reportingValue;

      const nameText = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
      const empId = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
      const searchMatch = !searchValue || nameText.includes(searchValue) || empId.includes(searchValue);

      if (roleMatch && teamMatch && reportingMatch && searchMatch) {
        row.classList.remove('filtered-out');
        visibleCount++;
      } else {
        row.classList.add('filtered-out');
      }
    });

    if (userCount) {
      userCount.textContent = `${visibleCount} user${visibleCount !== 1 ? 's' : ''} displayed`;
    }
  }

  // Event listeners
  roleFilter.addEventListener('change', applyFilters);
  teamFilter.addEventListener('change', applyFilters);
  reportingFilter.addEventListener('change', applyFilters);
  searchFilter.addEventListener('input', applyFilters);

  clearFilters.addEventListener('click', function () {
    roleFilter.value = '';
    teamFilter.value = '';
    reportingFilter.value = '';
    searchFilter.value = '';
    applyFilters();
  });

  // Apply on load
  applyFilters();
});
</script>
<style>
    /* All dropdowns in this page smaller */
#usersmanageTable .form-select {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    line-height: 1.2;
}

</style>