<!-- Header Section starts -->
<header class="header-main">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card-header">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 d-flex align-items-center header-left">
                            <div class="app-logo">
                                <?php
                                $company = company_info();
                                $lightLogo = !empty($company['light_logo']) ? base_url('uploads/company/' . $company['light_logo']) : base_url('uploads/company/default.png');
                                ?>
                                <a class="logo brand-logo d-inline-block" href="<?= base_url('') ?>">
                                    <img src="<?= $lightLogo ?>" alt="<?= html_escape($company['company_name'] ?? 'Logo') ?>" class="">
                                </a>
                            </div>
                                <span class="header-toggle me-3">
                                    <i class="ti ti-category"></i>
                                </span>
                                
                                <div class="header-searchbar">
                                    <form class="me-3 app-form app-icon-form " action="#">
                                        <div class="position-relative">
                                            <input type="search" class="form-control" placeholder="Search..."
                                                   aria-label="Search">
                                            <i class="ti ti-search text-dark"></i>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="col-6 d-flex align-items-center justify-content-end header-right">
                                <ul class="d-flex align-items-center">
                                    <li class="header-search">
                                        <a href="#" class="d-block head-icon" role=button data-bs-toggle="offcanvas"
                                           data-bs-target="#offcanvasTop" aria-controls="offcanvasTop">
                                            <i class="ti ti-search"></i>
                                        </a>

                                        <div class="offcanvas offcanvas-top search-canvas" tabindex="-1"
                                             id="offcanvasTop">
                                            <div class="offcanvas-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <form class="me-3 app-form app-icon-form " action="#">
                                                            <div class="position-relative">
                                                                <input type="search" class="form-control"
                                                                       placeholder="Search..."
                                                                       aria-label="Search">
                                                                <i class="ti ti-search f-s-15"></i>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                                            aria-label="Close"></button>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    
                                    <?php if (function_exists('is_superadmin') && is_superadmin()): ?>
                                      <span
                                        class="badge rounded-pill ms-2 me-2 bg-danger-subtle text-danger border border-danger-subtle"
                                        title="All permission checks are bypassed for this session."
                                        aria-label="Viewing as Super Admin">
                                        <i class="ti ti-user me-2"></i> Super Admin
                                      </span>
                                    <?php endif; ?>

                                    <?php
                                    $CI =& get_instance();
                                    
                                    $userId = (int) ($CI->session->userdata('user_id') ?? 0);
                                    $totalRequests = 0;
                                    
                                    if ($userId > 0) {
                                        if (!isset($CI->requests)) {
                                            $CI->load->model('Requests_model', 'requests');
                                        }
                                    
                                        // Unified requests count (new system)
                                        if (method_exists($CI->requests, 'get_request_stats')) {
                                            $stats = $CI->requests->get_request_stats($userId, []);
                                            $totalRequests = (int) ($stats['total'] ?? 0);
                                        }
                                    }
                                    ?>
                                    
                                    <?php if (
                                        (function_exists('is_teamlead')   && is_teamlead()) ||
                                        (function_exists('is_superadmin') && is_superadmin()) ||
                                        (function_exists('is_manager')    && is_manager())
                                    ): ?>
                                    
                                    <button type="button"
                                            class="btn btn-light-primary position-relative btn-header"
                                            onclick="window.open('<?= site_url('requests'); ?>', '_blank');">
                                        <i class="ti ti-forms me-1"></i> Requests
                                    
                                        <?php if ($totalRequests > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary badge-notification">
                                                <?= $totalRequests; ?>
                                                <span class="visually-hidden">Total Requests</span>
                                            </span>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <?php endif; ?>


                                    <?php
                                    $CI =& get_instance();
                                    $CI->load->helper('global_helper');
                                    $iconApps = get_header_app_icons();
                                    ?>
                                    
                                    <?php foreach ($iconApps as $app): ?>
                                      <li class="d-block head-icon">
                                        <a href="<?= $app['href'] ?>">
                                          <span class="">
                                            <i class="<?= $app['icon'] ?>"></i>
                                          </span>
                                        </a>
                                      </li>
                                    <?php endforeach; ?>
                                    
                                    <li class="d-block head-icon">
                                        <a href="#" class="d-block head-icon" id="btn-fullscreen" title="Fullscreen">
                                            <i class="ti ti-maximize" id="fullscreen-icon"></i>
                                        </a>
                                    </li>
        
                                    <!-- Starts Loading Shortcuts -->
                                    <?php $CI =& get_instance(); ?>
                                    <?php echo $CI->load->view('layouts/includes/shortcuts', [], true); ?>
                                    <!-- Ends Loading Shortcuts -->

                                    <!-- Starts Loading Qick Add -->
                                    
                                    <!-- Ends Loading Qick Add -->
                                    
                                    <!-- Notifications Section Start -->
                                    <li class="header-notification">
                                        <div class="flex-shrink-0 app-dropdown">
                                            <a href="#" class="d-block head-icon position-relative"
                                               data-bs-toggle="dropdown"
                                               data-bs-auto-close="outside" aria-expanded="false">
                                                <i class="ti ti-bell"></i>
                                                <span id="top-notifications-count"
                                                      class="position-absolute translate-middle badge rounded-pill bg-info badge-notification <?= $notifUnreadCount > 0 ? '' : 'd-none'; ?>">
                                                    <?= (int) $notifUnreadCount ?>
                                                </span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end bg-transparent border-0">
                                                <div class="card">
                                                    <div class="card-header bg-primary py-2 px-3">
                                                        <h6 class="text-white mb-0 f-s-14">
                                                            Notifications
                                                            <span class="float-end"><i class="ti ti-bell text-white"></i></span>
                                                        </h6>
                                                    </div>
                                    
                                                    <div class="card-body p-0">
                                                        <div id="top-notifications-list" class="head-container app-scroll">
                                                            <?php if (empty($notifUnreadList)): ?>
                                                                <div class="hidden-massage py-4 px-3 text-center">
                                                                    <div class="display-4 text-muted mb-3 mt-2">
                                                                        <i class="ti ti-bell"></i>
                                                                    </div>
                                                                    <h6 class="mb-1 f-s-14">No New Notifications</h6>
                                                                    <p class="text-secondary f-s-12">Your latest updates will appear here.</p>
                                                                </div>
                                                            <?php else: foreach ($notifUnreadList as $n): ?>
                                                                <a href="<?= site_url('notifications?mark_read=' . $n['id']) ?>"
                                                               class="head-box d-flex align-items-start justify-content-between px-3 py-2 border-bottom text-decoration-none text-dark">
                                    
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="bg-secondary h-35 w-35 d-flex-center b-r-50 position-relative me-2">
                                                                            <?php
                                                                            $senderImage = trim($n['sender_image'] ?? '');
                                                                            $profileImagePath = $senderImage
                                                                                ? base_url('uploads/users/profile/' . $senderImage)
                                                                                : base_url('assets/images/default.png');
                                                                            ?>
                                                                            <img 
                                                                                src="<?= $profileImagePath ?>" 
                                                                                alt="<?= html_escape($n['sender_first'] ?? 'User') ?>" 
                                                                                class="img-fluid b-r-50 h-35 w-35"
                                                                                onerror="this.onerror=null;this.src='<?= base_url('assets/images/default.png') ?>';"
                                                                            />
                                                                        </span>
                                                                        <div>
                                                                            <h6 class="mb-1 f-s-13 text-truncate" style="max-width: 180px;">
                                                                                <?= html_escape($n['short_text']) ?>
                                                                            </h6>
                                                                            <p class="text-secondary f-s-11 mb-0"><?= date('M j, H:i', strtotime($n['created_at'])) ?></p>
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                            <?php endforeach; endif; ?>
                                                        </div>
                                                    </div>
                                    
                                                    <div class="card-footer py-2 px-3">
                                                        <a href="<?= site_url('notifications') ?>" class="btn btn-sm btn-primary w-100">
                                                            <i class="ti ti-list"></i> View All
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- Notifications Section Ends -->



                                    
                                    <!-- User Section Start -->
                                    <?php
                                    $CI =& get_instance();
                                    $CI->load->model('User_model');
                                    
                                    // Load user data
                                    $user = [];
                                    if ($CI->session->userdata('is_logged_in')) {
                                        $user_id = $CI->session->userdata('user_id');
                                        $user = $CI->User_model->get_user_by_id($user_id) ?: [];
                                    }
                                    
                                    // Set profile image
                                    $default_avatar   = base_url('assets/images/default.png');
                                    $profile_img_url  = $default_avatar;
                                    if (!empty($user['profile_image'])) {
                                        $uploaded_path = FCPATH . 'uploads/users/profile/' . $user['profile_image'];
                                        if (is_file($uploaded_path)) {
                                            $profile_img_url = base_url('uploads/users/profile/' . $user['profile_image']);
                                        }
                                    }
                                    
                                    // Set display info
                                    $firstName = $user['firstname'] ?? '';
                                    $lastName  = $user['lastname'] ?? '';
                                    $full_name = trim($firstName . ' ' . $lastName);
                                    $title     = $user['emp_title'] ?? '';
                                    
                                    if (!function_exists('e')) {
                                        function e($string) {
                                            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
                                        }
                                    }
                                    ?>
                                    
                                    <li class="header-profile">
                                        <div class="flex-shrink-0 dropdown">
                                            <a href="#" class="d-block head-icon pe-0" data-bs-toggle="dropdown" aria-expanded="false">
                                                <img src="<?= $profile_img_url ?>" class="rounded-circle h-40 w-40 me-2"
                                                     onerror="this.onerror=null;this.src='<?= $default_avatar ?>';">
                                                <span class="text-muted small me-2"><?= e($firstName) ?></span>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end header-card border-0 px-2">
                                                <li class="dropdown-item d-flex align-items-center p-2">
                                                    <span class="h-40 w-40 d-flex-center position-relative" style="border-radius: 50%; overflow: hidden;">
                                                        <img src="<?= $profile_img_url ?>"
                                                        alt="<?= e($full_name) ?>"
                                                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                                                        onerror="this.onerror=null;this.src='<?= $default_avatar ?>';">
                                                    </span>
                                                    <div class="flex-grow-1 ps-2">
                                                        <h6 class="mb-0" style="font-size: 13px;"><?= e($full_name) ?></h6>
                                                        <?php if ($title): ?>
                                                            <p class="f-s-12 mb-0 text-secondary"><?= e($title) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                    
                                                <li class="app-divider-v dotted py-1"></li>
                                                
                                                <?php foreach (get_profile_menu_items() as $item): ?>
                                                    <?php if (($item['type'] ?? '') === 'divider'): ?>
                                                        <li class="app-divider-v dotted py-1"></li>
                                                    <?php else: ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= htmlspecialchars($item['href'], ENT_QUOTES) ?>">
                                                                <?php if (!empty($item['icon'])): ?>
                                                                    <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES) ?>"></i>
                                                                <?php endif; ?>
                                                                <?= htmlspecialchars($item['name'], ENT_QUOTES) ?>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                    
                                                <li class="app-divider-v dotted py-1"></li>
                                            </ul>
                                        </div>
                                    </li>

                                    <!-- User Section Ends -->
                                    
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Header Section ends -->


<script>
document.addEventListener("DOMContentLoaded", function () {
    const notifLinks = document.querySelectorAll('.head-box');

    notifLinks.forEach(link => {
        link.addEventListener('click', function () {
            this.classList.add('opacity-50'); // visual feedback (optional)
        });
    });
});
</script>