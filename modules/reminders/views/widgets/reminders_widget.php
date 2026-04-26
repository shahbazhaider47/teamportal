<?php
$CI = &get_instance();
$CI->load->model('reminders/Reminders_model');
$user_id = (int)$CI->session->userdata('user_id');
$reminders = $CI->Reminders_model->get_dashboard_reminders($user_id, 5);
?>
<div class="card dashboard-widget shadow-sm rounded mb-4" style="min-height: 220px;">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold text-primary">
            <i class="ti ti-bell me-1"></i>
            My Reminders
        </span>
<a href="<?= site_url('reminders') ?>" class="btn btn-ssm text-primary bg-light-primary">View All</a>        
    </div>
    <div class="card-body" style="font-size: 14px;">
        <?php if (is_array($reminders) && count($reminders)): ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($reminders as $r): ?>
                    <li class="mb-1 d-flex align-items-start">
                        <span class="icon-btn badge bg-<?= $r['is_completed'] ? 'primary' : 'warning' ?> me-2 mt-1" style="width:25px; height:25px;">
                            <?= $r['is_completed'] ? '<i class="ti ti-check"></i>' : '<i class="ti ti-bell"></i>' ?>
                        </span>
                        <div>
                            <div class="small mb-1"><?= html_escape($r['title']) ?></div>
                            <div class="small text-muted">
                                <span class="badge text-light-secondary"><?= date('M d, Y H:i', strtotime($r['date'])) ?></span>
                                <span class="badge text-light-primary capital"><?= html_escape($r['priority']) ?></span>
                                <span class="badge text-light-info capital"><?= html_escape($r['recurring_frequency']) ?></span>                                
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-muted small text-center pt-3">
                <i class="ti ti-bell-off fs-3 d-block mb-2"></i>
                No reminders found.<br>
                <?php
                  $canCreate    = staff_can('create', 'reminders');
                ?>                
                <button type="button"
                        id="btn-add-reminder"
                        class="mt-3 btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                        <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#add_reminder_modal"' : 'disabled' ?>
                        title="Add New Reminder">
                  <i class="fa fa-plus me-1"></i> Add New
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('reminders/modals/add_reminder', [], true); ?>
<?php echo $CI->load->view('reminders/modals/edit_reminder', [], true); ?>
<?php echo $CI->load->view('reminders/modals/view', [], true); ?>