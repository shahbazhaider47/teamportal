<div class="card">
<div class="card-body">    
<?php
  $creatorImg = !empty($announcement['sender_profile_image'])
      ? base_url('uploads/users/profile/' . $announcement['sender_profile_image'])
      : base_url('assets/images/default-avatar.png');
?>
<div class="d-flex align-items-center mb-2">
  <img src="<?= $creatorImg ?>" alt="Creator" width="28" height="28" class="rounded-circle me-2"
       onerror="this.onerror=null;this.src='<?= base_url('assets/images/default-avatar.png') ?>';">
  <h5 class="mb-0 text-primary"><?= html_escape($announcement['title']) ?></h5>
</div>
<hr>
  <p class="text-muted small"><?= nl2br(html_escape($announcement['message'])) ?></p>

  <?php if (!empty($announcement['attachment'])): ?>
    <div class="mt-3">
      <a href="<?= base_url('uploads/announcements/' . $announcement['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-paperclip"></i> <?= $announcement['attachment'] ?>
      </a>
    </div>
  <?php endif; ?>
</div>
</div>
<div class="mt-3 small text-muted bg-light-primary text-center p-3 rounded">
  <strong>By:</strong>
  <?= html_escape($announcement['sender_name'] ?: 'Unknown') ?>
  <i class="ti ti-dots-vertical"></i> <strong>Priority:</strong> <?= ucfirst(html_escape($announcement['priority'])) ?>
    <?php
    $catName = $announcement['category_name'] ?? '';
    ?>    
  <i class="ti ti-dots-vertical"></i> <strong>Category:</strong> <?= html_escape($catName ?: 'N/A') ?>
  <i class="ti ti-dots-vertical"></i> <strong>Sent To:</strong> <?= ucfirst(html_escape($announcement['sent_to'])) ?>
  <i class="ti ti-dots-vertical"></i> <strong>Date:</strong> <?= date('M j, Y g:i a', strtotime($announcement['created_at'])) ?>
</div>