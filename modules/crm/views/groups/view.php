<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>

<?php
$groupId   = (int)($group['id'] ?? 0);
$status    = strtolower(trim((string)($group['status'] ?? 'inactive')));
$isActive  = ($status === 'active');
$isInactive = ($status === 'inactive');
$isSuspended = ($status === 'suspended');
$isChurned   = ($status === 'churned');

$canEdit        = staff_can('client_edit', 'crm');
$canDelete      = staff_can('client_delete', 'crm');
$canViewClients = staff_can('client_view', 'crm') || staff_can('view', 'crm');

$totalClients    = is_array($clients) ? count($clients) : 0;
$activeClients   = 0;
$inactiveClients = 0;

if (!empty($clients)) {
    foreach ($clients as $c) {
        if ((int)($c['is_active'] ?? 0) === 1) {
            $activeClients++;
        } else {
            $inactiveClients++;
        }
    }
}

$val = function ($v, $fallback = '—') {
    $v = is_string($v) ? trim($v) : $v;
    return ($v !== '' && $v !== null) ? $v : $fallback;
};

$groupName   = $val($group['group_name'] ?? null);
$companyName = $val($group['company_name'] ?? null);

$words    = preg_split('/\s+/', $companyName);
$initials = strtoupper(substr($words[0] ?? 'G', 0, 1));
if (isset($words[1])) {
    $initials .= strtoupper(substr($words[1], 0, 1));
}

// Address builder
$addressParts = array_filter([
    $group['address'] ?? '',
    $group['city'] ?? '',
    $group['state'] ?? '',
    $group['zip_code'] ?? '',
    $group['country'] ?? '',
]);
$fullAddress = implode(', ', $addressParts);
?>

