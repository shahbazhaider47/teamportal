<?php
$CI = &get_instance();
$menu_items = get_complete_sidebar_menu('finance');
$current_uri = trim($CI->uri->uri_string(), '/');
$year = date('Y');
?>

<nav class="dark-sidebar d-flex flex-column" style="min-height: 100vh;">
    <div class="app-nav" id="app-simple-bar" style="flex: 1;">
        <!-- Mobile-only logo section -->
        <div class="app-logo-mobile d-lg-none">
            <div class="d-flex align-items-center justify-content-between px-3 py-2">
                <?php
                $company = company_info();
                $lightLogo = !empty($company['light_logo']) ? base_url('uploads/company/' . $company['light_logo']) : base_url('uploads/company/default-light.png');
                $darkLogo  = !empty($company['dark_logo'])  ? base_url('uploads/company/' . $company['dark_logo'])  : base_url('uploads/company/default-dark.png');
                ?>
                <a class="logo brand-logo" href="<?= base_url('') ?>">
                    <img src="<?= $darkLogo ?>" alt="<?= html_escape($company['company_name'] ?? 'Logo') ?>" class="dark-logo" style="height: 30px;">
                    <img src="<?= $lightLogo ?>" alt="<?= html_escape($company['company_name'] ?? 'Logo') ?>" class="light-logo" style="height: 30px;">
                </a>

                <!-- Close button visible on mobile only -->
                <button type="button" class="sidebar-close-btn toggle-semi-nav btn btn-link p-0" style="font-size: 1.8rem; color: #fff;">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="app-divider-v mt-1 mb-3"></div>
        </div>

        <ul class="main-nav p-0 mt-0">
            <?php foreach ($menu_items as $slug => $item):
                $has_children = !empty($item['children']);
                $is_active = false;

                $parent_path = trim(parse_url($item['href'], PHP_URL_PATH) ?? '', '/');
                if ($parent_path && $parent_path !== '#' && $parent_path !== '') {
                    $is_active = $parent_path === $current_uri;
                }

                if ($has_children) {
                    foreach ($item['children'] as $child) {
                        $child_path = trim(parse_url($child['href'], PHP_URL_PATH), '/');
                        if ($child_path === $current_uri) {
                            $is_active = true;
                            break;
                        }
                    }
                }

                $badge_html = '';
                if (isset($item['badge']['value'])) {
                    $badge_class = $item['badge']['class'] ?? 'text-bg-success';
                    $badge_html = '<span class="badge ' . $badge_class . ' badge-notification ms-2">' . $item['badge']['value'] . '</span>';
                }
            ?>

            <li class="<?= $has_children ? 'has-sub' : 'no-sub' ?> <?= $is_active ? 'active' : '' ?>">
                <a class=""
                   <?= $has_children ? 'data-bs-toggle="collapse"' : '' ?>
                   href="<?= $has_children ? "#$slug" : htmlspecialchars($item['href'], ENT_QUOTES) ?>"
                   aria-expanded="<?= $is_active ? 'true' : 'false' ?>"
                   <?= $has_children ? 'aria-controls="' . $slug . '"' : '' ?>>
                    <?php if (!empty($item['icon'])): ?>
                        <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES) ?>"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['name'], ENT_QUOTES) ?>
                    <?= $badge_html ?>
                </a>

                <?php if ($has_children): ?>
                    <ul class="collapse <?= $is_active ? 'show' : '' ?>" id="<?= $slug ?>">
                        <?php foreach ($item['children'] as $child_slug => $child):
                            $child_path = trim(parse_url($child['href'], PHP_URL_PATH), '/');
                            $child_active = ($child_path === $current_uri);
                        ?>
                        <li class="no-sub <?= $child_active ? 'active' : '' ?>">
                            <a href="<?= htmlspecialchars($child['href'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($child['name'], ENT_QUOTES) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>

        </ul>
    </div>

    <div class="sidebar-footer mt-auto" style="position: sticky; bottom: 0; background: inherit;">
        <p class="p-1 text-muted text-center x-small">
            &copy; <?= $year ?> <?= get_company_name($company) ?>
        <br>
        All Rights Reserved. <span class="ms-1">v<?= html_escape($app_version) ?>  ✨</span>
        </p>
    </div>

    <div class="menu-navs">
        <span class="menu-previous"><i class="ti ti-chevron-left"></i></span>
        <span class="menu-next"><i class="ti ti-chevron-right"></i></span>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.querySelector('.dark-sidebar');
    var overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // Toggle sidebar
    document.querySelector('.header-toggle').addEventListener('click', function() {
        sidebar.classList.toggle('mobile-active');
        overlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
        
        // Add/remove padding to prevent content shift when scrollbar disappears
        if (document.body.classList.contains('sidebar-open')) {
            document.body.style.paddingRight = window.innerWidth - document.documentElement.clientWidth + 'px';
        } else {
            document.body.style.paddingRight = '';
        }
    });

    // Hide on overlay click
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-active');
        overlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
        document.body.style.paddingRight = '';
    });

    // Close button functionality
    var closeBtn = document.querySelector('.sidebar-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            sidebar.classList.remove('mobile-active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            document.body.style.paddingRight = '';
        });
    }
    
    // Close sidebar when clicking on nav links (optional)
    document.querySelectorAll('.main-nav a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('mobile-active');
                overlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                document.body.style.paddingRight = '';
            }
        });
    });
});
</script>