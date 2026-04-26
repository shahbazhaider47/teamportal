<?php 
$CI = &get_instance();
$CI->load->model('User_model');
$user_id = (int)$CI->session->userdata('user_id');

// Get team overview with error handling
try {
    $team_info = $CI->User_model->get_team_overview($user_id);
} catch (Exception $e) {
    log_message('error', 'Team widget error: ' . $e->getMessage());
    $team_info = null;
}

if (!empty($team_info)): 
    // Calculate progress percentage (example metric)
    $progress_percentage = min(100, max(0, $team_info['progress'] ?? 0));
    $progress_color = $progress_percentage >= 75 ? 'success' : ($progress_percentage >= 50 ? 'primary' : 'warning');
?>

<div class="card team-widget">
    <div class="card-body">
        <!-- Header with team info and action button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <div class="bg-light-primary p-2 rounded-2 me-2">
                    <i class="ti ti-users text-primary fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-semibold text-dark small">My Team <span class="badge text-light-primary small"><?= $team_info['member_count'] ?? 0 ?> Members</span></h6>
                    <small class="text-muted"><?= html_escape($team_info['team_name'] ?? 'Unnamed Team') ?></small>
                </div>
            </div>
        </div>

        <!-- Team stats -->
            <div class="col-md-12">
                <div class="p-2 px-3 bg-light-primary rounded-2 mt-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small">Reporting To</span>
                            <h5 class="mb-0 mt-1 fw-semibold small"><?= html_escape($team_info['team_lead'] ?? 'None') ?></h5>
                        </div>
                        <i class="ti ti-user-check text-success fs-4"></i>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php else: ?>
<!-- Empty state with refresh action -->
<div class="card">
    <div class="card-body text-center py-4">
        <i class="ti ti-users-off text-muted fs-2 mb-4"></i>
        <h6 class="fw-semibold">No Team Information</h6>
        <p class="text-muted small mb-3">No team assigned yet.</p>
        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
            <i class="ti ti-refresh me-1"></i> Refresh
        </button>
    </div>
</div>
<?php endif; ?>