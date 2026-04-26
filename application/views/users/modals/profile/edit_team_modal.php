<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('e')) {
    function e($str) { return html_escape($str ?? ''); }
}

$user_role = strtolower($user['user_role'] ?? 'employee');

/**
 * Build role-based user lists
 */
$teamlead_list = $teamLeads ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'teamlead');
$manager_list  = $managers  ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'manager');
$director_list = $directors ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'director');
?>

<div class="modal fade" id="editTeamModal" tabindex="-1" aria-labelledby="editTeamModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="editTeamModalLabel">Edit Team Information</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Reporting Summary -->
      <div class="alert alert-light-primary m-4 mb-0">
        <h6 class="mb-3 text-primary">Current Reporting Structure</h6>

        <?php if ($user_role === 'employee'): ?>
          <div><strong>Team Lead:</strong> <?= user_profile_image($teamLeadName ?? 'Not assigned'); ?></div>

        <?php elseif ($user_role === 'teamlead'): ?>
          <div><strong>Manager:</strong> <?= user_profile_image($managerName ?? 'Not assigned'); ?></div>

        <?php elseif ($user_role === 'manager'): ?>
          <div><strong>Director:</strong> <?= user_profile_image($reportingName ?? 'Not assigned'); ?></div>

        <?php else: ?>
          <div>No reporting hierarchy for this role.</div>
        <?php endif; ?>

        <div><strong class="mt-2">Team:</strong> <?= e($teamName ?? 'Not assigned'); ?></div>
      </div>

      <!-- Form -->
      <form action="<?= site_url('profile_editor/edit_team/' . (int)$user['id']); ?>" method="post" class="app-form">
        <div class="modal-body p-4">
          <div class="row">

            <!-- TEAM -->
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Team</label>
                <select class="form-select" name="emp_team">
                  <option value="">Select Team</option>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?= (int)$team['id']; ?>"
                      <?= ((int)($user['emp_team'] ?? 0) === (int)$team['id']) ? 'selected' : ''; ?>>
                      <?= e($team['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- EMPLOYEE → TEAM LEAD -->
            <?php if ($user_role === 'employee'): ?>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Team Lead</label>
                  <select class="form-select" name="emp_teamlead">
                    <option value="">Select Team Lead</option>
                    <?php foreach ($teamlead_list as $tl): ?>
                      <?php if ((int)$tl['id'] === (int)$user['id']) continue; ?>
                      <option value="<?= (int)$tl['id']; ?>"
                        <?= ((int)($user['emp_teamlead'] ?? 0) === (int)$tl['id']) ? 'selected' : ''; ?>>
                        <?= e($tl['fullname'] ?? ($tl['first_name'].' '.$tl['last_name'])); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            <?php endif; ?>

            <!-- TEAM LEAD → MANAGER -->
            <?php if ($user_role === 'teamlead'): ?>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Manager</label>
                  <select class="form-select" name="emp_manager">
                    <option value="">Select Manager</option>
                    <?php foreach ($manager_list as $mgr): ?>
                      <?php if ((int)$mgr['id'] === (int)$user['id']) continue; ?>
                      <option value="<?= (int)$mgr['id']; ?>"
                        <?= ((int)($user['emp_manager'] ?? 0) === (int)$mgr['id']) ? 'selected' : ''; ?>>
                        <?= e($mgr['fullname'] ?? ($mgr['first_name'].' '.$mgr['last_name'])); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            <?php endif; ?>

            <!-- MANAGER → DIRECTOR -->
            <?php if ($user_role === 'manager'): ?>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Director</label>
                  <select class="form-select" name="emp_reporting">
                    <option value="">Select Director</option>
                    <?php foreach ($director_list as $dir): ?>
                      <?php if ((int)$dir['id'] === (int)$user['id']) continue; ?>
                      <option value="<?= (int)$dir['id']; ?>"
                        <?= ((int)($user['emp_reporting'] ?? 0) === (int)$dir['id']) ? 'selected' : ''; ?>>
                        <?= e($dir['fullname'] ?? ($dir['first_name'].' '.$dir['last_name'])); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            <?php endif; ?>

            <!-- DIRECTOR → NOTHING -->
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
      </form>

    </div>
  </div>
</div>