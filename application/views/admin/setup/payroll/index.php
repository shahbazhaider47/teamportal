<div class="container-fluid">

<!-- ==========================================================
 | PAGE HEADER
 ========================================================== -->
<div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title">Payroll Settings</h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="btn-divider"></div> 
        
        <!-- SETTINGS QUICK NAV -->
        <div class="dropdown">
            <button class="btn btn-header btn-primary dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown">
                <i class="ti ti-settings"></i> Select Setting
            </button>

            <ul class="dropdown-menu p-2">
                <h6 class="text-muted small mt-1 text-center">Settings Menu</h6>
                <div class="app-divider-v dashed"></div>
                    
                <li class="small">
                    <a class="dropdown-item small" href="<?= site_url('admin/setup/company') ?>">
                        <i class="ti ti-building me-2 text-primary"></i> Company Setup
                    </a>
                </li>

                <li class="small">
                    <a class="dropdown-item small" href="<?= site_url('admin/setup/attendance') ?>">
                        <i class="ti ti-calendar-time me-2 text-primary"></i> Attendance Settings
                    </a>
                </li>

                <li class="small">
                    <a class="dropdown-item small" href="<?= site_url('admin/setup/payroll') ?>">
                        <i class="ti ti-report-money me-2 text-primary"></i> Payroll Settings
                    </a>
                </li>

                <li class="small">
                    <a class="dropdown-item small" href="<?= site_url('admin/setup/utility') ?>">
                        <i class="ti ti-keyframes me-2 text-primary"></i>
                        Utility <small class="text-muted">(Import & Export)</small>
                    </a>
                </li>
                    
                <div class="app-divider-v dashed"></div>
                
                <li class="small">
                    <a class="dropdown-item small text-danger" href="<?= site_url('settings') ?>">
                        <i class="ti ti-adjustments me-2 text-danger"></i>
                        Default System Settings
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- ==========================================================
 | PAYROLL SETUP CARD
 ========================================================== -->
<div class="card">
    <div class="card-body">

        <!-- PAYROLL TABS -->
        <ul class="nav nav-tabs tab-primary bg-primary p-1 mb-3 small"
            id="payrollTabs"
            role="tablist">

            <li class="nav-item">
                <button class="nav-link active"
                        data-bs-toggle="tab"
                        data-bs-target="#salarycycles">
                    <i class="ti ti-calendar-stats me-1"></i> Salary Cycles
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#allowances">
                    <i class="ti ti-cash me-1"></i> Allowances
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#deductions">
                    <i class="ti ti-cash-off me-1"></i> Deductions
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#taxrules">
                    <i class="ti ti-percentage me-1"></i> Tax Rules
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#calculations">
                    <i class="ti ti-calculator me-1"></i> Payroll Calculation
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#finalsettlement">
                    <i class="ti ti-clipboard-check me-1"></i> Final Settlement
                </button>
            </li>
        </ul>

        <!-- TAB CONTENT -->
        <div class="tab-content small">

            <div class="tab-pane fade show active" id="salarycycles">
                <div class="text-muted p-3">Salary cycle configuration coming soon</div>
            </div>

            <div class="tab-pane fade" id="allowances">
                <div class="text-muted p-3">Allowance rules configuration coming soon</div>
            </div>

            <div class="tab-pane fade" id="deductions">
                <div class="text-muted p-3">Deduction rules configuration coming soon</div>
            </div>

            <div class="tab-pane fade" id="taxrules">
                <div class="text-muted p-3">Tax rules configuration coming soon</div>
            </div>

            <div class="tab-pane fade" id="calculations">
                <div class="text-muted p-3">Payroll calculation logic coming soon</div>
            </div>

            <div class="tab-pane fade" id="finalsettlement">
                <div class="text-muted p-3">Final settlement rules coming soon</div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /**
     * Hash → Tab Pane ID mapping
     * URL hash MUST NOT include #
     * Example: /admin/setup/payroll#allowances
     */
    const tabMap = {
        'salarycycles'   : 'salarycycles',
        'allowances'     : 'allowances',
        'deductions'     : 'deductions',
        'taxrules'       : 'taxrules',
        'calculations'   : 'calculations',
        'finalsettlement': 'finalsettlement'
    };

    /**
     * Activate tab based on URL hash
     */
    function activateTabFromHash() {
        let hash = window.location.hash.replace('#', '');

        // Default tab
        if (!hash || !tabMap[hash]) {
            hash = 'salarycycles';
        }

        const targetPaneId = tabMap[hash];
        const trigger = document.querySelector(
            `[data-bs-target="#${targetPaneId}"]`
        );

        if (trigger) {
            const tab = new bootstrap.Tab(trigger);
            tab.show();
        }
    }

    /**
     * Update URL hash when tab changes
     */
    document.querySelectorAll('#payrollTabs [data-bs-toggle="tab"]')
        .forEach(tabBtn => {
            tabBtn.addEventListener('shown.bs.tab', function (e) {
                const targetId = e.target
                    .getAttribute('data-bs-target')
                    .replace('#', '');

                // Reverse lookup
                const hash = Object.keys(tabMap)
                    .find(key => tabMap[key] === targetId);

                if (hash) {
                    history.replaceState(null, '', '#' + hash);
                }
            });
        });

    // Initial page load
    activateTabFromHash();

    // Browser back / forward navigation
    window.addEventListener('hashchange', activateTabFromHash);
});
</script>
