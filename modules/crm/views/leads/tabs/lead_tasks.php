<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
/* ── Dummy task data ──────────────────────────────────────────────── */
$tasks = [
    [
        'id'          => 'TSK-0091',
        'title'       => 'Send follow-up email after proposal review',
        'description' => 'Reach out to confirm receipt of PRP-2026-0044 and address any questions on pricing or scope.',
        'type'        => 'follow_up',
        'priority'    => 'high',
        'status'      => 'open',
        'assigned_to' => 'Sarah Mitchell',
        'created_by'  => 'James Thornton',
        'due_at'      => date('Y-m-d', strtotime('+2 days')),
        'completed_at'=> null,
        'reminder_at' => date('Y-m-d', strtotime('+1 days')),
        'tags'        => ['Proposal', 'Email'],
    ],
    [
        'id'          => 'TSK-0088',
        'title'       => 'Schedule discovery call with clinical director',
        'description' => 'Coordinate with Dr. Patel\'s assistant to book a 30-min call to discuss the cognitive assessment suite.',
        'type'        => 'call',
        'priority'    => 'high',
        'status'      => 'in_progress',
        'assigned_to' => 'James Thornton',
        'created_by'  => 'Sarah Mitchell',
        'due_at'      => date('Y-m-d', strtotime('+1 days')),
        'completed_at'=> null,
        'reminder_at' => null,
        'tags'        => ['Call', 'Discovery'],
    ],
    [
        'id'          => 'TSK-0085',
        'title'       => 'Prepare revised pricing sheet for telehealth plan',
        'description' => 'Update the starter plan pricing based on the discounted session rates discussed on 28 Feb call.',
        'type'        => 'internal',
        'priority'    => 'medium',
        'status'      => 'in_progress',
        'assigned_to' => 'Sarah Mitchell',
        'created_by'  => 'Sarah Mitchell',
        'due_at'      => date('Y-m-d', strtotime('+5 days')),
        'completed_at'=> null,
        'reminder_at' => date('Y-m-d', strtotime('+3 days')),
        'tags'        => ['Pricing', 'Internal'],
    ],
    [
        'id'          => 'TSK-0079',
        'title'       => 'Confirm insurance coverage eligibility',
        'description' => 'Verify client\'s provider network coverage before finalising the wellness program agreement.',
        'type'        => 'admin',
        'priority'    => 'medium',
        'status'      => 'open',
        'assigned_to' => 'James Thornton',
        'created_by'  => 'James Thornton',
        'due_at'      => date('Y-m-d', strtotime('+8 days')),
        'completed_at'=> null,
        'reminder_at' => null,
        'tags'        => ['Admin', 'Insurance'],
    ],
    [
        'id'          => 'TSK-0072',
        'title'       => 'Send welcome packet and onboarding checklist',
        'description' => 'Dispatch the standard new-client welcome PDF along with the digital intake forms.',
        'type'        => 'email',
        'priority'    => 'low',
        'status'      => 'completed',
        'assigned_to' => 'Sarah Mitchell',
        'created_by'  => 'Sarah Mitchell',
        'due_at'      => '2026-02-20',
        'completed_at'=> '2026-02-19',
        'reminder_at' => null,
        'tags'        => ['Onboarding', 'Email'],
    ],
    [
        'id'          => 'TSK-0068',
        'title'       => 'Log outcome of initial consultation meeting',
        'description' => 'Record session notes, client feedback, and agreed next steps in the CRM timeline.',
        'type'        => 'admin',
        'priority'    => 'low',
        'status'      => 'completed',
        'assigned_to' => 'James Thornton',
        'created_by'  => 'James Thornton',
        'due_at'      => '2026-02-10',
        'completed_at'=> '2026-02-10',
        'reminder_at' => null,
        'tags'        => ['Admin', 'Notes'],
    ],
    [
        'id'          => 'TSK-0061',
        'title'       => 'Chase outstanding signature on group therapy contract',
        'description' => 'Client has not returned the signed agreement for the 12-week block. Third follow-up needed.',
        'type'        => 'follow_up',
        'priority'    => 'high',
        'status'      => 'overdue',
        'assigned_to' => 'Sarah Mitchell',
        'created_by'  => 'James Thornton',
        'due_at'      => date('Y-m-d', strtotime('-3 days')),
        'completed_at'=> null,
        'reminder_at' => null,
        'tags'        => ['Contract', 'Urgent'],
    ],
];

