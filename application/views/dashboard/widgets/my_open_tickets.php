<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * My Open Tickets — Improved Compact Dashboard Widget
 *
 * This version is redesigned for a professional, space-efficient view.
 * Key changes:
 * - Total count is now a badge in the header.
 * - Priority is shown as a colored dot, removing the second badge from each item.
 * - The progress bar's text legend is removed to save space (tooltips remain).
 * - Redundant footer is removed.
 * - Metadata line is cleaner with an icon and better date formatting.
 */

$CI =& get_instance();
$CI->load->model('Support_tickets_model', 'tickets');

// --- Permission Check ---
if (!function_exists('staff_can') || (!staff_can('view_global','support') && !staff_can('view_own','support'))) {
    echo '<div class="card"><div class="card-body small text-muted">No permission.</div></div>';
    return;
}

// --- Data Fetching ---
$uid          = (int)$CI->session->userdata('user_id');
$openStatuses = ['open', 'in_progress', 'waiting_user', 'on_hold'];
$baseUrl      = base_url('support/');

$assigned  = $CI->tickets->list_tickets(['assignee_id' => $uid], 50, 0, ['last_activity_at' => 'DESC']);
$requested = $CI->tickets->list_tickets(['requester_id' => $uid], 50, 0, ['last_activity_at' => 'DESC']);

$byId = [];
foreach (array_merge($assigned, $requested) as $t) {
    if (!in_array(($t['status'] ?? ''), $openStatuses, true)) continue;
    $byId[(int)$t['id']] = $t;
}
$allOpen   = array_values($byId);
$totalOpen = count($allOpen);
$items     = array_slice($allOpen, 0, 5); // Show top 5

// --- Visual Mappings & Calculations ---
$statusColor = [
    'open'         => 'primary',
    'in_progress'  => 'info',
    'waiting_user' => 'warning',
    'on_hold'      => 'secondary',
];
$priorityColor = [
    'low'    => 'secondary',
    'normal' => 'primary',
    'high'   => 'warning',
    'urgent' => 'danger',
];

$byStatus = array_fill_keys(array_keys($statusColor), 0);
foreach ($allOpen as $t) {
    $s = strtolower((string)($t['status'] ?? ''));
    if (isset($byStatus[$s])) $byStatus[$s]++;
}
$totalForBar = max(1, $totalOpen);

?>


<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="main-title mb-0">
          <i class="ti ti-lifebuoy text-primary me-2"></i> Support Tickets
            <?php if ($totalOpen > 0): ?>
                <span class="small badge bg-light-primary rounded-pill ms-1"><?= $totalOpen ?></span>
            <?php endif; ?>          
        </h6>            

        <a href="<?= $baseUrl; ?>" class="btn btn-ssm bg-light-primary">View all</a>
    </div>

    <div class="card-body">

        <?php if (empty($items)): ?>
            <div class="text-center text-muted p-4">
                <i class="ti ti-circle-check" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">No open tickets. Great job!</p>
            </div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($items as $t):
                    $sid   = (int)$t['id'];
                    $sub   = (string)($t['subject'] ?? 'No Subject');
                    $prio  = strtolower((string)($t['priority'] ?? 'normal'));
                    $stat  = strtolower((string)($t['status']   ?? 'open'));
                    $last  = (string)($t['last_activity_at'] ?? '');
                    $code  = (string)($t['code'] ?? ('#'.$sid));
                    $resDue = (string)($t['resolution_due_at'] ?? '');
                ?>
                <li class="list-group-item d-flex align-items-center justify-content-between gap-2 small">
                    <div class="flex-grow-1 text-truncate">
                        <a class="text-decoration-none fw-semibold" href="<?= $baseUrl . 'view/' . $sid; ?>" title="<?= html_escape($sub) ?>">
                            <?= html_escape($sub) ?>
                        </a>
                        <div class="small text-muted d-flex align-items-center mt-1 gap-2">
                            <span title="Last Activity">
                                <i class="ti ti-refresh" style="font-size: 0.9em;"></i>
                                <?= !empty($last) ? date('M j, g:i A', strtotime($last)) : 'N/A' ?>
                            </span>
                            <?php if (!empty($resDue)): ?>
                                <span class="text-nowrap text-danger" title="Resolution due">
                                    <i class="ti ti-clock" style="font-size: 0.9em;"></i>
                                    <?= date('M j, g:i A', strtotime($resDue)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-nowrap">
                        <span class="capital badge text-bg-<?= $statusColor[$stat] ?? 'secondary' ?>">
                            <?= str_replace('_',' ', $stat) ?>
                        </span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>