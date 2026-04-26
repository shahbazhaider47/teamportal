<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php foreach ($teams as $row):
  $tid = (int)$row['id'];
?>
<div class="modal fade" id="deleteTeamModal<?= $tid ?>" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static">
  <div class="modal-dialog modal-sm modal-dialog-top">
    <form method="post" action="<?= site_url('teams') ?>" class="modal-content">
      <input type="hidden" name="delete_team" value="<?= $tid ?>">

      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white">
          <i class="fas fa-trash me-2"></i>Delete Team
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body small">
        <p class="mb-1">Are you sure you want to delete:</p>
        <p class="fw-semibold text-danger mb-0">"<?= html_escape($row['name']) ?>"</p>
        <?php if ((int)($row['member_count'] ?? 0) > 0): ?>
          <div class="alert alert-warning mt-3 mb-0 py-2 small">
            <i class="fas fa-exclamation-triangle me-1"></i>
            This team has <strong><?= (int)$row['member_count'] ?> active member<?= (int)$row['member_count'] !== 1 ? 's' : '' ?></strong>
            and cannot be deleted until they are reassigned.
          </div>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-ssm"
                data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger btn-ssm"
                <?= ((int)($row['member_count'] ?? 0) > 0) ? 'disabled title="Remove all members first"' : '' ?>>
          Delete
        </button>
      </div>

    </form>
  </div>
</div>
<?php endforeach; ?>