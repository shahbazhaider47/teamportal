<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h2 class="mb-3">Modules & Structure</h2>
<p class="text-muted">Build fully self-contained features without touching core files.</p>

<div class="alert alert-info small">
  <strong>Convention:</strong> Each module resides under <code>modules/{slug}/</code> with its own controllers, models, views,
  assets, language files, migrations, and an init file that registers hooks/permissions.
</div>

<h3 class="mt-4">Directory Layout</h3>
<pre class="small bg-light p-3 rounded-3 border">
modules/
  mymodule/
    controllers/
      MyModule.php
    models/
      MyModule_model.php
    views/
      manage.php
      modals/
        create_modal.php
    assets/
      mymodule.js
      mymodule.css
    language/
      english/mymodule_lang.php
    migrations/
      001_init.php
    mymodule.php   ← init (metadata + hooks + permissions)
    install.php    ← create tables/data
    uninstall.php  ← drop tables/data
</pre>

<h3 class="mt-4">Module Init (hooks + permissions)</h3>
<pre class="small bg-light p-3 rounded-3 border"><code>&lt;?php defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: My Module
Description: Example feature module showcasing hooks and permissions.
Version: 1.0.0
Author: RCM Centric
*/

hooks()->add_action('app_init', function () {
    // Register language files
    if (function_exists('register_language_files')) {
        register_language_files('mymodule', ['mymodule']);
    }

    // Register permissions
    if (function_exists('register_staff_capability')) {
        register_staff_capability('mymodule', 'view_global');
        register_staff_capability('mymodule', 'create');
        register_staff_capability('mymodule', 'edit');
        register_staff_capability('mymodule', 'delete');
    }

    // Sidebar entry
    hooks()->add_filter('app_sidebar_menu', function ($menus) {
        $menus['mymodule'] = [
            'name' => 'My Module',
            'href' => site_url('mymodule'),
            'icon' => 'ti ti-apps',
            'permission' => 'view_global',
        ];
        return $menus;
    });
});</code></pre>

<h3 class="mt-4">Install / Uninstall</h3>
<pre class="small bg-light p-3 rounded-3 border"><code>&lt;?php // modules/mymodule/install.php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->db->query("CREATE TABLE IF NOT EXISTS `mymodule_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");</code></pre>
