<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$CI = &get_instance();
$modules = $modules ?? [];

/**
 * Helper: resolve a brand image URL for a module, plus a universal fallback.
 * Priority:
 *  1) $headers['brand_image'] (absolute or relative)
 *  2) /modules/<system_name>/assets/images/brand.png (convention)
 *  3) Fallback: /assets/images/default-module-brand.jpg
 */
function module_brand_image_url($system_name, $headers)
{
    $fallback = base_url('assets/images/default-module-brand.jpg');

    $img = $headers['brand_image'] ?? '';
    if (!empty($img)) {
        if (preg_match('#^https?://#i', $img)) {
            return [$img, $fallback];
        }
        return [base_url(ltrim($img, '/')), $fallback];
    }

    // Conventional path inside module
    $conventional = 'modules/' . $system_name . '/assets/images/brand.png';
    return [base_url($conventional), $fallback];
}

?>

<style>
/* Scoped marketplace styling */
.modules-marketplace .card {
  border: 1px solid rgba(0,0,0,.06);
  border-radius: 1rem;
  overflow: hidden;
  transition: transform .12s ease, box-shadow .12s ease;
}
.modules-marketplace .card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0,0,0,.08);
}
.modules-marketplace .brand-wrap {
  position: relative;
  background: linear-gradient(180deg, rgba(0,0,0,.04), rgba(0,0,0,.02));
  /* Standardized stage: consistent across all cards */
  aspect-ratio: auto;   /* change to 3/1 if you prefer wider banners */
  min-height: auto;       /* governed by aspect-ratio */
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0px;          /* breathing room */
}

.modules-marketplace .brand-wrap img {
  /* Never overflow, never distort */
  max-width: 100%;
  max-height: 80%;
  width: auto;
  height: auto;
  border-radius: 5px;
  object-fit: contain;
}

.module-badge{
    font-size: 8px;
    border-radius: 2px;
}

.modules-marketplace .status-ribbon {
  position: absolute; top: 5px; left: 5px;
}
.modules-marketplace .status-ribbon .badge {
  box-shadow: 0 2px 6px rgba(0,0,0,.12);
  border: 1px solid white;
  font-size: 9px;
  font-weight: 400;
}
.modules-marketplace .module-title {
  font-weight: 600;
  margin-bottom: .25rem;
}
.modules-marketplace .module-desc {
  min-height: 60px;
  font-size: 11px;

}
.modules-marketplace .meta-chips .badge,
.modules-marketplace .meta-chips .tw-inline-block {
  margin-right: .375rem;
  margin-bottom: .375rem;
}
.modules-marketplace .actions .btn {
  padding: .375rem .6rem;
}
.modules-marketplace .avatar-fallback {
  width: 96px; height: 96px;
  border-radius: 16px;
  background: #F3F4F6;
  display: inline-flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 28px; color: #6B7280;
}

</style>

<div class="container-fluid modules-marketplace">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <!-- Left: Title -->
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-1"><?= e($page_title ?? 'Modules') ?></h1>
    </div>

    <!-- Right: Controls -->
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
      <!-- Inline upload + install -->
      <?= form_open_multipart(base_url('admin/Mods/upload'), [
          'id'    => 'module_install_form',
          'class' => 'd-flex align-items-center gap-2'
      ]); ?>
        <div class="input-group input-group-sm app-form" style="max-width: 600px;">
          <input
            type="file"
            id="module_upload"
            name="module"
            class="form-control"
            accept=".zip"
            aria-describedby="module_upload_help"
          >
          <button type="submit" class="btn btn-primary btn-header">
            <i class="fa fa-box-open me-1"></i><?= _l('upload_install'); ?>
          </button>
        </div>
      <?= form_close(); ?>

      <div class="btn-divider"></div>

      <?php
        $canExport = staff_can('export', 'general');
        $canPrint  = staff_can('print', 'general');
      ?>

      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table btn-sm"
                title="Export list"
                data-export-filename="<?= e($page_title ?? 'modules_export') ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-print-table btn-sm"
                title="Print">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Marketplace Grid -->
