<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
/**
 * Apps Hub View
 * -------------
 * - Shows a grid of dummy apps so you can see the layout working.
 * - Provides a hook-based function `apps_hub_collect_apps()` so modules can
 *   register themselves as apps.
 *
 * HOW MODULES CAN REGISTER AN APP:
 *
 * hooks()->add_filter('apps_hub_register', function ($apps) {
 *     $apps[] = [
 *         'app_slug'        => 'my_module',
 *         'app_name'        => 'My Module',
 *         'app_description' => 'Short description of what this app does.',
 *         'app_icon'        => 'ti ti-rocket',
 *         'app_image'       => '', // optional image URL
 *         'app_author'      => 'RCM Centric',
 *         'app_view_link'   => site_url('my_module'),
 *         'app_tags'        => ['productivity', 'internal'],
 *         'app_sort'        => 50, // lower = shown earlier
 *     ];
 *     return $apps;
 * });
 */

if (!function_exists('apps_hub_collect_apps')) {
    /**
     * Collect apps for the Apps hub.
     *
     * @param array $baseApps
     * @return array
     */
    function apps_hub_collect_apps(array $baseApps = []): array
    {
        // Allow modules to register their own apps via hook
        if (function_exists('hooks')) {
            $baseApps = hooks()->apply_filters('apps_hub_register', $baseApps);
        }

        // Normalize and default values
        foreach ($baseApps as &$app) {
            // Slug
            if (empty($app['app_slug']) && !empty($app['slug'])) {
                $app['app_slug'] = $app['slug'];
            }

            // Name
            if (empty($app['app_name']) && !empty($app['name'])) {
                $app['app_name'] = $app['name'];
            }

            // Description
            if (empty($app['app_description']) && !empty($app['description'])) {
                $app['app_description'] = $app['description'];
            }

            // Icon
            if (empty($app['app_icon']) && !empty($app['icon'])) {
                $app['app_icon'] = $app['icon'];
            }

            // View link / URL
            if (empty($app['app_view_link']) && !empty($app['url'])) {
                $app['app_view_link'] = $app['url'];
            }

            // Sort order
            $app['app_sort'] = isset($app['app_sort'])
                ? (int)$app['app_sort']
                : 100; // default sort at bottom
        }
        unset($app);

        // Sort by app_sort ASC then name
        usort($baseApps, function ($a, $b) {
            $sa = (int)($a['app_sort'] ?? 100);
            $sb = (int)($b['app_sort'] ?? 100);
            if ($sa === $sb) {
                $na = strtolower($a['app_name'] ?? '');
                $nb = strtolower($b['app_name'] ?? '');
                return strcmp($na, $nb);
            }
            return $sa <=> $sb;
        });

        return $baseApps;
    }
}

/**
 * 1) Dummy apps – purely for UI demo
 *    (Controller does not need to pass anything)
 */
