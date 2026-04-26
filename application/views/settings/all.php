
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>


</style>
<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canEdit    = staff_can('editsystem', 'general');
        ?>

        <a href="<?= $canEdit ? site_url('settings/system_info') : 'javascript:void(0);' ?>"
           class="btn <?= $canEdit ? 'btn-light-primary' : 'btn-disabled' ?> btn-header"
           title="See php current info"
           <?= $canEdit ? '' : 'tabindex="-1" aria-disabled="true" style="pointer-events:none;opacity:.65;"' ?>>
           System Info <i class="fas fa-question me-1"></i>
        </a>
        
        <div class="btn-divider"></div>
        
        <a href="<?= $canEdit ? site_url('admin/setup/company') : 'javascript:void(0);' ?>"
           class="btn <?= $canEdit ? 'btn-primary' : 'btn-disabled' ?> btn-header"
           title="Main Company Setup"
           <?= $canEdit ? '' : 'tabindex="-1" aria-disabled="true" style="pointer-events:none;opacity:.65;"' ?>>
          <i class="ti ti-settings me-1"></i> Main Setup
        </a>

        <a href="<?= $canEdit ? site_url('admin/modules') : 'javascript:void(0);' ?>"
           class="btn <?= $canEdit ? 'btn-primary' : 'btn-disabled' ?> btn-header"
           title="Modules & Plugins"
           <?= $canEdit ? '' : 'tabindex="-1" aria-disabled="true" style="pointer-events:none;opacity:.65;"' ?>>
          <i class="fas fa-box me-1"></i> Modules & Plugins
        </a>
        
      </div>
    </div>

<?php $CI =& get_instance(); ?>
<?php if ($CI->session->flashdata('debug')): ?>
  <div class="alert alert-warning">
    <?= $CI->session->flashdata('debug'); ?>
  </div>
<?php endif; ?>


<div class="row">
  <div class="col-12">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-3">
        <div class="card shadow-sm">
          <div class="card-body p-1">
            <div class="list-group list-group-flush">
              <?php foreach ($sections as $sectionId => $section): ?>
                <div class="mb-0">
                  <div class="px-3 pt-3 pb-1">
                    <span class="badge text-light-primary"><?= e($section['title']); ?></span>
                  </div>
                  <ul class="list-group list-group-flush">
                    <?php foreach ($section['children'] as $child): ?>
                      <li class="list-group-item py-2 px-4 small settings-group-<?= e($child['id']); ?> border-0">
                        <a href="<?= site_url('settings?group=' . $child['id']); ?>"
                           class="d-flex text-secondary align-items-center <?= ($group['id'] === $child['id']) ? 'fw-bold text-primary' : 'text-body'; ?>">
                          <i class="<?= e($child['icon']); ?> me-2 fs-10"></i>
                          <span><?= e($child['name']); ?></span>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <!-- Main Content -->
      <div class="col-md-9">
        <?php
        $actionUrl = $group['update_url']
            ?? site_url($CI->uri->uri_string()) . '?group=' . $group['id']
               . ($CI->input->get('tab') ? '&active_tab=' . $CI->input->get('tab') : '');

        $formAttributes = [
            'id'    => 'settings-form',
            'class' => 'app-form' . isset($group['update_url']) ? 'custom-update-url' : '',
        ];
        echo form_open_multipart($actionUrl, $formAttributes);
        ?>
        <div class="card shadow-sm">
          <div class="card-body">
            <?php
            if (function_exists('hooks') && is_callable('hooks()->do_action')) {
                hooks()->do_action('before_settings_group_view', $group);
            }
            $CI->load->view($group['view']);
            if (function_exists('hooks') && is_callable('hooks()->do_action')) {
                hooks()->do_action('after_settings_group_view', $group);
            }
            ?>
          </div>
          <?php if (empty($group['without_submit_button'])): ?>
            <div class="card-footer text-end bg-white border-0">
            <?php if (staff_can('editsystem', 'general')): ?>
              <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="fas fa-save me-1"></i> Save Settings
              </button>
            <?php else: ?>
              <span title="You do not have permission to update these settings.">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm px-4"
                        disabled
                        tabindex="-1">
                  <i class="fas fa-lock me-1"></i> Save Settings
                </button>
              </span>
            <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <?= form_close(); ?>
      </div>
    </div>
  </div>
</div>
</div>

<div id="new_version"></div>

<script>
    $(function() {
        var settingsForm = $('#settings-form');
        var slug = "<?= e($group['id']); ?>";

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            if (settingsForm.hasClass('custom-update-url')) {
                return;
            }
            var tab = $(this).attr('href').slice(1);
            settingsForm.attr('action',
                '<?= site_url($CI->uri->uri_string()); ?>?group=' +
                slug +
                '&active_tab=' + tab
            );
        });

        <?php if ($group['id'] === 'email'): ?>
            $('input[name="settings[email_protocol]"]').on('change', function() {
                var proto = $(this).val();
                if (proto === 'sendmail') {
                    $('.smtp-fields').addClass('hide');
                } else {
                    $('.smtp-fields').removeClass('hide');
                }
            });
        <?php endif; ?>
    });
</script>
