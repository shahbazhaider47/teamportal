<?php
$canView   = staff_can('view_global', 'announcements') || staff_can('view_own', 'announcements');

if ($canView):

?>

<div class="card">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold text-primary">
            <i class="ti ti-speakerphone me-1"></i>
            Latest Announcements
        </span>
        <a href="<?= site_url('announcements') ?>" class="btn btn-ssm text-primary bg-light-primary">View All</a>        
    </div>
    
  <div class="card-body">
    <?php if (!empty($announcements)): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($announcements as $a): ?>
          <li class="list-group-item px-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div class="flex-grow-1">
                <a href="javascript:void(0);" 
                   class="text-dark small btn-view-announcement"
                   data-id="<?= $a['id'] ?>"
                   data-title="<?= html_escape($a['title']) ?>"
                   data-message="<?= html_escape($a['message']) ?>"
                   data-category="<?= $a['category_name'] ?? 'General' ?>"
                   data-category-color="<?= $a['category_color'] ?? '#6c757d' ?>"
                   data-sender="<?= $a['sender_name'] ?? 'System' ?>"
                   data-priority="<?= ucfirst($a['priority']) ?>"
                   data-start="<?= $a['start_date'] ?? '' ?>"
                   data-end="<?= $a['end_date'] ?? '' ?>"
                   data-published="<?= $a['is_published'] ?>"
                   data-sent_to="<?= $a['sent_to'] ?>"
                   data-attachment="<?= !empty($a['attachment']) ? base_url('uploads/announcements/' . $a['attachment']) : '' ?>">
                   <?= character_limiter(html_escape($a['title']), 20) ?>
                </a>
                <div class="small text-muted mt-1">
                  <?= !empty($a['sender_name']) ? 'By ' . html_escape($a['sender_name']) : 'By System' ?> on
                  <?= date('M d, Y', strtotime($a['created_at'])) ?>
                </div>
              </div>
              <div class="text-end ms-3">
                <?php if (!empty($a['category_name'])): ?>
                  <span class="badge" style="background-color: <?= html_escape($a['category_color']) ?>;">
                    <?= html_escape($a['category_name']) ?>
                  </span>
                <?php endif; ?>
                <span class="badge text-light-primary">
                  <?= ucfirst($a['priority']) ?>
                </span>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted">No announcements found.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; // $canView ?>
<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('modals/view_announcements', [], true); ?>