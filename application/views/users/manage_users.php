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
        ?>
    
        <button type="submit" 
                form="userRolesForm" 
                class="btn <?= $canManage ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                <?= $canManage ? '' : 'disabled' ?>
                title="Save role changes">
          <i class="fas fa-save me-1"></i> Save Changes
        </button>
        
        <div class="btn-divider"></div>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'usersmanageTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
            <a class="btn btn-light-primary icon-btn b-r-4" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button"
           aria-expanded="false" aria-controls="multiCollapseExample1"><i class="ti ti-question-mark"></i></a>

            <a class="btn btn-light-primary icon-btn b-r-4" data-bs-toggle="collapse" href="#listFilter" role="button"
           aria-expanded="false" aria-controls="listFilter"><i class="ti ti-filter"></i></a>     
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
    
<div class="col-sm-12">
  <div class="collapse multi-collapse" id="multiCollapseExample1">
    <div class="card card-body">
      <h6 class="mb-2 main-title">
        <i class="fas fa-info-circle me-2 text-primary"></i>Instructions
      </h6>
      <p class="text-muted mb-3 small">Efficiently manage users, assign roles, organize teams, and configure reporting hierarchies with ease.</p>
      <ul class="mb-0 list-unstyled">
        <li class="text-muted small mb-1">
          <i class="fas fa-arrow-right me-1"></i> Use this page to manage user roles, teams, and reporting structure
        </li>
        <li class="text-muted small mb-1">
          <i class="fas fa-arrow-right me-1"></i> Changes take effect immediately after saving
        </li>
        <li class="text-muted small mb-1">
          <i class="fas fa-arrow-right me-1"></i> Employees report to Team Leads, Team Leads report to Managers, Managers report to Admins
        </li>
        <li class="text-muted small">
          <i class="fas fa-arrow-right me-1"></i> Deactivating a user will prevent them from logging in
        </li>
      </ul>
    </div>
  </div>
