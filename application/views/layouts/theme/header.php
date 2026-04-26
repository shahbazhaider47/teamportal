<?php
/**
 * layouts/includes/header.php
 * ─────────────────────────────────────────────────────────────
 * Nexus CRM — Page header partial for CodeIgniter master layout.
 * Loaded inside .app-content, above <main>, in master.php.
 *
 * Available CI view data (set in controller or sub-view data):
 *   $page_title      string   Main heading text
 *   $page_subtitle   string   Optional sub-heading / description
 *   $breadcrumbs     array    [['label' => 'Home', 'url' => '/'], ...]
 *                             Last item is treated as current page (no url needed)
 *   $header_actions  string   Raw HTML for action buttons (optional)
 *                             — or pass individual button arrays if preferred
 */

// Safe defaults
$page_title     = isset($page_title)    ? html_escape($page_title)    : 'Dashboard';
$page_subtitle  = isset($page_subtitle) ? html_escape($page_subtitle) : '';
$breadcrumbs    = isset($breadcrumbs)   ? $breadcrumbs                : [];
$header_actions = isset($header_actions)? $header_actions             : '';
?>

<header class="page-header">

  <div class="page-header-left">

    <!-- Breadcrumb -->
    <?php if (!empty($breadcrumbs)): ?>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $i => $crumb):
          $is_last = ($i === count($breadcrumbs) - 1);
        ?>
          <?php if ($i > 0): ?>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
          <?php endif; ?>

          <span class="breadcrumb-item <?= $is_last ? 'current' : '' ?>"
                <?= $is_last ? 'aria-current="page"' : '' ?>>
            <?php if (!$is_last && !empty($crumb['url'])): ?>
              <a href="<?= site_url($crumb['url']) ?>"><?= html_escape($crumb['label']) ?></a>
            <?php else: ?>
              <?= html_escape($crumb['label']) ?>
            <?php endif; ?>
          </span>

        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <!-- Page title -->
    <h1 class="page-title"><?= $page_title ?></h1>

    <?php if ($page_subtitle): ?>
      <p class="page-subtitle"><?= $page_subtitle ?></p>
    <?php endif; ?>

  </div><!-- /page-header-left -->

  <!-- Header action buttons (injected by controller/sub-view) -->
  <?php if ($header_actions): ?>
    <div class="page-header-actions">
      <?= $header_actions ?>
    </div>
  <?php endif; ?>

</header><!-- /page-header -->