$baseApps = [
    [
        'app_slug'        => 'notepad',
        'app_name'        => 'Personal Notepad',
        'app_description' => 'Capture quick notes, ideas, and to-dos in a distraction-free workspace.',
        'app_icon'        => 'ti ti-notes',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('apps/notepad'),
        'app_tags'        => ['notes', 'personal', 'quick'],
        'app_sort'        => 10,
    ],
    [
        'app_slug'        => 'reminders',
        'app_name'        => 'Reminders',
        'app_description' => 'Schedule reminders so you never miss key tasks, follow-ups, or approvals.',
        'app_icon'        => 'ti ti-bell',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('reminders'),
        'app_tags'        => ['tasks', 'alerts', 'follow-up'],
        'app_sort'        => 20,
    ],
    [
        'app_slug'        => 'signoff',
        'app_name'        => 'Daily Signoff',
        'app_description' => 'Submit daily signoff forms, KPIs, and performance checkpoints in one place.',
        'app_icon'        => 'ti ti-checkup-list',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('signoff'),
        'app_tags'        => ['kpi', 'daily', 'performance'],
        'app_sort'        => 30,
    ],
    [
        'app_slug'        => 'attendance',
        'app_name'        => 'Attendance & Leaves',
        'app_description' => 'Track attendance, leaves, and working hours with centralized visibility.',
        'app_icon'        => 'ti ti-calendar-time',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('attendance'),
        'app_tags'        => ['hr', 'time', 'leaves'],
        'app_sort'        => 40,
    ],
    [
        'app_slug'        => 'payroll',
        'app_name'        => 'Payroll',
        'app_description' => 'Run payroll with allowances, deductions, and salary insights for your team.',
        'app_icon'        => 'ti ti-cash',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('payroll'),
        'app_tags'        => ['finance', 'salary', 'hr'],
        'app_sort'        => 50,
    ],
    [
        'app_slug'        => 'projects',
        'app_name'        => 'Projects',
        'app_description' => 'Plan, track, and deliver projects with timelines, files, and stakeholders.',
        'app_icon'        => 'ti ti-layout-kanban',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('projects'),
        'app_tags'        => ['projects', 'work', 'teams'],
        'app_sort'        => 60,
    ],
    [
        'app_slug'        => 'tasks',
        'app_name'        => 'Tasks',
        'app_description' => 'Organize tasks, owners, and status updates in a structured task board.',
        'app_icon'        => 'ti ti-list-check',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('tasks'),
        'app_tags'        => ['tasks', 'work', 'productivity'],
        'app_sort'        => 70,
    ],
    [
        'app_slug'        => 'support',
        'app_name'        => 'Support Desk',
        'app_description' => 'Centralize tickets, SLAs, and resolutions for internal and external support.',
        'app_icon'        => 'ti ti-life-buoy',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('support'),
        'app_tags'        => ['support', 'tickets', 'sla'],
        'app_sort'        => 80,
    ],
    [
        'app_slug'        => 'subscriptions',
        'app_name'        => 'Subscriptions',
        'app_description' => 'Monitor domains, hosting, and recurring subscriptions with renewal alerts.',
        'app_icon'        => 'ti ti-repeat',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('subscriptions'),
        'app_tags'        => ['billing', 'renewals', 'assets'],
        'app_sort'        => 90,
    ],
    [
        'app_slug'        => 'teamchat',
        'app_name'        => 'Team Chat',
        'app_description' => 'Real-time team messaging with groups, file sharing, and read receipts.',
        'app_icon'        => 'ti ti-brand-hipchat',
        'app_image'       => '',
        'app_author'      => 'RCM Centric',
        'app_view_link'   => site_url('teamchat'),
        'app_tags'        => ['chat', 'communication', 'teams'],
        'app_sort'        => 100,
    ],
];

// 2) Final apps list (dummy + modules via hook)
$apps = apps_hub_collect_apps($baseApps);

/**
 * Small helper for tags
 */
$normalizeTags = function ($tags) {
    if (is_string($tags)) {
        $tags = array_filter(array_map('trim', explode(',', $tags)));
    }
    return is_array($tags) ? $tags : [];
};
?>

<div class="container-fluid apps-hub">

    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex align-items-center small gap-1">
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
                    
        <div class="btn-divider"></div>
        
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search apps..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'appsTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
    
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($apps)) : ?>
        <div class="alert alert-info">
            No apps are registered yet. Use the <code>apps_hub_register</code> hook to add your first app.
        </div>
    <?php else : ?>
        <div class="row g-3">
            <?php foreach ($apps as $app): ?>
                <?php
                    $name        = $app['app_name']        ?? 'Unnamed app';
                    $desc        = $app['app_description'] ?? '';
                    $icon        = $app['app_icon']        ?? 'ti ti-apps';
                    $image       = $app['app_image']       ?? '';
                    $author      = $app['app_author']      ?? '';
                    $viewLink    = $app['app_view_link']   ?? '#';
                    $tags        = $normalizeTags($app['app_tags'] ?? []);
                ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card app-card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title middle text-primary">
                                <i class="<?php echo html_escape($icon); ?> me-2"></i> <?php echo html_escape($name); ?>
                            </h6>

                            <?php if ($author): ?>
                                <div class="text-muted x-small mb-1">
                                    by <?php echo html_escape($author); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($desc): ?>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo html_escape($desc); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($tags)): ?>
                                <div class="mb-2">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="badge bg-light-primary text-muted x-small border me-1 mb-1">
                                            #<?php echo html_escape($tag); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                                <a href="<?php echo $viewLink; ?>" class="btn btn-header btn-light-primary">
                                    Open App <i class="ti ti-external-link"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .apps-hub .app-card__icon {
        width: 48px;
        height: 48px;
        background: rgba(15, 23, 42, 0.04);
    }
    .apps-hub .app-card__image-wrapper {
        max-height: 140px;
        overflow: hidden;
    }
    .apps-hub .app-card__image {
        object-fit: cover;
        width: 100%;
        height: 140px;
    }
</style>
