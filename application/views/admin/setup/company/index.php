<div class="container-fluid">
<div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title">Company Main Setup</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        
        <div class="btn-divider"></div> 
        
            <div class="dropdown">
                <button class="btn btn-header btn-primary dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-settings"></i> Select Setting
                </button>
                <ul class="dropdown-menu p-2">
                <h6 class="text-muted small mt-1 text-center">Settings Menu</h6>
                <div class="app-divider-v dashed"></div>
                    
                    <li class="small">
                        <a class="dropdown-item small" href="<?= site_url('admin/setup/company') ?>">
                            <i class="ti ti-building me-2 text-primary"></i>Company Setup
                        </a>
                    </li>

                    <li class="small">
                        <a class="dropdown-item small" href="<?= site_url('admin/setup/attendance') ?>">
                            <i class="ti ti-calendar-time me-2 text-primary"></i>Attendance Setting
                        </a>
                    </li>

                    <li class="small">
                        <a class="dropdown-item small" href="<?= site_url('admin/setup/payroll') ?>">
                            <i class="ti ti-report-money me-2 text-primary"></i>Payroll Setting
                        </a>
                    </li>

                    <li class="small">
                        <a class="dropdown-item small" href="<?= site_url('admin/setup/utility') ?>">
                            <i class="ti ti-keyframes me-2 text-primary"></i>Utility <small class="text-muted">(Import & Export)</small>
                        </a>
                    </li>
                    
                    <div class="app-divider-v dashed"></div>
                
                    <li class="small">
                        <a class="dropdown-item small text-danger" href="<?= site_url('settings') ?>">
                            <i class="ti ti-adjustments me-2 text-danger"></i>Default System Settings
                        </a>
                    </li>
                </ul>
            </div>
        
    </div>
</div>

    <div class="card">
        <div class="card-body">

            <!-- Setup Tabs -->
            <ul class="nav nav-tabs tab-primary bg-primary p-1 mb-3 small"
                id="setupTabs"
                role="tablist">

                <li class="nav-item" role="presentation">
                    <button class="nav-link active"
                        id="orgchart-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#orgchart"
                        type="button"
                        role="tab">
                        <i class="ti ti-users me-1"></i> Org Chart
                    </button>
                </li>
                
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="company_info-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#company_info"
                        type="button"
                        role="tab">
                        <i class="ti ti-building-skyscraper me-1"></i> Company Info
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="offices-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#offices"
                        type="button"
                        role="tab">
                        <i class="ti ti-map me-1"></i> Offices
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="departments-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#departments"
                        type="button"
                        role="tab">
                        <i class="ti ti-building me-1"></i> Departments
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="positions-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#positions"
                        type="button"
                        role="tab">
                        <i class="ti ti-shield-lock me-1"></i> Positions
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="staffroles-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#staffroles"
                        type="button"
                        role="tab">
                        <i class="ti ti-user-check me-1"></i> Staff Roles
                    </button>
                </li>
                
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="variablestypes-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#variablestypes"
                        type="button"
                        role="tab">
                        <i class="ti ti-list me-1"></i> Variable Types
                    </button>
                </li>
                
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                        id="companysettings-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#companysettings"
                        type="button"
                        role="tab">
                        <i class="ti ti-settings me-1"></i> Company Settings
                    </button>
                </li>
                
            </ul>

            <!-- Tab Content -->
            <div class="tab-content small" id="setupTabsContent">

                <!-- TIME -->
                <div class="tab-pane fade show active"
                     id="orgchart"
                     role="tabpanel"
                     aria-labelledby="orgchart-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance();
                    echo $CI->load->view( 'admin/setup/company/tabs/1_orgchart', ['org_chart' => $org_chart ?? []], true ); ?>
                </div>

                <!-- Company Info -->
                <div class="tab-pane fade"
                     id="company_info"
                     role="tabpanel"
                     aria-labelledby="company_info-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'company' => $company ?? [], 'offices' => $offices ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/2_company_info', $modalData, true ); ?>
                </div>

                <!-- Offices -->
                <div class="tab-pane fade"
                     id="offices"
                     role="tabpanel"
                     aria-labelledby="offices-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'offices' => $offices ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/3_offices', $modalData, true ); ?>
                </div>

                <!-- Departments -->
                <div class="tab-pane fade"
                     id="departments"
                     role="tabpanel"
                     aria-labelledby="departments-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'departments' => $departments ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/4_departments', $modalData, true ); ?>
                </div>

                <!-- POSITIONS -->
                <div class="tab-pane fade"
                     id="positions"
                     role="tabpanel"
                     aria-labelledby="positions-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'positions' => $positions ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/5_positions', $modalData, true ); ?>
                </div>

                <!-- STAFF ROLES -->
                <div class="tab-pane fade"
                     id="staffroles"
                     role="tabpanel"
                     aria-labelledby="staffroles-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'roles' => $roles ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/6_staffroles', $modalData, true ); ?>
                </div>
                
                <!-- Variable Types -->
                <div class="tab-pane fade"
                     id="variablestypes"
                     role="tabpanel"
                     aria-labelledby="variablestypes-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'variablestypes' => $variablestypes ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/7_variablestypes', $modalData, true ); ?>
                </div>
                
                <!-- Company Settings -->
                <div class="tab-pane fade"
                     id="companysettings"
                     role="tabpanel"
                     aria-labelledby="companysettings-tab">
                    <!-- Load Related Modal --> 
                    <?php $CI =& get_instance(); $modalData = [ 'companysettings' => $companysettings ?? [], ];
                    echo $CI->load->view('admin/setup/company/tabs/8_companysettings', $modalData, true ); ?>
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
     */
    const tabMap = {
        'orgchart'          : 'orgchart',
        'company'           : 'company_info',
        'offices'           : 'offices',
        'departments'       : 'departments',
        'positions'         : 'positions',
        'staffroles'        : 'staffroles',
        'variablestypes'    : 'variablestypes',
        'companysettings'   : 'companysettings',
    };

    /**
     * Activate tab by hash
     */
    function activateTabFromHash() {
        let hash = window.location.hash.replace('#', '');

        // Default tab
        if (!hash || !tabMap[hash]) {
            hash = 'orgchart';
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
