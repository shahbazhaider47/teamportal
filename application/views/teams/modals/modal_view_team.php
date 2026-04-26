<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php foreach ($teams as $row):
  $tid     = (int)$row['id'];
  $members = $team_members_by_team[$tid] ?? [];
?>
<div class="modal fade" id="viewTeamModal<?= $tid ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <div class="modal-content">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="fas fa-users me-2"></i><?= html_escape($row['name']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">

        <!-- Meta strip -->
        <div class="d-flex flex-wrap gap-3 p-3 bg-light-secondary border-bottom small">
          <div>
            <span class="text-muted">Department:</span>
            <strong class="ms-1"><?= html_escape($row['department_name'] ?? '—') ?></strong>
          </div>
          <div>
            <span class="text-muted">Team Lead:</span>
            <strong class="ms-1"><?= html_escape($row['lead_name'] ?? '—') ?></strong>
          </div>
          <div>
            <span class="text-muted">Manager:</span>
            <strong class="ms-1"><?= html_escape($row['manager_name'] ?? '—') ?></strong>
          </div>
          <div>
            <span class="text-muted">Members:</span>
            <strong class="ms-1"><?= (int)($row['member_count'] ?? 0) ?></strong>
          </div>
        </div>

        <!-- Members table -->
        <?php if (empty($members)): ?>
          <div class="text-center text-muted py-4 small">
            <i class="fas fa-users fa-lg mb-2 d-block text-secondary"></i>
            No active members in this team yet.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm small table-bottom-border align-middle mb-0">
              <thead class="bg-light-primary">
                <tr>
                  <th>Member</th>
                  <th>Email</th>
                  <th>Role</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m):
                  $initials = strtoupper(substr($m['name'], 0, 1));
                  $hasAvatar = !empty($m['avatar']) && strpos($m['avatar'], 'default.png') === false;
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if ($hasAvatar): ?>
                        <img src="<?= html_escape($m['avatar']) ?>"
                             class="rounded-circle flex-shrink-0"
                             width="26" height="26" loading="lazy"
                             onerror="this.onerror=null;this.src='<?= base_url('assets/images/default-avatar.png') ?>'">
                      <?php else: ?>
                        <span class="rounded-circle bg-light-primary d-inline-flex align-items-center
                                     justify-content-center fw-semibold flex-shrink-0"
                              style="width:26px;height:26px;font-size:10px">
                          <?= html_escape($initials ?: 'U') ?>
                        </span>
                      <?php endif; ?>
                      <span class="fw-semibold"><?= html_escape($m['name']) ?></span>
                    </div>
                  </td>
                  <td class="text-muted"><?= html_escape($m['email'] ?: '—') ?></td>
                  <td>
                    <?php
                      $rl = strtolower($m['role'] ?? '');
                      $bc = in_array($rl, ['admin','manager','teamlead','team lead'])
                          ? 'bg-light-primary' : 'bg-light-secondary text-muted';
                    ?>
                    <span class="badge <?= $bc ?>"><?= html_escape(ucwords($m['role'])) ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<?php endforeach; ?>