/* ── Config maps ──────────────────────────────────────────────────── */
$status_map = [
    'open'        => ['label' => 'Open',        'class' => 'tsk-badge-open',       'icon' => 'ti-circle'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'tsk-badge-inprogress', 'icon' => 'ti-progress'],
    'completed'   => ['label' => 'Completed',   'class' => 'tsk-badge-completed',  'icon' => 'ti-circle-check'],
    'overdue'     => ['label' => 'Overdue',     'class' => 'tsk-badge-overdue',    'icon' => 'ti-alert-circle'],
];

$priority_map = [
    'high'   => ['label' => 'High',   'class' => 'tsk-pri-high',   'dot' => '#dc2626'],
    'medium' => ['label' => 'Medium', 'class' => 'tsk-pri-medium', 'dot' => '#d97706'],
    'low'    => ['label' => 'Low',    'class' => 'tsk-pri-low',    'dot' => '#16a34a'],
];

$type_map = [
    'follow_up' => ['label' => 'Follow-up', 'icon' => 'ti-refresh',       'color' => '#6366f1'],
    'call'      => ['label' => 'Call',      'icon' => 'ti-phone',         'color' => '#0891b2'],
    'email'     => ['label' => 'Email',     'icon' => 'ti-mail',          'color' => '#056464'],
    'internal'  => ['label' => 'Internal',  'icon' => 'ti-building',      'color' => '#7c3aed'],
    'admin'     => ['label' => 'Admin',     'icon' => 'ti-clipboard-list','color' => '#475569'],
];

/* ── KPI calculations ─────────────────────────────────────────────── */
$total      = count($tasks);
$open       = count(array_filter($tasks, fn($t) => $t['status'] === 'open'));
$in_prog    = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
$completed  = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
$overdue    = count(array_filter($tasks, fn($t) => $t['status'] === 'overdue'));

/* ── Helpers ──────────────────────────────────────────────────────── */
function tsk_fmt_date(?string $d): string {
    if (!$d) return '<span class="text-muted">—</span>';
    return date('M j, Y', strtotime($d));
}
function tsk_due_label(?string $d, string $status): string {
    if (!$d) return '<span class="text-muted">—</span>';
    if ($status === 'completed') {
        return '<span style="color:#16a34a;font-weight:600;">' . date('M j, Y', strtotime($d)) . '</span>';
    }
    $today = new DateTime();
    $due   = new DateTime($d);
    $diff  = (int) $today->diff($due)->days;
    $past  = $due < $today;
    if ($past) {
        return '<span style="color:#dc2626;font-weight:600;">' . date('M j, Y', strtotime($d)) . '<br><small>' . $diff . 'd overdue</small></span>';
    } elseif ($diff === 0) {
        return '<span style="color:#d97706;font-weight:700;">Today</span>';
    } elseif ($diff <= 2) {
        return '<span style="color:#d97706;font-weight:600;">' . date('M j, Y', strtotime($d)) . '<br><small>In ' . $diff . 'd</small></span>';
    } else {
        return '<span style="color:#334155;">' . date('M j, Y', strtotime($d)) . '</span>';
    }
}
function tsk_initials(string $name): string {
    return implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $name)));
}
?>

