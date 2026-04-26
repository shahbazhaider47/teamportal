<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h2 class="mb-3">Welcome to the Developers Hub</h2>
<p class="text-muted">
  This knowledge base documents the architecture, extension points, and best practices for building on this platform:
  modules, hooks, routing, controllers/models/views, helpers/libraries, assets, language files, permissions, widgets,
  notifications, and more.
</p>

<div class="row g-3">
  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><i class="ti ti-box"></i> Architecture at a Glance</h5>
        <ul class="small mb-0">
          <li>CodeIgniter 3 + HMVC (MX) modules</li>
          <li>`App_Controller` base for controllers</li>
          <li>Hooks system (`hooks()->add_action`, `hooks()->add_filter`)</li>
          <li>Layout contract: <code>$layout_data</code> → <code>layouts/master</code></li>
          <li>Role/permission checks via <code>staff_can()</code></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><i class="ti ti-puzzle"></i> What You Can Build</h5>
        <ul class="small mb-0">
          <li>Self-contained feature modules</li>
          <li>Dashboard widgets & header shortcuts</li>
          <li>Hooks: extend menus, forms, queries</li>
          <li>Helpers/libraries for shared logic</li>
          <li>Install/Uninstall scripts & migrations</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><i class="ti ti-book"></i> Start Here</h5>
        <ol class="small mb-0">
          <li>Read <a href="<?= site_url('developers/modules') ?>">Modules & Structure</a></li>
          <li>Skim <a href="<?= site_url('developers/hooks') ?>">Hooks</a> (actions/filters)</li>
          <li>Follow <a href="<?= site_url('developers/routing') ?>">Routing & Controllers</a></li>
          <li>Align with <a href="<?= site_url('developers/conventions') ?>">Coding Conventions</a></li>
        </ol>
      </div>
    </div>
  </div>
</div>
