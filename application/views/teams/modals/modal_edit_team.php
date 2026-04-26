<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php foreach ($teams as $row):
  $tid           = (int)$row['id'];
  $curLeadId     = (int)($row['teamlead_id'] ?? 0);
  $curManagerId  = (int)($row['manager_id']  ?? 0);
?>
<div class="modal fade" id="editTeamModal<?= $tid ?>" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-top">
    <form method="post" action="<?= site_url('teams') ?>" class="modal-content app-form">
      <input type="hidden" name="update_team" value="1">
      <input type="hidden" name="team_id"     value="<?= $tid ?>">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="fas fa-edit me-2"></i>Edit — <?= html_escape($row['name']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Team Name -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">
            Team Name <span class="text-danger">*</span>
          </label>
          <input type="text" name="team_name" class="form-control form-control-sm"
                 required value="<?= html_escape($row['name']) ?>" maxlength="120">
        </div>

        <!-- Department -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">
            Department <span class="text-danger">*</span>
          </label>
          <select name="department_id" class="form-select form-select-sm" required>
            <option value="">— Select department —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int)$d['id'] ?>"
                <?= ((int)$d['id'] === (int)($row['department_id'] ?? 0)) ? 'selected' : '' ?>>
                <?= html_escape($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Team Lead -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">Team Lead</label>
          <select name="teamlead_id" class="form-select form-select-sm">
            <option value="">— None —</option>
            <?php foreach ($eligible_leads as $u): ?>
              <option value="<?= (int)$u['id'] ?>"
                <?= ((int)$u['id'] === $curLeadId) ? 'selected' : '' ?>>
                <?= !empty($u['emp_id']) ? html_escape($u['emp_id']).' — ' : '' ?>
                <?= html_escape(trim($u['firstname'].' '.$u['lastname'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text text-warning-emphasis">
            <i class="fas fa-info-circle me-1"></i>
            Changing the lead updates reporting lines for all team members.
          </div>
        </div>

        <!-- Manager -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">Manager</label>
          <select name="manager_id" class="form-select form-select-sm">
            <option value="">— None —</option>
            <?php foreach ($eligible_managers as $u): ?>
              <option value="<?= (int)$u['id'] ?>"
                <?= ((int)$u['id'] === $curManagerId) ? 'selected' : '' ?>>
                <?= !empty($u['emp_id']) ? html_escape($u['emp_id']).' — ' : '' ?>
                <?= html_escape(trim($u['firstname'].' '.$u['lastname'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="fas fa-save me-1"></i>Update Team
        </button>
      </div>

    </form>
  </div>
</div>
<?php endforeach; ?>