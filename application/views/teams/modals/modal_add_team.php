<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="addTeamModal" tabindex="-1" aria-labelledby="addTeamModalLabel"
     aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-top">
    <form method="post" action="<?= site_url('teams') ?>" class="modal-content app-form">
      <input type="hidden" name="add_team" value="1">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="addTeamModalLabel">
          <i class="fas fa-plus me-2"></i>Add New Team
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
                 required placeholder="e.g. Billing Operations" maxlength="120">
        </div>

        <!-- Department -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">
            Department <span class="text-danger">*</span>
          </label>
          <select name="department_id" class="form-select form-select-sm" required>
            <option value="">— Select department —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int)$d['id'] ?>"><?= html_escape($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Team Lead -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">Team Lead</label>
          <select name="teamlead_id" class="form-select form-select-sm">
            <option value="">— None —</option>
            <?php foreach ($eligible_leads as $u): ?>
              <option value="<?= (int)$u['id'] ?>">
                <?= !empty($u['emp_id']) ? html_escape($u['emp_id']).' — ' : '' ?>
                <?= html_escape(trim($u['firstname'].' '.$u['lastname'])) ?>
                <span class="text-muted">(<?= html_escape($u['user_role']) ?>)</span>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Selected user will be assigned to this team and set as lead.</div>
        </div>

        <!-- Manager -->
        <div class="mb-3">
          <label class="form-label fw-semibold small">Manager</label>
          <select name="manager_id" class="form-select form-select-sm">
            <option value="">— None —</option>
            <?php foreach ($eligible_managers as $u): ?>
              <option value="<?= (int)$u['id'] ?>">
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
          <i class="fas fa-save me-1"></i>Save Team
        </button>
      </div>

    </form>
  </div>
</div>