<!-- ── Scoped styles ──────────────────────────────────────────────── -->
<style>
/* KPI strip */
.tasks-kpi-strip {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin-bottom: 18px;
}
.tasks-kpi {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 11px 14px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.tasks-kpi-label {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.tasks-kpi-value {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.5px;
    color: #0f172a;
}

/* status badges */
.tsk-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    border: 1px solid transparent;
}
.tsk-badge-open        { background: #f8fafc; border-color: #dbe5ef; color: #475569; }
.tsk-badge-inprogress  { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.tsk-badge-completed   { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.tsk-badge-overdue     { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

/* priority badges */
.tsk-pri {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
}
.tsk-pri-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tsk-pri-high   .tsk-pri-dot { background: #dc2626; }
.tsk-pri-medium .tsk-pri-dot { background: #d97706; }
.tsk-pri-low    .tsk-pri-dot { background: #16a34a; }
.tsk-pri-high   { color: #dc2626; }
.tsk-pri-medium { color: #d97706; }
.tsk-pri-low    { color: #16a34a; }

/* type chip */
.tsk-type-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    white-space: nowrap;
}
.tsk-type-chip i { font-size: 12px; }

/* tag pill */
.tsk-tag {
    display: inline-block;
    background: #f1f5f9;
    color: #64748b;
    border-radius: 5px;
    padding: 1px 7px;
    font-size: 10.5px;
    font-weight: 600;
    margin: 1px 2px 1px 0;
    white-space: nowrap;
}

/* table */
.tasks-table th {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 14px;
    white-space: nowrap;
}
.tasks-table td {
    padding: 11px 14px;
    font-size: 13px;
    color: #334155;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.tasks-table tbody tr:last-child td { border-bottom: none; }
.tasks-table tbody tr:hover { background: #fafbff; }
.tasks-table tbody tr.is-completed td { opacity: 0.55; }
.tasks-table tbody tr.is-overdue { background: #fff8f8; }
.tasks-table tbody tr.is-overdue:hover { background: #fff1f1; }

/* task ID */
.tsk-id {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    font-family: 'Courier New', monospace;
}

/* task title */
.tsk-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.4;
}
.tsk-title.is-done {
    text-decoration: line-through;
    color: #94a3b8;
}
.tsk-desc {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
    line-height: 1.4;
}

/* assignee */
.tsk-assignee {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.tsk-avatar {
    width: 24px; height: 24px;
    border-radius: 6px;
    background: #056464;
    color: #fff;
    font-size: 9px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tsk-avatar-alt { background: #6366f1; }

/* reminder chip */
.tsk-reminder {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #7c3aed;
    background: #f5f3ff;
    border: 1px solid #ede9fe;
    border-radius: 6px;
    padding: 2px 7px;
    margin-top: 3px;
    white-space: nowrap;
}

/* action btns */
.tsk-actions {
    display: flex;
    gap: 4px;
    align-items: center;
}
.tsk-actions .btn {
    padding: 3px 9px;
    font-size: 11px;
    border-radius: 6px;
    white-space: nowrap;
}

/* section separator */
.tsk-section-row td {
    background: #f1f5f9 !important;
    padding: 6px 14px !important;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0 !important;
}

/* progress bar */
.tasks-progress-wrap {
    height: 5px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
    margin-top: 10px;
}
.tasks-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #5ebfbf, #056464);
    transition: width 0.6s ease;
}
.tasks-progress-label {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
    display: flex;
    justify-content: space-between;
}

@media (max-width: 767px) {
    .tasks-kpi-strip { grid-template-columns: repeat(3, 1fr); }
}
</style>

<!-- ── Card shell ──────────────────────────────────────────────────── -->


    <!-- header -->
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="fw-semibold">
            <i class="ti ti-checkbox me-2 text-primary"></i>Lead Tasks
            <span class="badge bg-light-primary">
                <?= $total ?> Tasks
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                <i class="ti ti-filter me-1"></i>Filter
            </button>
            <button type="button" class="btn btn-sm btn-primary" disabled>
                <i class="ti ti-plus me-1"></i>Add Task
            </button>
        </div>
    </div>

    <div class="card-body p-3">

        <!-- KPI strip -->
        <div class="tasks-kpi-strip">
            <div class="tasks-kpi">
                <div class="tasks-kpi-label">Total Tasks</div>
                <div class="tasks-kpi-value"><?= $total ?></div>
            </div>
            <div class="tasks-kpi">
                <div class="tasks-kpi-label">Open</div>
                <div class="tasks-kpi-value" style="color:#475569;"><?= $open ?></div>
            </div>
            <div class="tasks-kpi">
                <div class="tasks-kpi-label">In Progress</div>
                <div class="tasks-kpi-value" style="color:#1d4ed8;"><?= $in_prog ?></div>
            </div>
            <div class="tasks-kpi">
                <div class="tasks-kpi-label">Completed</div>
                <div class="tasks-kpi-value" style="color:#16a34a;"><?= $completed ?></div>
            </div>
            <div class="tasks-kpi">
                <div class="tasks-kpi-label">Overdue</div>
                <div class="tasks-kpi-value" style="color:#dc2626;"><?= $overdue ?></div>
            </div>
        </div>

        <!-- completion progress -->
        <?php $pct = $total > 0 ? round(($completed / $total) * 100) : 0; ?>
        <div class="tasks-progress-wrap">
            <div class="tasks-progress-fill" style="width:<?= $pct ?>%;"></div>
        </div>
        <div class="tasks-progress-label">
            <span><?= $pct ?>% of tasks completed</span>
            <span><?= $completed ?> / <?= $total ?></span>
        </div>

        <!-- table -->
        <div class="table-responsive mt-3" style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
            <table class="table mb-0 tasks-table">
                <thead>
                    <tr>
                        <th style="width:90px;">Task ID</th>
                        <th>Title &amp; Description</th>
                        <th style="width:100px;">Type</th>
                        <th style="width:80px;">Priority</th>
                        <th style="width:105px;">Status</th>
                        <th style="width:130px;">Assigned To</th>
                        <th style="width:120px;">Due Date</th>
                        <th style="width:110px;">Completed</th>
                        <th style="width:115px;">Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                    /* ── Group tasks: active first, completed last ── */
                    $active_tasks    = array_filter($tasks, fn($t) => $t['status'] !== 'completed');
                    $completed_tasks = array_filter($tasks, fn($t) => $t['status'] === 'completed');

                    /* sort active: overdue → high → medium → low */
                    $pri_order = ['overdue' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
                    usort($active_tasks, function($a, $b) use ($pri_order) {
                        $a_key = $a['status'] === 'overdue' ? 'overdue' : $a['priority'];
                        $b_key = $b['status'] === 'overdue' ? 'overdue' : $b['priority'];
                        return ($pri_order[$a_key] ?? 9) <=> ($pri_order[$b_key] ?? 9);
                    });

                    $render_row = function(array $t) use ($status_map, $priority_map, $type_map): void {
                        $st  = $status_map[$t['status']];
                        $pri = $priority_map[$t['priority']];
                        $typ = $type_map[$t['type']];
                        $is_done    = $t['status'] === 'completed';
                        $is_overdue = $t['status'] === 'overdue';
                        $row_class  = $is_done ? 'is-completed' : ($is_overdue ? 'is-overdue' : '');
                        $ini_a      = tsk_initials($t['assigned_to']);
                        $ini_c      = tsk_initials($t['created_by']);
                        $same_owner = $t['assigned_to'] === $t['created_by'];
                        ?>
                        <tr class="<?= $row_class ?>">

                            <!-- ID -->
                            <td><span class="tsk-id"><?= htmlspecialchars($t['id']) ?></span></td>

                            <!-- Title + desc + tags + reminder -->
                            <td>
                                <div class="tsk-title <?= $is_done ? 'is-done' : '' ?>">
                                    <?php if ($is_overdue): ?>
                                        <i class="ti ti-alert-circle text-danger me-1" style="font-size:12px;"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($t['title']) ?>
                                </div>
                                <div class="tsk-desc"><?= htmlspecialchars($t['description']) ?></div>
                                <div class="mt-1">
                                    <?php foreach ($t['tags'] as $tag): ?>
                                        <span class="tsk-tag"><?= htmlspecialchars($tag) ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($t['reminder_at'] && !$is_done): ?>
                                        <span class="tsk-reminder">
                                            <i class="ti ti-bell" style="font-size:11px;"></i>
                                            Reminder <?= date('M j', strtotime($t['reminder_at'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Type -->
                            <td>
                                <span class="tsk-type-chip">
                                    <i class="ti <?= $typ['icon'] ?>" style="color:<?= $typ['color'] ?>;"></i>
                                    <?= $typ['label'] ?>
                                </span>
                            </td>

                            <!-- Priority -->
                            <td>
                                <span class="tsk-pri <?= $pri['class'] ?>">
                                    <span class="tsk-pri-dot"></span>
                                    <?= $pri['label'] ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td>
                                <span class="tsk-badge <?= $st['class'] ?>">
                                    <i class="ti <?= $st['icon'] ?>"></i>
                                    <?= $st['label'] ?>
                                </span>
                            </td>

                            <!-- Assigned to -->
                            <td>
                                <div class="tsk-assignee">
                                    <div class="tsk-avatar <?= $same_owner ? '' : 'tsk-avatar-alt' ?>"><?= $ini_a ?></div>
                                    <span style="font-size:12px;"><?= htmlspecialchars($t['assigned_to']) ?></span>
                                </div>
                                <?php if (!$same_owner): ?>
                                    <div style="font-size:10.5px;color:#94a3b8;margin-top:2px;">
                                        by <?= htmlspecialchars($t['created_by']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Due date -->
                            <td style="font-size:12px;"><?= tsk_due_label($t['due_at'], $t['status']) ?></td>

                            <!-- Completed -->
                            <td style="font-size:12px;"><?= tsk_fmt_date($t['completed_at']) ?></td>

                            <!-- Actions -->
                            <td>
                                <div class="tsk-actions">
                                    <?php if (!$is_done): ?>
                                        <button class="btn btn-success btn-sm" title="Mark Complete" disabled>
                                            <i class="ti ti-check"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" title="Edit" disabled>
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" title="Delete" disabled>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm" title="Reopen" disabled>
                                            <i class="ti ti-refresh"></i> Reopen
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                        <?php
                    };

                    /* ── Active tasks section ── */
                    if (!empty($active_tasks)): ?>
                        <tr class="tsk-section-row">
                            <td colspan="9">
                                <i class="ti ti-clock me-1"></i>Active &amp; Pending — <?= count($active_tasks) ?> task<?= count($active_tasks) !== 1 ? 's' : '' ?>
                            </td>
                        </tr>
                        <?php foreach ($active_tasks as $t): $render_row($t); endforeach; ?>
                    <?php endif; ?>

                    <?php /* ── Completed tasks section ── */
                    if (!empty($completed_tasks)): ?>
                        <tr class="tsk-section-row">
                            <td colspan="9">
                                <i class="ti ti-circle-check me-1"></i>Completed — <?= count($completed_tasks) ?> task<?= count($completed_tasks) !== 1 ? 's' : '' ?>
                            </td>
                        </tr>
                        <?php foreach ($completed_tasks as $t): $render_row($t); endforeach; ?>
                    <?php endif; ?>

                </tbody>

                <!-- footer -->
                <tfoot>
                    <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                        <td colspan="4" style="padding:10px 14px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">
                            <?= $total ?> tasks total
                        </td>
                        <td colspan="5" style="padding:10px 14px;font-size:12px;color:#94a3b8;">
                            <?= $open ?> open &middot; <?= $in_prog ?> in progress &middot; <?= $completed ?> completed
                            <?php if ($overdue > 0): ?>
                                &middot; <span style="color:#dc2626;font-weight:600;"><?= $overdue ?> overdue</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- end table -->

    </div>
    <!-- end card-body -->

