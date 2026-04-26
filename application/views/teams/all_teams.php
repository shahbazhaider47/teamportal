<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>
<?php
$table_id     = $table_id ?? 'dataTable';
$canCreate = staff_can('create', 'teams');
$canExport = staff_can('export', 'general');
$canPrint  = staff_can('print',  'general');

/* Avatar helper (reused in table rows) */
$rowAvatar = function(?string $file, string $initials, int $sz = 24): string {
    $fb = base_url('assets/images/default-avatar.png');
    if ($file && file_exists(FCPATH . 'uploads/users/profile/' . $file)) {
        $src = base_url('uploads/users/profile/' . $file);
        return '<img src="'.html_escape($src).'" width="'.$sz.'" height="'.$sz.'"'
             . ' class="rounded-circle" loading="lazy"'
             . ' onerror="this.onerror=null;this.src=\''.html_escape($fb).'\'">';
    }
    return '<span class="rounded-circle bg-light-primary d-inline-flex align-items-center'
         . ' justify-content-center fw-semibold" style="width:'.$sz.'px;height:'.$sz.'px;font-size:'
         . max(9,(int)($sz*.38)).'px">'.html_escape(strtoupper(substr($initials,0,2))).'</span>';
};
?>

<div class="container-fluid">

  <div class="view-header mb-3">
    <div class="view-icon me-3"><i class="fa-solid fa-users fa-fw"></i></div>
    <div class="flex-grow-1">
      <div class="view-title"><?= $page_title ?>
        <span class="badge bg-light-primary border"><?= count($teams) ?> team<?= count($teams) !== 1 ? 's' : '' ?></span>
        <div class="view-sub">All teams in your company</div>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">

      <button type="button"
              class="btn <?= $canCreate ? 'btn-primary' : 'btn-secondary' ?> btn-header"
              <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#addTeamModal"' : 'disabled' ?>
              title="Add New Team">
        <i class="fas fa-plus me-1"></i> Add New
      </button>
      
      <?php if (staff_can('view_global','users') || staff_can('manage','users')): ?>
        <a href="<?= site_url('users/manage_users') ?>"
           class="btn btn-outline-primary btn-header" title="Manage users and team membership">
          <i class="fas fa-user-slash me-1"></i> Manage Users
        </a>
      <?php endif; ?>

      <?php if (staff_can('view_global','vault') || staff_can('view_own','vault')): ?>
        <a href="<?= site_url('login_vault') ?>"
           class="btn btn-outline-primary btn-header" title="Team passwords">
          <i class="fas fa-lock me-1"></i> Team Passwords
        </a>
      <?php endif; ?>
        
    <div class="btn-divider mt-1"></div>        

<a href="<?= site_url('teams/rankings') ?>"
   class="btn btn-header btn-outline-primary">
  <i class="ti ti-trophy me-1"></i> Team Rankings
</a>

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
                    'exclude_columns' => ['EMP ID', 'Current Salary', 'Date of Joining'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
  <!-- ── Teams Table ─────────────────────────────────────────── -->
  <div class="card">
    <div class="list-table-header app-scroll text-muted">
      <table class="table small table-bottom-border align-middle mb-2" id="teamsTable">
        <thead class="bg-light-primary">
          <tr>
            <th width="60" class="text-center">ID</th>
            <th>Team Name</th>
            <th>Department</th>
            <th>Team Lead</th>
            <th>Manager</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($teams)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No teams found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($teams as $row):
            $tid = (int)$row['id'];
          ?>
          <tr>
            <!-- ID -->
            <td class="text-center text-muted small"><?= $tid ?></td>

            <!-- Team Name -->
            <td>
              <div class="d-flex align-items-center gap-2">
            
                <!-- Icon -->
                <div class="rounded d-flex align-items-center justify-content-center bg-light-primary flex-shrink-0"
                     style="width:28px;height:28px">
                  <i class="fas fa-users text-primary" style="font-size:11px"></i>
                </div>
            
                <!-- Name (top) + Count (bottom) -->
                <div class="d-flex flex-column">
                  <span class="fw-semibold small mb-0">
                    <?= html_escape($row['name']) ?>
                  </span>
            
                  <span class="badge bg-light-primary mt-1"
                        style="width:fit-content;">
                    <?= (int)($row['member_count'] ?? 0) ?> Members
                  </span>
                </div>
            
              </div>
            </td>

            <!-- Department -->
            <td>
              <?php if (!empty($row['department_name'])): ?>
                <span class="badge bg-light-primary"><?= html_escape($row['department_name']) ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>

            <!-- Team Lead -->
            <td>
              <?php if (!empty($row['teamlead_id'])): ?>
                <div class="d-flex align-items-center gap-2">
                  <?= $rowAvatar($row['lead_avatar'] ?? null,
                        $row['lead_name'] ?? 'L', 26) ?>
                  <div class="lh-sm">
                    <div class="small fw-semibold"><?= html_escape($row['lead_name'] ?? '') ?></div>
                    <?php if (!empty($row['lead_emp_id'])): ?>
                      <div style="font-size:10px" class="text-muted"><?= emp_id_display($row['lead_emp_id']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <span class="text-muted small">Not assigned</span>
              <?php endif; ?>
            </td>

            <!-- Manager -->
            <td>
              <?php if (!empty($row['manager_id'])): ?>
                <div class="d-flex align-items-center gap-2">
                  <?= $rowAvatar($row['manager_avatar'] ?? null,
                        $row['manager_name'] ?? 'M', 26) ?>
                  <div class="lh-sm">
                    <div class="small fw-semibold"><?= html_escape($row['manager_name'] ?? '') ?></div>
                    <?php if (!empty($row['manager_emp_id'])): ?>
                      <div style="font-size:10px" class="text-muted"><?= emp_id_display($row['manager_emp_id']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <span class="text-muted small">Not assigned</span>
              <?php endif; ?>
            </td>

            <!-- Actions -->
            <td class="text-end">
              <div class="btn-group btn-group-sm">

                <a href="<?= site_url('teams/team_progress/' . (int)$row['id']) ?>"
                   class="btn btn-light-primary btn-ssm"
                   title="Team Progress">
                  <i class="ti ti-chart-bar"></i>
                </a>

                <button type="button" class="btn btn-light-primary btn-ssm"
                        data-bs-toggle="modal"
                        data-bs-target="#addUsersModal<?= $tid ?>"
                        title="Add Users">
                  <i class="fas fa-user-plus"></i>
                </button>
                <button type="button" class="btn btn-light-primary btn-ssm"
                        data-bs-toggle="modal"
                        data-bs-target="#viewTeamModal<?= $tid ?>"
                        title="View Team">
                  <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-light-primary btn-ssm"
                        data-bs-toggle="modal"
                        data-bs-target="#editTeamModal<?= $tid ?>"
                        title="Edit Team">
                  <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger btn-ssm"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteTeamModal<?= $tid ?>"
                        title="Delete Team">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php

$CI->load->view('teams/modals/modal_add_team');
$CI->load->view('teams/modals/modal_edit_team');
$CI->load->view('teams/modals/modal_view_team');
$CI->load->view('teams/modals/modal_add_users');
$CI->load->view('teams/modals/modal_delete_team');
?>