<div class="card">
 <div class="card-body">      
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 p-3">
    <?php foreach ($modules as $module): ?>
      <?php
        $system_name  = $module['system_name'];
        $activated    = !empty($module['activated']) ? 1 : 0;
        $headers      = $module['headers'] ?? [];
        $db_upgrade   = $CI->app_modules->is_database_upgrade_required($system_name);
        $versionOK    = $CI->app_modules->is_minimum_version_requirement_met($system_name);
        $newVersion   = $CI->app_modules->new_version_available($system_name);

        // Action links (we'll convert to Buttons below)
        $action_links = hooks()->apply_filters("module_{$system_name}_action_links", []);

        $canActivate   = ($activated === 0 && $versionOK);
        $canDeactivate = ($activated === 1);
        $canUpgradeDB  = $db_upgrade;
        $canUninstall  = ($activated === 0 && !in_array($system_name, uninstallable_modules()));
        $canUpdate     = $CI->app_modules->is_update_handler_available($system_name);

        $brandUrl      = module_brand_image_url($system_name, $headers);
        list($brandUrl, $fallbackUrl) = module_brand_image_url($system_name, $headers);
        $moduleName    = e($headers['module_name'] ?? ucfirst($system_name));
        $desc          = e($headers['description'] ?? '');
        $version       = $headers['version'] ?? null;
        $author        = $headers['author'] ?? null;
        $authorUri     = $headers['author_uri'] ?? null;
        $requiresAt    = $headers['requires_at_least'] ?? null;
        $requiresMods  = $headers['requires_modules'] ?? null;
        $uri           = $headers['uri'] ?? null;

        // Prebuild URLs
        $urlActivate   = base_url('admin/Mods/activate/' . $system_name);
        $urlDeactivate = base_url('admin/Mods/deactivate/' . $system_name);
        $urlUpgradeDB  = base_url('admin/Mods/upgrade_database/' . $system_name);
        $urlUninstall  = base_url('admin/Mods/uninstall/' . $system_name);
        $urlUpdate     = base_url('admin/Mods/update_version/' . $system_name);

        // Release notes link from newVersion data
        $releaseNotes  = !empty($newVersion['changelog']) ? $newVersion['changelog'] : null;

        // Avatar initials (fallback)
        $initials = strtoupper(substr($system_name, 0, 2));
      ?>
      <div class="col">
        <div class="card">
          <div class="brand-wrap">
                <span class="status-ribbon">
                  <?php if ((int)$activated !== 1): ?>
                    <span class="badge bg-danger">In-Active</span>
                
                  <?php else: ?>
                    <?php if (!empty($db_upgrade)): ?>
                      <span class="badge bg-warning text-dark">DB Upgrade</span>
                    <?php endif; ?>
                
                    <?php if (!empty($newVersion)): ?>
                      <span class="badge bg-info text-dark">Update Available</span>
                    <?php endif; ?>
                
                    <?php if (empty($db_upgrade) && empty($newVersion)): ?>
                      <span class="badge bg-success">Active</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </span>

            <img
              src="<?= $brandUrl ?>"
              alt="<?= $moduleName ?>"
              onerror="if(!this.dataset.fallback){this.dataset.fallback=1;this.src='<?= $fallbackUrl ?>';}else{this.style.display='none';}"
            />
            
          </div>

          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="module-title"><?= $moduleName ?></div>
              </div>

              <?php if (!$versionOK): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger">Min. Version Not Met</span>
              <?php endif; ?>
            </div>

            <?php if (!empty($desc)): ?>
              <p class="text-muted small module-desc mt-2 mb-2"><?= $desc ?></p>
            <?php endif; ?>

            <div class="meta-chips mb-3">
              <?php if (!empty($version)): ?>
                <span class="badge text-light-primary module-badge">v<?= e($version) ?></span>
              <?php endif; ?>

              <?php if (!empty($author)): ?>
                <span class="badge text-light-info module-badge">
                  <?= _l('module_by'); ?>
                  <?php if (!empty($authorUri)): ?>
                    <a href="<?= e($authorUri) ?>" class="text-decoration-none" target="_blank"><?= e($author) ?></a>
                  <?php else: ?>
                    <?= e($author) ?>
                  <?php endif; ?>
                </span>
              <?php endif; ?>

              <?php if (!empty($requiresAt)): ?>
                <span class="badge text-light-secondary module-badge">
                  <?= _l('minimum_version'); ?> <?= e($requiresAt) ?>
                </span>
              <?php endif; ?>

              <?php if (!empty($requiresMods)): ?>
                <span class="tw-inline-block tw-bg-gray-100 tw-text-xs tw-px-2 tw-py-1 tw-rounded">
                  <b>Requires:</b> <?= e($requiresMods) ?>
                </span>
              <?php endif; ?>

              <?php if (!empty($uri)): ?>
                <span class="tw-inline-block tw-bg-gray-100 tw-text-xs tw-px-2 tw-py-1 tw-rounded">
                  <b>Module URI:</b> <a href="<?= e($uri) ?>" target="_blank"><?= e($uri) ?></a>
                </span>
              <?php endif; ?>
            </div>

            <?php if (!$versionOK): ?>
              <div class="alert alert-warning py-2 px-3 small mb-3">
                <?= _l('module_requires_version', [e($requiresAt ?? '-'), ($activated === 0) ? _l('module_cannot_be_activated') : '']); ?>
              </div>
            <?php endif; ?>

            <?php if ($newVersion): ?>
              <div class="alert alert-success py-2 px-3 small mb-3">
                There is a new version of <?= $moduleName ?> available<?= !empty($newVersion['version']) ? ' ('.$newVersion['version'].')' : '' ?>.
                <?php if ($releaseNotes): ?>
                  <a href="<?= e($releaseNotes) ?>" target="_blank" class="alert-link">Release Notes</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="mt-auto pt-2">
              <div class="actions d-flex flex-wrap gap-2">
                <?php if ($canActivate): ?>
                  <a href="<?= $urlActivate ?>" class="btn btn-header btn-primary">
                    <i class="ti ti-power me-1"></i><?= _l('module_activate') ?>
                  </a>
                <?php endif; ?>

                <?php if ($canDeactivate): ?>
                  <a href="<?= $urlDeactivate ?>" class="btn btn-header btn-outline-danger">
                    <i class="ti ti-power me-1"></i><?= _l('module_deactivate') ?>
                  </a>
                <?php endif; ?>

                <?php if ($canUpgradeDB): ?>
                  <a href="<?= $urlUpgradeDB ?>" class="btn btn-header btn-success">
                    <i class="ti ti-database-export me-1"></i><?= _l('module_upgrade') ?>
                  </a>
                <?php endif; ?>

                <?php if ($canUninstall): ?>
                  <a href="<?= $urlUninstall ?>" class="btn btn-header btn-outline-danger _delete">
                    <i class="ti ti-trash me-1"></i><?= _l('module_uninstall') ?>
                  </a>
                <?php endif; ?>

                <?php if ($newVersion && $canUpdate): ?>
                  <a href="<?= $urlUpdate ?>" id="update-module-<?= e($system_name) ?>" class="btn btn-header btn-outline-info">
                    <i class="ti ti-refresh me-1"></i>Update
                  </a>
                <?php endif; ?>
              </div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /card -->
      </div><!-- /col -->
    <?php endforeach; ?>
  </div><!-- /grid -->
</div>
</div>
</div><!-- /container -->

<script>
  $(function() {
    appValidateForm($('#module_install_form'), {
      module: { required: true, extension: "zip" }
    });
  });
</script>