</div>

  <div class="collapse multi-collapse" id="listFilter">
  <div class="card mb-4">
    <div class="card-body">
      <form id="filterForm" class="row g-3">
        <div class="col-md-3">
          <select id="roleFilter" class="form-select text-smll">
            <option value="">All Roles</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= e($r) ?>"><?= ucfirst(e($r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-3">
          <select id="teamFilter" class="form-select text-smll">
            <option value="">All Teams</option>
            <?php foreach ($teams as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-3">
          <select id="reportingFilter" class="form-select text-smll">
            <option value="">All Reporting Persons</option>
            <?php 
              $allReporters = array_merge($teamLeads, $managers, $admins);
              foreach ($allReporters as $r): 
            ?>
              <option value="<?= $r['id'] ?>">
                <?= e($r['first_name'] . ' ' . $r['last_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-3">
          <div class="input-group">
            <input type="text" id="searchFilter" class="form-control text-smll" placeholder="Search...">
            <button class="btn btn-primary icon-btn b-r-4" type="button" id="clearFilters">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
  </div>

  <div class="card">
    <div class="card-header p-1">
      <form id="userRolesForm" method="post" action="<?= site_url('users/update_roles') ?>" class="app-form">
        <div class="list-table-header app-scroll text-muted">
          <table class="table small table-sm table-bottom-border table-hover table-striped align-middle mb-2" id="usersmanageTable">
            <thead class="bg-light-primary">
              <tr class="app-sort">
                <th width="14%">Employee Name</th>
                <th>Department</th>                
                <th>Designation</th>
                <th>Current Role</th>
                <th>Current Team</th>
                <th>Reporting Person</th>
                <th width="7%" class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>

            <?php
            // --- Active first (is_active=1), then alphabetical by full name ---
            if (is_array($users)) {
                usort($users, function ($a, $b) {
                    $ai = (int)($a['is_active'] ?? 0);
                    $bi = (int)($b['is_active'] ?? 0);
                    if ($ai !== $bi) {
                        return $bi - $ai; // active (1) before inactive (0)
                    }
                    // Secondary stable sort by name (firstname + lastname)
                    $an = trim(($a['firstname'] ?? '') . ' ' . ($a['lastname'] ?? ''));
                    $bn = trim(($b['firstname'] ?? '') . ' ' . ($b['lastname'] ?? ''));
                    return strcasecmp($an, $bn);
                });
            }
            ?>
                
              <?php foreach ($users as $u): ?>
                <?php if (strtolower($u['user_role'] ?? '') === 'superadmin') continue; ?>
                <?php 
                  // Convert role to lowercase for consistency
                  $userRole = strtolower($u['user_role']);
                  $reportingId = '';
                  if ($userRole === 'employee') {
                    $reportingId = $u['emp_teamlead'];
                  } elseif ($userRole === 'teamlead') {
                    $reportingId = $u['emp_manager'];
                  } elseif ($userRole === 'manager') {
                    $reportingId = $u['emp_reporting'];
                  }
                ?>
                <tr class="<?= $u['is_active'] ? '' : 'table-secondary' ?>"
                    data-role="<?= e($userRole) ?>"
                    data-team="<?= $u['emp_team'] ?>"
                    data-reporting="<?= $reportingId ?>">
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

                    <td>
                      <select name="users[<?= $u['id'] ?>][emp_department]" class="form-select form-select-sm js-searchable-select" onchange="this.classList.toggle('is-changed')">
                        <option value="">— Select Department —</option>
                        <?php foreach ($departments as $dept): ?>
                          <option value="<?= $dept['id'] ?>" <?= $u['emp_department'] == $dept['id'] ? 'selected' : '' ?>>
                            <?= html_escape($dept['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </td>                  
                  <td>
                <select name="users[<?= $u['id'] ?>][emp_title]" class="form-select form-select-sm js-searchable-select">
                  <option value="">— Select Designation —</option>
                  <?php foreach ($positions as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $u['emp_title'] == $p['id'] ? 'selected' : '' ?>>
                      <?= html_escape($p['title']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                </td>

                  <td>
                    <select name="users[<?= $u['id'] ?>][role]" class="form-select form-select-sm js-searchable-select" onchange="this.classList.toggle('is-changed')">
                      <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= strtolower($u['user_role']) === $r ? 'selected' : '' ?>>
                          <?= ucfirst($r) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <select name="users[<?= $u['id'] ?>][emp_team]" class="form-select form-select-sm js-searchable-select" onchange="this.classList.toggle('is-changed')">
                      <option value="">— Select Team —</option>
                      <?php foreach ($teams as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $u['emp_team'] == $t['id'] ? 'selected' : '' ?>>
                          <?= e($t['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <?php
                      switch ($userRole) {
                        case 'employee':
                          $pool = $teamLeads;
                          $field = 'emp_teamlead';
                          break;
                        case 'teamlead':
                          $pool = $managers;
                          $field = 'emp_manager';
                          break;
                        case 'manager':
                          $pool = $admins;
                          $field = 'emp_reporting';
                          break;
                        default: 
                          $pool = []; 
                          $field = null;
                      }
                    ?>
                    <?php if ($field): ?>
                      <select name="users[<?= $u['id'] ?>][<?= $field ?>]" class="form-select form-select-sm js-searchable-select" onchange="this.classList.toggle('is-changed')">
                        <option value="">— Select —</option>
                        <?php foreach ($pool as $p): ?>
                          <option value="<?= $p['id'] ?>" <?= $u[$field] == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['first_name'] . ' ' . $p['last_name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                    <td class="text-center align-middle">
                      <div class="d-flex justify-content-center text-smll align-items-center gap-2">
                        <?php $canManage = staff_can('manage', 'users'); ?>
                        <div class="form-check form-switch m-0">
                          <input type="hidden" name="users[<?= $u['id'] ?>][active]" value="0">
                          <input type="checkbox"
                                 class="form-check"
                                 role="switch"
                                 name="users[<?= $u['id'] ?>][active]"
                                 value="1"
                                 <?= $u['is_active'] ? 'checked' : '' ?>
                                 <?= $canManage ? '' : 'disabled' ?>>
                        </div>
                        <span class="pill pill-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                          <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                      </div>
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