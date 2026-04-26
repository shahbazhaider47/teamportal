<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php foreach ($teams as $row):
  $tid  = (int)$row['id'];
  $avail = $available_users_by_team[$tid] ?? [];
?>
<div class="modal fade" id="addUsersModal<?= $tid ?>" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-top">
    <form method="post" action="<?= site_url('teams') ?>" class="modal-content app-form">
      <input type="hidden" name="add_users" value="1">
      <input type="hidden" name="team_id"   value="<?= $tid ?>">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="fas fa-user-plus me-2"></i>
          Add Users — <span class="text-white-50"><?= html_escape($row['name']) ?></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <?php if (empty($avail)): ?>
          <div class="alert alert-info m-3 mb-0">
            <i class="fas fa-info-circle me-2"></i>No available users to add to this team.
          </div>
        <?php else: ?>
          <div class="px-3 pt-3 pb-1">
            <p class="text-muted small mb-0">
              Checking a user who already belongs to another team will
              <strong>move</strong> them here.
            </p>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle small table-bottom-border mb-0">
              <thead class="bg-light-primary">
                <tr>
                  <th width="36"></th>
                  <th>Employee</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Current Team</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($avail as $u):
                  $inTeam = !empty($u['in_this_team']);
                ?>
                <tr class="<?= $inTeam ? 'table-active' : '' ?>">
                  <td class="text-center">
                    <input type="checkbox" name="user_ids[]"
                           value="<?= (int)$u['id'] ?>"
                           <?= $inTeam ? 'disabled checked' : '' ?>
                           title="<?= $inTeam ? 'Already in this team' : 'Add to team' ?>">
                  </td>
                  <td class="fw-semibold"><?= html_escape($u['name']) ?></td>
                  <td class="text-muted"><?= html_escape($u['email']) ?></td>
                  <td><?= html_escape($u['role']) ?></td>
                  <td>
                    <?php if (!empty($u['current_team_id'])): ?>
                      <span class="badge bg-light-secondary text-muted">
                        <?= html_escape($u['current_team_name'] ?: 'Unknown') ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">Unassigned</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($inTeam): ?>
                      <span class="badge bg-success">Member</span>
                    <?php elseif (!empty($u['current_team_id'])): ?>
                      <span class="badge bg-warning text-dark">Other team</span>
                    <?php else: ?>
                      <span class="badge bg-light-primary">Unassigned</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                data-bs-dismiss="modal">Cancel</button>
        <?php if (!empty($avail)): ?>
          <button type="submit" class="btn btn-primary btn-sm px-4">
            <i class="fas fa-user-plus me-1"></i>Add Selected
          </button>
        <?php endif; ?>
      </div>

    </form>
  </div>
</div>
<?php endforeach; ?>