<div class="container-fluid">

    <!-- ── Page Header ────────────────────────────────── -->
    <div class="crm-page-header mb-3">
        <div class="crm-page-icon me-3">
            <a href="<?= site_url('crm/groups') ?>" class="fs-semibold text-decoration-none" title="Back to groups">
                <i class="ti ti-arrow-back-up"></i>
            </a>
        </div>
        <div class="flex-grow-1">
            <div class="crm-page-title"><?= html_escape($groupName) ?></div>
            <div class="crm-page-sub">
                Managed for <span class="fw-semibold"><?= html_escape($companyName) ?></span>
            </div>
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">

            <?php if ($canEdit): ?>
                <button type="button"
                        class="btn btn-light-primary btn-header"
                        data-modal-url="<?= site_url('crm/group_edit_modal/' . $groupId) ?>">
                    <i class="ti ti-edit"></i> Edit
                </button>
            <?php endif; ?>

            <div class="btn-divider"></div>

            <div class="dropdown">
                <button class="btn btn-light-primary btn-header dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end app-page-header__menu">
                    <a class="dropdown-item" href="#" onclick="addClientToGroup(<?= $groupId ?>)">
                        <i class="ti ti-user-plus"></i> Add Client
                    </a>
                    <a class="dropdown-item" href="#" onclick="logActivity('call')">
                        <i class="ti ti-phone"></i> Log a Call
                    </a>
                    <a class="dropdown-item" href="#" onclick="logActivity('email')">
                        <i class="ti ti-mail"></i> Email Group Contact
                    </a>
                    <a class="dropdown-item" href="#" onclick="createGroupInvoice()">
                        <i class="ti ti-file-invoice"></i> Create Group Invoice
                    </a>
                    <div class="dropdown-divider"></div>
                    <?php if ($canDelete): ?>
                        <button type="button" class="dropdown-item text-danger"
                                onclick="deleteGroup(<?= $groupId ?>)">
                            <i class="ti ti-trash"></i> Delete Group
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Hero Card ──────────────────────────────────── -->
    <div class="crm-card">
        <div class="hero-inner">
            <div class="avatar"><?= html_escape($initials) ?></div>
            <div class="hero-info">
                <h2 class="hero-name">
                    <?= html_escape($companyName) ?>
                    <?= group_status_badge($status) ?>
                </h2>
                <div class="badge-row">
                    <span class="badge badge-type">
                        <i class="ti ti-users-group" style="font-size:11px;"></i> Client Group
                    </span>
                    <?php if (!empty($group['industry'])): ?>
                        <span class="badge badge-pill">
                            <i class="ti ti-briefcase"></i>
                            <?= html_escape($group['industry']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($group['onboarding_status'])): ?>
                        <span class="badge badge-pill">
                            <i class="ti ti-loader"></i>
                            <?= html_escape(ucwords(str_replace('_', ' ', $group['onboarding_status']))) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($group['website'])): ?>
                        <span class="badge badge-pill">
                            <i class="ti ti-world text-primary"></i>
                            <a href="<?= prep_url($group['website']) ?>" target="_blank" class="text-decoration-none">
                                Website
                            </a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($group['contract_date'])): ?>
                        <span class="badge badge-pill">
                            <i class="ti ti-signature"></i>
                            Contracted: <?= html_escape(crm_date($group['contract_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KPI Strip -->
        <div class="kpi-strip">
            <div class="kpi">
                <span class="kpi-label">Total Clients</span>
                <span class="kpi-value"><?= $totalClients ?></span>
                <span class="kpi-sub">All assigned</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Active Clients</span>
                <span class="kpi-value kpi-value-success"><?= $activeClients ?></span>
                <span class="kpi-sub">Currently active</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Inactive Clients</span>
                <span class="kpi-value"><?= $inactiveClients ?></span>
                <span class="kpi-sub">Paused or ended</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Invoice Mode</span>
                <span class="kpi-value"><?= html_escape(ucfirst($val($group['invoice_mode'] ?? null))) ?></span>
                <span class="kpi-sub">Billing method</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Next Renewal</span>
                <span class="kpi-value">
                    <?= !empty($group['next_renew']) ? html_escape(crm_date($group['next_renew'])) : '—' ?>
                </span>
                <span class="kpi-sub">Upcoming renewal</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Contract Ends</span>
                <span class="kpi-value">
                    <?= !empty($group['contract_end']) ? html_escape(crm_date($group['contract_end'])) : '—' ?>
                </span>
                <span class="kpi-sub">Expiry date</span>
            </div>
        </div>

        <div class="col-md-12">
            <div class="crm-profile-wrap mt-4">
                <ul class="crm-tab-nav" id="groupProfileTabs" role="tablist">
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn active"
                                id="group-details-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#group-details-pane"
                                type="button" role="tab"
                                aria-controls="group-details-pane"
                                aria-selected="true">
                            <i class="ti ti-list-details"></i> Group Details
                        </button>
                    </li>
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn"
                                id="group-clients-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#group-clients-pane"
                                type="button" role="tab"
                                aria-controls="group-clients-pane"
                                aria-selected="false">
                            <i class="ti ti-users"></i> Clients
                            <span class="crm-tab-badge"><?= $totalClients ?></span>
                        </button>
                    </li>
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn"
                                id="group-invoices-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#group-invoices-pane"
                                type="button" role="tab"
                                aria-controls="group-invoices-pane"
                                aria-selected="false">
                            <i class="ti ti-file-invoice"></i> Invoices
                            <span class="crm-tab-badge"><?= isset($invoices) && is_array($invoices) ? count($invoices) : 0 ?></span>
                        </button>
                    </li>
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn"
                                id="group-payments-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#group-payments-pane"
                                type="button" role="tab"
                                aria-controls="group-payments-pane"
                                aria-selected="false">
                            <i class="ti ti-cash"></i> Payments
                            <span class="crm-tab-badge"><?= isset($payments) && is_array($payments) ? count($payments) : 0 ?></span>
                        </button>
                    </li>
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn"
                                id="group-activity-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#group-activity-pane"
                                type="button" role="tab"
                                aria-controls="group-activity-pane"
                                aria-selected="false">
                            <i class="ti ti-activity"></i> Activity
                            <span class="crm-tab-badge"><?= isset($activity_log) && is_array($activity_log) ? count($activity_log) : 0 ?></span>
                        </button>
                    </li>
        
                </ul>
        
                <div class="tab-content crm-tab-content" id="groupProfileTabsContent">
        
                    <div class="tab-pane fade show active"
                         id="group-details-pane"
                         role="tabpanel"
                         aria-labelledby="group-details-tab"
                         tabindex="0">
                        <?= $CI->load->view('crm/groups/tabs/group_details', [
                            'group'       => $group,
                            'groupName'   => $groupName,
                            'companyName' => $companyName,
                            'fullAddress' => $fullAddress,
                            'status'      => $status,
                        ], true); ?>
                    </div>
        
                    <div class="tab-pane fade"
                         id="group-clients-pane"
                         role="tabpanel"
                         aria-labelledby="group-clients-tab"
                         tabindex="0">
                        <?= $CI->load->view('crm/groups/tabs/group_clients', [
                            'group'          => $group,
                            'clients'        => $clients ?? [],
                            'canViewClients' => $canViewClients,
                        ], true); ?>
                    </div>
        
                    <div class="tab-pane fade"
                         id="group-invoices-pane"
                         role="tabpanel"
                         aria-labelledby="group-invoices-tab"
                         tabindex="0">
                        <?= $CI->load->view('crm/groups/tabs/group_invoices', [
                            'group'    => $group,
                            'invoices' => $invoices ?? [],
                        ], true); ?>
                    </div>
        
                    <div class="tab-pane fade"
                         id="group-payments-pane"
                         role="tabpanel"
                         aria-labelledby="group-payments-tab"
                         tabindex="0">
                        <?= $CI->load->view('crm/groups/tabs/group_payments', [
                            'group'    => $group,
                            'payments' => $payments ?? [],
                        ], true); ?>
                    </div>
        
                    <div class="tab-pane fade"
                         id="group-activity-pane"
                         role="tabpanel"
                         aria-labelledby="group-activity-tab"
                         tabindex="0">
                        <?= $CI->load->view('crm/groups/tabs/group_activity', [
                            'group'        => $group,
                            'activity_log' => $activity_log ?? [],
                        ], true); ?>
                    </div>
        
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── AJAX Modal Shell ────────────────────────────── -->
<div class="modal fade app-modal" id="crmGroupModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div id="crmGroupModalBody">
                <div class="p-4 text-muted text-center">
                    <i class="ti ti-loader ti-spin me-2"></i> Loading…
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {

    // ── AJAX modal loader ─────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-modal-url]');
        if (!btn) return;

        const url  = btn.getAttribute('data-modal-url');
        const body = document.getElementById('crmGroupModalBody');
        if (!url || !body) return;

        body.innerHTML = '<div class="p-4 text-center text-muted"><i class="ti ti-loader ti-spin me-2"></i> Loading…</div>';

        const modalEl = document.getElementById('crmGroupModal');
        const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        fetch(url, { credentials: 'same-origin' })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(html => body.innerHTML = html)
            .catch(() => body.innerHTML = '<div class="p-4 text-danger"><i class="ti ti-alert-circle me-2"></i> Failed to load. Please try again.</div>');
    });

    // ── Clean up modal on close ───────────────────────
    document.getElementById('crmGroupModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('crmGroupModalBody').innerHTML =
            '<div class="p-4 text-center text-muted"><i class="ti ti-loader ti-spin me-2"></i> Loading…</div>';
    });

    // ── Action stubs ──────────────────────────────────
    window.addClientToGroup   = id => console.log('[CRM] Add client to group', id);
    window.createGroupInvoice = ()  => console.log('[CRM] Create group invoice');
    window.logActivity        = type => console.log('[CRM] Log activity:', type);

    window.deleteGroup = function (id) {
        if (!confirm('Delete this group? This action cannot be undone.')) return;
        fetch('<?= site_url('crm/group_delete') ?>/' + id, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.href = '<?= site_url('crm/groups') ?>';
            } else {
                alert(res.message || 'Delete failed. Please try again.');
            }
        })
        .catch(() => alert('Delete request failed. Please try again.'));
    };

})();
</script>