<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$canCreate = staff_can('client_create', 'crm');
$canEdit   = staff_can('client_edit', 'crm');
$canView   = staff_can('client_view', 'crm') || staff_can('view', 'crm');
$table_id  = $table_id ?? 'crmGroupsTable';

/*
|--------------------------------------------------------------------------
| Group KPI Stats
|--------------------------------------------------------------------------
| Expected from controller as:
| $group_kpi = [
|   'total_groups'      => 0,
|   'active_groups'     => 0,
|   'inactive_groups'   => 0,
|   'hold_groups'       => 0,
|   'terminated_groups' => 0,
|   'total_clients'     => 0,
| ];
|
| Add from controller like:
| 'group_kpi' => $this->crmclients->get_groups_kpi()
|--------------------------------------------------------------------------
*/
$group_kpi = $group_kpi ?? [
    'total_groups'      => 0,
    'active_groups'     => 0,
    'inactive_groups'   => 0,
    'hold_groups'       => 0,
    'terminated_groups' => 0,
    'total_clients'     => 0,
];
?>

<div class="container-fluid">

    <div class="crm-page-header mb-3">
        <div class="crm-page-icon me-3">
            <i class="fa-solid fa-users fa-fw"></i>
        </div>

        <div class="flex-grow-1">
            <div class="crm-page-title"><?= html_escape($page_title ?? 'Client Groups') ?></div>
            <div class="crm-page-sub">Manage all client groups for the company</div>
        </div>

        <div class="ms-auto d-flex gap-2">
            <?php if ($canCreate): ?>
            <button type="button" class="btn-add-new" data-bs-toggle="modal" 
                    data-bs-target="#clientGroupCreateModal">
                    New Group
            </button>
            <?php endif; ?>

            <div class="btn-divider mt-1"></div>

            <?php render_export_buttons([
                'filename' => $page_title ?? 'groups_export'
            ]); ?>
        </div>
    </div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">
                <?php if (function_exists('app_table_filter')): ?>
                    <?php app_table_filter($table_id, [
                        'exclude_columns' => ['Contact Email', 'Contact Phone', 'Contract Date', 'Total Clients'],
                    ]); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <div class="crm-card">
    <div class="row g-2 mb-3">

        <div class="col">
            <div class="crm-kpi-card">
                <div class="crm-kpi-icon" style="background:#dbeafe;">
                    <i class="ti ti-users"></i>
                </div>
                <div>
                    <div class="crm-kpi-value"><?= (int)($group_kpi['total_groups'] ?? 0); ?></div>
                    <div class="crm-kpi-label">Total Groups</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="crm-kpi-card">
                <div class="crm-kpi-icon" style="background:#16a34a18;">
                    <i class="ti ti-circle-check"></i>
                </div>
                <div>
                    <div class="crm-kpi-value"><?= (int)($group_kpi['active_groups'] ?? 0); ?></div>
                    <div class="crm-kpi-label">Active Groups</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="crm-kpi-card">
                <div class="crm-kpi-icon" style="background:#f59e0b18;">
                    <i class="ti ti-user-off"></i>
                </div>
                <div>
                    <div class="crm-kpi-value"><?= (int)($group_kpi['inactive_groups'] ?? 0); ?></div>
                    <div class="crm-kpi-label">Inactive Groups</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="crm-kpi-card">
                <div class="crm-kpi-icon" style="background:#fde68a4d;">
                    <i class="ti ti-player-pause"></i>
                </div>
                <div>
                    <div class="crm-kpi-value"><?= (int)($group_kpi['hold_groups'] ?? 0); ?></div>
                    <div class="crm-kpi-label">On Hold</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="crm-kpi-card">
                <div class="crm-kpi-icon" style="background:#ef444418;">
                    <i class="ti ti-user-x"></i>
                </div>
                <div>
                    <div class="crm-kpi-value"><?= (int)($group_kpi['terminated_groups'] ?? 0); ?></div>
                    <div class="crm-kpi-label">Terminated</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="crm-kpi-card">
                <div class="crm-kpi-icon" style="background:#6366f118;">
                    <i class="ti ti-building-community"></i>
                </div>
                <div>
                    <div class="crm-kpi-value"><?= (int)($group_kpi['total_clients'] ?? 0); ?></div>
                    <div class="crm-kpi-label">Total Clients</div>
                </div>
            </div>
        </div>

    </div>
    
    <div class="app-divider-v dashed mb-3"></div>
        <div class="table-responsive crm-table">
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
                <thead class="bg-light-primary">
                    <tr>
                        <th>Group Name</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Contact Info</th>
                        <th>Contract Date</th>
                        <th>Group Status</th>
                        <th>Total Clients</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($groups)): ?>
                        <?php foreach ($groups as $g): ?>
                            <?php
                            $statusRaw = trim((string)($g['status'] ?? 'inactive'));
                            $status    = strtolower($statusRaw);

                            $isActive     = $status === 'active';
                            $isInactive   = $status === 'inactive';
                            $isOnHold     = in_array($status, ['hold', 'on-hold', 'on hold'], true);
                            $isTerminated = $status === 'terminated';
                            $isArchived   = $status === 'archived';

                            $groupName     = trim((string)($g['group_name'] ?? ''));
                            $companyName   = trim((string)($g['company_name'] ?? ''));
                            $contactPerson = trim((string)($g['contact_person'] ?? ''));
                            $contactEmail  = trim((string)($g['contact_email'] ?? ''));
                            $contactPhone  = trim((string)($g['contact_phone'] ?? ''));
                            $contractDate  = trim((string)($g['contract_date'] ?? ''));
                            $totalClients  = (int)($g['clients_total'] ?? 0);
                            ?>
                            <tr>

                                <td class="small">
                                    <div class="fw-semibold mb-1">
                                        <?php if ($canView): ?>
                                            <a href="<?= site_url('crm/group_view/' . (int)$g['id']); ?>"
                                               class="text-primary"
                                               target="_blank"
                                               rel="noopener"
                                               title="Open Group Profile">
                                                <?= html_escape($groupName !== '' ? $groupName : '—'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-dark">
                                                <?= html_escape($groupName !== '' ? $groupName : '—'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="x-small text-muted">
                                        <i class="ti ti-users-group text-info me-1"></i>
                                        Group Record
                                    </div>
                                </td>

                                <td class="small">
                                    <div class="fw-semibold text-dark">
                                        <?= html_escape($companyName !== '' ? $companyName : '—'); ?>
                                    </div>
                                    <div class="x-small text-muted">
                                        <i class="ti ti-building text-primary me-1"></i>
                                        Company Name
                                    </div>
                                </td>

                                <td class="small">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="crm-user-avatar">
                                            <i class="ti ti-user"></i>
                                        </div>

                                        <div>
                                            <div>
                                                <?= html_escape($contactPerson !== '' ? $contactPerson : '—'); ?>
                                            </div>
                                            <span class="x-small text-muted">
                                                Primary Contact
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="small">
                                    <?php if ($contactEmail !== '' || $contactPhone !== ''): ?>
                                        
                                        <div class="d-flex flex-column gap-1 text-muted">
                                            
                                            <?php if ($contactEmail !== ''): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="ti ti-mail text-primary" style="font-size:14px;"></i>
                                                    <span><?= html_escape($contactEmail); ?></span>
                                                </div>
                                            <?php endif; ?>
                                
                                            <?php if ($contactPhone !== ''): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="ti ti-phone text-primary" style="font-size:14px;"></i>
                                                    <span><?= html_escape($contactPhone); ?></span>
                                                </div>
                                            <?php endif; ?>
                                
                                        </div>
                                
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="small">
                                    <?php if ($contractDate !== ''): ?>
                                        <div class="d-flex align-items-center gap-1 text-muted">
                                            <i class="ti ti-calendar-event text-success" style="font-size:13px;"></i>
                                            <span><?= html_escape($contractDate); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php elseif ($isInactive): ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php elseif ($isOnHold): ?>
                                        <span class="badge badge-hold">On Hold</span>
                                    <?php elseif ($isTerminated): ?>
                                        <span class="badge badge-terminated">Terminated</span>
                                    <?php elseif ($isArchived): ?>
                                        <span class="badge badge-archived">Archived</span>
                                    <?php else: ?>
                                        <span class="pill pill-na"><?= html_escape(ucwords(str_replace('-', ' ', $statusRaw ?: 'unknown'))); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="small">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-users text-primary" style="font-size:15px;"></i>
                                        <div>
                                            <div class="fw-semibold text-dark"><?= $totalClients; ?></div>
                                            <span class="x-small text-muted">Linked Clients</span>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No groups found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>


<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('groups/modals/group_add', [], true); ?>


<script>
document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-modal-url]');
    if(!btn) return;

    const url = btn.getAttribute('data-modal-url');
    const body = document.getElementById('crmGroupModalBody');
    if(!url || !body) return;

    body.innerHTML = '<div class="p-4 text-muted">Loading...</div>';

    fetch(url, { credentials: 'same-origin' })
        .then(r => r.text())
        .then(html => body.innerHTML = html)
        .catch(() => body.innerHTML = '<div class="p-4 text-danger">Failed to load modal.</div>');
});
</script>