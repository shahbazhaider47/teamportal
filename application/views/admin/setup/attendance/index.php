<link rel="stylesheet" href="<?=base_url('assets/css/app-multiselect.min.css')?>">

<div class="container-fluid">

<!-- ==========================================================
 | PAGE HEADER
 ========================================================== -->
<div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title">Attendance Settings</h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="btn-divider"></div> 
        
        <!-- SETTINGS QUICK NAV -->
        <div class="dropdown">
            <button class="btn btn-header btn-primary dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
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
 | ATTENDANCE SETUP CARD
 ========================================================== -->
<div class="card">
    <div class="card-body">

        <!-- ==================================================
         | ATTENDANCE CONFIGURATION TABS
         ================================================== -->
        <ul class="nav nav-tabs tab-primary bg-primary p-1 mb-3 small"
            id="setupTabs"
            role="tablist">

            <li class="nav-item">
                <button class="nav-link active"
                        id="shifts-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#shifts"
                        type="button">
                    <i class="ti ti-clock me-1"></i> Work Shifts
                </button>
            </li>
                
            <li class="nav-item">
                <button class="nav-link"
                        id="holidays-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#holidays"
                        type="button">
                    <i class="ti ti-calendar-event me-1"></i> Public Holidays
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        id="leavetypes-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#leavetypes"
                        type="button">
                    <i class="ti ti-clipboard-list me-1"></i> Leave Types
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        id="attendancesettings-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#attendancesettings"
                        type="button">
                    <i class="ti ti-settings me-1"></i> Attendance Settings
                </button>
            </li>
            
        </ul>

        <!-- ==================================================
         | TAB CONTENT
         ================================================== -->
        <div class="tab-content small" id="setupTabsContent">

            <!-- WORK SHIFTS -->
            <div class="tab-pane fade show active"
                 id="shifts"
                 role="tabpanel"
                 aria-labelledby="which-tab">
                <!-- Load Related Modal --> 
                <?php $CI =& get_instance();
                echo $CI->load->view( 'admin/setup/attendance/tabs/1_shifts', ['shifts' => $shifts ?? []], true ); ?>
            </div>
            
            <!-- PUBLIC HOLIDAYS -->
            <div class="tab-pane fade"
                 id="holidays"
                 role="tabpanel"
                 aria-labelledby="holidays-tab">
                <!-- Load Related Modal --> 
                <?php $CI =& get_instance();
                echo $CI->load->view( 'admin/setup/attendance/tabs/2_holidays', ['holidays' => $holidays ?? []], true ); ?>
            </div>

            <!-- LEAVE TYPES -->
            <div class="tab-pane fade"
                 id="leavetypes"
                 role="tabpanel"
                 aria-labelledby="leavetypes-tab">
                <!-- Load Related Modal --> 
                <?php $CI =& get_instance();
                echo $CI->load->view( 'admin/setup/attendance/tabs/3_leavetypes', ['leavetypes' => $leavetypes ?? []], true ); ?>
            </div>

            <!-- OVERTIME POLICY -->
            <div class="tab-pane fade"
                 id="attendancesettings"
                 role="tabpanel"
                 aria-labelledby="attendancesettings-tab">
                <!-- Load Related Modal --> 
                <?php $CI =& get_instance();
                echo $CI->load->view( 'admin/setup/attendance/tabs/4_attendancesettings', ['attendancesettings' => $attendancesettings ?? []], true ); ?>
            </div>

        </div>
    </div>
</div>
</div>

<script src="<?= base_url('assets/js/app-multiselect.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /**
     * Hash → Tab Pane ID mapping
     * URL hash MUST NOT include #
     */
    const tabMap = {
        'shifts': 'shifts',
        'holidays': 'holidays',
        'leavetypes': 'leavetypes',
        'attendancesettings': 'attendancesettings',
    };

    /**
     * Activate tab by hash
     */
    function activateTabFromHash() {
        let hash = window.location.hash.replace('#', '');

        // Default tab
        if (!hash || !tabMap[hash]) {
            hash = 'shifts';
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
    document.querySelectorAll('#setupTabs [data-bs-toggle="tab"]')
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

    // Initial load
    activateTabFromHash();

    // Back/forward browser navigation
    window.addEventListener('hashchange', activateTabFromHash);
});
</script>
