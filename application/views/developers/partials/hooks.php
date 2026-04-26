<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h2 class="mb-3">Hooks: Actions & Filters</h2>
<p class="text-muted">
  Hooks let you extend core behavior without editing core files. Use <code>add_action</code> to execute side effects,
  and <code>add_filter</code> to modify a value and return it.
</p>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Action Example</h5>
        <pre class="small bg-light p-3 rounded-3 border"><code>hooks()->add_action('user_created', function ($user) {
    log_message('info', 'New user created: '.$user['email']);
});</code></pre>
        <p class="small text-muted mb-0">Fires after a user is created; observe and react.</p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Filter Example</h5>
        <pre class="small bg-light p-3 rounded-3 border"><code>hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
    $items[] = '&lt;div class="app-cell"&gt;...&lt;/div&gt;';
    return $items;
});</code></pre>
        <p class="small text-muted mb-0">Alter a list before it’s rendered.</p>
      </div>
    </div>
  </div>
</div>

<h3 class="mt-4">Common Hook Points</h3>
<ul class="small">
  <li><code>app_init</code> — Modules bootstrap themselves</li>
  <li><code>app_sidebar_menu</code> — Inject menu items</li>
  <li><code>app_shortcut_icons_raw</code> — Add header grid apps</li>
  <li><code>app_footer_modals</code> — Load modals in master layout</li>
  <li><code>dashboard_widgets</code> — Register widgets</li>
  <li><code>user_created</code>, <code>user_deleted</code> — Lifecycle</li>
</ul>
