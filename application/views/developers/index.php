<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Developers Guide • Single-Page Docs</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- No master layout; minimal embedded styles for a clean, modern look -->
<style>
  :root{
    --bg:#0f172a;--panel:#111827;--muted:#9ca3af;--text:#e5e7eb;--brand:#3b82f6;--line:#1f2937;--ok:#10b981;--warn:#f59e0b;--bad:#ef4444;
    --code-bg:#0b1220;--chip:#1f2937;--card:#0d1324;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{margin:0;background:var(--bg);color:var(--text);font:14px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial}
  a{color:var(--brand);text-decoration:none} a:hover{text-decoration:underline}
  .layout{display:grid;grid-template-columns:280px 1fr;min-height:100vh}
  .sidebar{background:var(--panel);border-right:1px solid var(--line);position:sticky;top:0;height:100vh;overflow:auto}
  .logo{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px}
  .logo .tag{background:var(--chip);color:var(--muted);border:1px solid var(--line);padding:2px 8px;border-radius:999px;font-size:12px}
  .search{padding:12px 16px;border-bottom:1px solid var(--line)}
  .search input{width:100%;background:#0b1020;border:1px solid #26314e;border-radius:8px;padding:10px 12px;color:var(--text)}
  .nav{padding:8px 8px 24px}
  .nav a{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;color:var(--muted)}
  .nav a.active,.nav a:hover{background:#0b1222;color:var(--text)}
  .content{padding:24px 32px}
  .hero{display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;margin-bottom:18px}
  .hero h1{margin:0;font-size:20px}
  .chips{display:flex;gap:8px;flex-wrap:wrap}
  .chip{background:var(--chip);border:1px solid var(--line);color:var(--muted);padding:4px 10px;border-radius:999px;font-size:12px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;margin:14px 0;overflow:hidden}
  .card h3{margin:0;padding:14px 16px;border-bottom:1px solid var(--line);font-size:16px}
  .card .body{padding:16px}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
  pre{background:var(--code-bg);border:1px solid #1c2540;border-radius:10px;padding:12px;overflow:auto;margin:10px 0;position:relative}
  code{font-size:12.5px}
  .copy{position:absolute;top:8px;right:8px;background:#0b1220;border:1px solid #273358;color:#dbe4ff;border-radius:8px;padding:4px 8px;font-size:12px;cursor:pointer}
  h2{margin:28px 0 10px;font-size:18px}
  h3{margin:22px 0 10px;font-size:15px;color:#cbd5e1}
  .kpi{display:flex;gap:12px;flex-wrap:wrap}
  .kpi .k{background:#0b1220;border:1px solid #1c2540;border-radius:10px;padding:10px 12px}
  .k strong{display:block;font-size:16px}
  .table{width:100%;border-collapse:collapse;border:1px solid var(--line);border-radius:10px;overflow:hidden}
  .table th,.table td{border-bottom:1px solid var(--line);padding:8px 10px;text-align:left}
  .notice{background:#0b2020;border:1px solid #1e3a3a;color:#a7f3d0;border-radius:10px;padding:10px 12px}
  .warn{background:#261b00;border:1px solid #3b2a00;color:#ffd37a;border-radius:10px;padding:10px 12px}
  .footer{margin:40px 0 20px;color:var(--muted);font-size:12px}
  .anchor{scroll-margin-top:18px}
  @media (max-width: 980px){ .layout{grid-template-columns:1fr} .sidebar{position:static;height:auto} }
</style>
</head>
<body>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="logo">
      <div><strong>Developers Guide</strong><div class="tag mono">CI3 • HMVC</div></div>
    </div>
    <div class="search"><input id="q" type="search" placeholder="Search sections (title/keywords)"></div>
    <nav class="nav" id="toc">
      <a href="#overview" class="active">Overview</a>
      <a href="#getting-started">Getting Started</a>
      <a href="#modules-structure">Modules & Structure</a>
      <a href="#routing-controllers">Routing & Controllers</a>
      <a href="#models-db">Models & DB</a>
      <a href="#views-assets">Views & Assets</a>
      <a href="#hooks">Hooks</a>
      <a href="#permissions">Permissions & RBAC</a>
      <a href="#helpers-libraries">Helpers & Libraries</a>
      <a href="#migrations-installers">Migrations & Installers</a>
      <a href="#widgets-shortcuts">Widgets & Shortcuts</a>
      <a href="#modals-footer">Modals & Footer Hook</a>
      <a href="#ajax-api">AJAX & APIs</a>
      <a href="#conventions">Coding Conventions</a>
      <a href="#testing-debug">Testing & Debugging</a>
      <a href="#faq">FAQ</a>
    </nav>
  </aside>

  <!-- Content -->
  <main class="content">
    <div class="hero">
      <h1>Engineering Documentation Hub</h1>
      <div class="chips">
        <span class="chip mono">CodeIgniter 3</span>
        <span class="chip mono">HMVC (MX)</span>
        <span class="chip mono">App_Controller</span>
        <span class="chip mono">Hooks</span>
        <span class="chip mono">Permissions</span>
      </div>
    </div>

    <div class="kpi">
      <div class="k"><strong>0 edits to core</strong><span class="muted">Use modules & hooks</span></div>
      <div class="k"><strong>1 install script</strong><span class="muted">Create/drop tables</span></div>
      <div class="k"><strong>Single-page docs</strong><span class="muted">Anchors & copy buttons</span></div>
    </div>

    <!-- Overview -->
    <section id="overview" class="anchor">
      <h2>Overview</h2>
      <div class="card">
        <h3>What this covers</h3>
        <div class="body">
          <ul>
            <li>How to create **self-contained modules** (controllers, models, views, assets, language, migrations)</li>
            <li>How to extend UI & behavior via **Hooks** (actions/filters) without touching core files</li>
            <li>Patterns for **routing**, **RBAC**, **AJAX**, **widgets**, **modals**, and **installers**</li>
          </ul>
          <p class="notice">Tip: Keep controllers thin; push logic into models and helpers. Prefer hooks over core edits.</p>
        </div>
      </div>
    </section>

    <!-- Getting Started -->
    <section id="getting-started" class="anchor">
      <h2>Getting Started</h2>
      <div class="two">
        <div class="card">
          <h3>Minimal Module Skeleton</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;?php // modules/sample/sample.php (init)
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Sample
Description: Example starter module.
Version: 1.0.0
Author: RCM Centric
*/
hooks()->add_action('app_init', function () {
  if (function_exists('register_language_files')) {
    register_language_files('sample', ['sample']);
  }
  if (function_exists('register_staff_capability')) {
    register_staff_capability('sample', 'view_global');
    register_staff_capability('sample', 'create');
    register_staff_capability('sample', 'edit');
    register_staff_capability('sample', 'delete');
  }
  hooks()->add_filter('app_sidebar_menu', function ($menus) {
    $menus['sample'] = [
      'name' =&gt; 'Sample',
      'href' =&gt; site_url('sample'),
      'icon' =&gt; 'ti ti-apps',
      'permission' =&gt; 'view_global',
    ];
    return $menus;
  });
});</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>Routes & Controller</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">// application/config/routes.php
$route['sample'] = 'sample/index';

// modules/sample/controllers/Sample.php
&lt;?php defined('BASEPATH') or exit('No direct script access allowed');
class Sample extends App_Controller {
  public function __construct(){ parent::__construct(); $this-&gt;load-&gt;model('sample/Sample_model','m'); }
  public function index(){
    $rows = $this-&gt;m-&gt;list();
    // Use master or render a direct view
    $this-&gt;load-&gt;view('sample/manage', compact('rows'));
  }
}</code></pre>
          </div>
        </div>
      </div>
    </section>

    <!-- Modules & Structure -->
    <section id="modules-structure" class="anchor">
      <h2>Modules & Structure</h2>
      <div class="card">
        <h3>Directory Layout</h3>
        <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">modules/
  mymodule/
    controllers/ MyModule.php
    models/      MyModule_model.php
    views/       manage.php
    assets/      mymodule.js, mymodule.css
    language/    english/mymodule_lang.php
    migrations/  001_init.php
    mymodule.php        // init metadata + hooks
    install.php         // create tables
    uninstall.php       // drop tables</code></pre>
          <p class="warn">Don’t modify core files. All UI injections should be done via hooks.</p>
        </div>
      </div>
    </section>

    <!-- Routing & Controllers -->
    <section id="routing-controllers" class="anchor">
      <h2>Routing & Controllers</h2>
      <div class="two">
        <div class="card">
          <h3>Routes</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">// application/config/routes.php
$route['mymodule'] = 'mymodule/index';
$route['mymodule/view/(:num)'] = 'mymodule/view/$1';</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>Controller Skeleton</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;?php defined('BASEPATH') or exit('No direct script access allowed');
class MyModule extends App_Controller {
  public function __construct(){
    parent::__construct();
    $this-&gt;load-&gt;model('mymodule/MyModule_model','mm');
    $this-&gt;load-&gt;helper(['url','form']);
  }
  public function index(){
    $rows = $this-&gt;mm-&gt;list();
    $this-&gt;load-&gt;view('mymodule/manage', compact('rows'));
  }
}</code></pre>
          </div>
        </div>
      </div>
    </section>

    <!-- Models & DB -->
    <section id="models-db" class="anchor">
      <h2>Models & Database</h2>
      <div class="two">
        <div class="card">
          <h3>Model Template</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;?php defined('BASEPATH') or exit('No direct script access allowed');
class MyModule_model extends CI_Model {
  protected $table = 'mymodule_items';
  public function list(){ return $this-&gt;db-&gt;order_by('id','DESC')-&gt;get($this-&gt;table)-&gt;result_array(); }
  public function get($id){ return $this-&gt;db-&gt;get_where($this-&gt;table, ['id'=>(int)$id])-&gt;row_array(); }
  public function create($data){ $this-&gt;db-&gt;insert($this-&gt;table,$data); return (int)$this-&gt;db-&gt;insert_id(); }
  public function update($id,$data){ return $this-&gt;db-&gt;where('id',(int)$id)-&gt;update($this-&gt;table,$data); }
  public function delete($id){ return $this-&gt;db-&gt;delete($this-&gt;table,['id'=>(int)$id]); }
}</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>DDL Example</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">CREATE TABLE IF NOT EXISTS `mymodule_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 0,
  `meta` JSON NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</code></pre>
          </div>
        </div>
      </div>
    </section>

    <!-- Views & Assets -->
    <section id="views-assets" class="anchor">
      <h2>Views & Assets</h2>
      <div class="card">
        <h3>View + Assets</h3>
        <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;!-- modules/mymodule/views/manage.php -->
&lt;div class="container-fluid">
  &lt;h1 class="h5">My Module&lt;/h1>
  &lt;table class="table table-sm">
    &lt;thead>&lt;tr>&lt;th>ID&lt;/th>&lt;th>Title&lt;/th>&lt;th>Status&lt;/th>&lt;/tr>&lt;/thead>
    &lt;tbody>
      &lt;?php foreach (($rows ?? []) as $r): ?&gt;
        &lt;tr>&lt;td>&lt;?= (int)$r['id'] ?&gt;&lt;/td>&lt;td>&lt;?= html_escape($r['title']) ?&gt;&lt;/td>&lt;td>&lt;?= (int)$r['status'] ?&gt;&lt;/td>&lt;/tr>
      &lt;?php endforeach; ?&gt;
    &lt;/tbody>
  &lt;/table>
&lt;/div></code></pre>
          <p class="notice">Use <code>html_escape()</code> or safe helpers for all outputs.</p>
        </div>
      </div>
    </section>

    <!-- Hooks -->
    <section id="hooks" class="anchor">
      <h2>Hooks</h2>
      <div class="two">
        <div class="card">
          <h3>Action</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">hooks()->add_action('user_created', function ($user) {
  log_message('info', 'New user: '.$user['email']);
});</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>Filter</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
  $items[] = '&lt;div class="app-cell"&gt;...&lt;/div&gt;';
  return $items;
});</code></pre>
          </div>
        </div>
      </div>
      <div class="card">
        <h3>Common Hook Points</h3>
        <div class="body">
          <ul>
            <li><code>app_init</code>, <code>app_sidebar_menu</code>, <code>app_shortcut_icons_raw</code></li>
            <li><code>app_footer_modals</code> (see “Modals & Footer Hook”)</li>
            <li><code>dashboard_widgets</code>, <code>user_created</code>, <code>user_deleted</code></li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Permissions -->
    <section id="permissions" class="anchor">
      <h2>Permissions & RBAC</h2>
      <div class="two">
        <div class="card">
          <h3>Register Capabilities</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">hooks()->add_action('app_init', function () {
  register_staff_capability('support', 'view_global');
  register_staff_capability('support', 'view_own');
  register_staff_capability('support', 'create');
  register_staff_capability('support', 'edit');
  register_staff_capability('support', 'delete');
});</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>Gate in Controllers/Views</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">if (!staff_can('view_global', 'support') &amp;&amp; !staff_can('view_own', 'support')) {
  show_404(); // or render 403
}
$canCreate = staff_can('create','support');</code></pre>
          </div>
        </div>
      </div>
    </section>

    <!-- Helpers & Libraries -->
    <section id="helpers-libraries" class="anchor">
      <h2>Helpers & Libraries</h2>
      <div class="two">
        <div class="card">
          <h3>Helper</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;?php // application/helpers/global_helper.php
if (!function_exists('t_s')) {
  function t_s($v){ return is_scalar($v) ? html_escape((string)$v) : ''; }
}</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>Library</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;?php // application/libraries/Notifier.php
class Notifier {
  protected $CI; public function __construct(){ $this-&gt;CI =& get_instance(); }
  public function inapp($userId,$message){ /* insert + socket emit ... */ }
}</code></pre>
          </div>
        </div>
      </div>
    </section>

    <!-- Migrations & Installers -->
    <section id="migrations-installers" class="anchor">
      <h2>Migrations & Installers</h2>
      <div class="card">
        <h3>Install / Uninstall</h3>
        <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">&lt;?php // modules/mymodule/install.php
$CI = &get_instance();
$CI-&gt;db-&gt;query("CREATE TABLE IF NOT EXISTS `mymodule_items` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `title` VARCHAR(191) NOT NULL,
 `status` TINYINT(1) NOT NULL DEFAULT 0,
 `created_at` DATETIME NULL,
 `updated_at` DATETIME NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// modules/mymodule/uninstall.php
$CI-&gt;db-&gt;query("DROP TABLE IF EXISTS `mymodule_items`;");</code></pre>
          <p class="notice">If you use versioned migrations, keep each change incremental and idempotent.</p>
        </div>
      </div>
    </section>

    <!-- Widgets & Shortcuts -->
    <section id="widgets-shortcuts" class="anchor">
      <h2>Widgets & Shortcuts</h2>
      <div class="two">
        <div class="card">
          <h3>Dashboard Widget</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">hooks()->add_filter('dashboard_widgets', function ($widgets) {
  $widgets[] = [
    'id' =&gt; 'sample_widget',
    'title' =&gt; 'Sample Stats',
    'permission' =&gt; 'view_global',
    'view' =&gt; 'mymodule/widgets/sample_stats', // your view path
  ];
  return $widgets;
});</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>Header Shortcut Tile</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
  $items[] = '&lt;div class="app-cell"&gt;
    &lt;a href="'.html_escape(site_url('developers')).'" class="app-tile" title="Developers Docs"&gt;
      &lt;span class="app-icon"&gt;&lt;i class="ti ti-book"&gt;&lt;/i&gt;&lt;/span&gt;
      &lt;div class="app-label"&gt;Developers&lt;/div&gt;
    &lt;/a&gt;
  &lt;/div&gt;';
  return $items;
});</code></pre>
          </div>
        </div>
      </div>
    </section>

    <!-- Modals & Footer Hook -->
    <section id="modals-footer" class="anchor">
      <h2>Modals & Footer Hook</h2>
      <div class="card">
        <h3>Inject Modals (no master edits)</h3>
        <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">hooks()->add_filter('app_footer_modals', function ($views) {
  $views[] = 'reminders/modals/add_reminder_modal';
  // Or with data: $views[] = ['view' =&gt; 'my_module/modals/x', 'data' =&gt; ['foo' =&gt; 'bar']];
  return $views;
});</code></pre>
          <p class="notice">Your <code>master.php</code> already calls <code>app_footer_modals()</code> to load all registered modal views.</p>
        </div>
      </div>
    </section>

    <!-- AJAX & APIs -->
    <section id="ajax-api" class="anchor">
      <h2>AJAX & APIs</h2>
      <div class="two">
        <div class="card">
          <h3>Controller JSON Endpoint</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">public function list_json(){
  if (!$this-&gt;input-&gt;is_ajax_request()) show_404();
  if (function_exists('staff_can') &amp;&amp; !staff_can('view_global','mymodule')) {
    $this-&gt;output-&gt;set_status_header(403)-&gt;set_content_type('application/json')
      -&gt;set_output(json_encode(['status'=>'error','message'=>'Forbidden'])); return;
  }
  $rows = $this-&gt;mm-&gt;list();
  $this-&gt;output-&gt;set_content_type('application/json')-&gt;set_output(json_encode([
    'status' =&gt; 'ok', 'data' =&gt; $rows
  ]));
}</code></pre>
          </div>
        </div>
        <div class="card">
          <h3>JS Fetch</h3>
          <div class="body">
<pre><button class="copy" data-copy>Copy</button><code class="mono">fetch('<?= site_url('mymodule/list_json') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
  .then(r =&gt; r.json())
  .then(({status,data}) =&gt; { if(status==='ok'){ console.log(data); } });</code></pre>
          </div>
        </div>
      </div>
      <p class="warn">Respect CSRF if enabled; include token in headers/body.</p>
    </section>

    <!-- Coding Conventions -->
    <section id="conventions" class="anchor">
      <h2>Coding Conventions</h2>
      <div class="card">
        <h3>Checklist</h3>
        <div class="body">
          <ul>
            <li>Controllers extend <code>App_Controller</code>; keep thin</li>
            <li>Models contain business logic and DB queries</li>
            <li>Escape output (<code>html_escape()</code> or safe helpers)</li>
            <li>Use hooks to extend menus, widgets, modals—no core edits</li>
            <li>SQL: InnoDB, utf8mb4, proper indexes, timestamps</li>
            <li>Permissions: register per module; gate in controllers/views</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Testing & Debugging -->
    <section id="testing-debug" class="anchor">
      <h2>Testing & Debugging</h2>
      <div class="card">
        <h3>Basics</h3>
        <div class="body">
          <ul>
            <li>Enable logs: <code>application/config/config.php</code> → <code>$config['log_threshold']</code></li>
            <li>Use <code>log_message('debug', '...')</code> in controllers/models</li>
            <li>Prefer stubs/fakes in models to isolate DB in unit tests</li>
            <li>Validate input on both client and server</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="anchor">
      <h2>FAQ</h2>
      <div class="card">
        <h3>Common Questions</h3>
        <div class="body">
          <table class="table">
            <thead><tr><th>Question</th><th>Answer</th></tr></thead>
            <tbody>
              <tr><td>How do I add a menu item?</td><td>Use <code>app_sidebar_menu</code> filter in your module init.</td></tr>
              <tr><td>How do I ship DB schema?</td><td>Place SQL in <code>install.php</code> and clean up in <code>uninstall.php</code>.</td></tr>
              <tr><td>Can I add modals globally?</td><td>Yes—hook into <code>app_footer_modals</code> with your modal views.</td></tr>
              <tr><td>How do I guard routes?</td><td>Use <code>staff_can()</code> in controllers and views.</td></tr>
            </tbody>
          </table>
          <p class="footer">References: <a href="https://codeigniter.com/user_guide/" target="_blank" rel="noopener">CodeIgniter 3 User Guide</a> · HMVC/MX patterns</p>
        </div>
      </div>
    </section>

    <div class="footer">© <?= date('Y') ?> Developers Guide — Single Page</div>
  </main>
</div>

<!-- Minimal JS: active link, smooth scroll, search, copy buttons -->
<script>
(function(){
  // Smooth scroll + active state
  const links = Array.from(document.querySelectorAll('#toc a'));
  const sections = links.map(a => document.querySelector(a.getAttribute('href')));
  function onScroll(){
    const y = window.scrollY + 120;
    let active = 0;
    for (let i=0;i<sections.length;i++){
      const el = sections[i]; if(!el) continue;
      const top = el.offsetTop;
      if (y >= top) active = i;
    }
    links.forEach((a,i)=>a.classList.toggle('active', i===active));
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  links.forEach(a=>{
    a.addEventListener('click', e=>{
      e.preventDefault();
      const t = document.querySelector(a.getAttribute('href'));
      if (t) window.scrollTo({top:t.offsetTop-10, behavior:'smooth'});
    });
  });
  onScroll();

  // Search (quick filter by section titles + keywords)
  const q = document.getElementById('q');
  q && q.addEventListener('input', function(){
    const needle = (this.value||'').toLowerCase().trim();
    links.forEach(a=>{
      const text = (a.textContent||'').toLowerCase();
      const href = a.getAttribute('href').slice(1);
      const sec = document.getElementById(href);
      const keywords = (sec ? (sec.getAttribute('data-key')||'') : '').toLowerCase();
      const ok = !needle || text.includes(needle) || keywords.includes(needle);
      a.style.display = ok ? '' : 'none';
    });
  });

  // Copy buttons for code blocks
  document.querySelectorAll('pre .copy').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const code = btn.parentElement.querySelector('code').innerText;
      navigator.clipboard.writeText(code).then(()=>{
        const old = btn.textContent;
        btn.textContent = 'Copied';
        setTimeout(()=>btn.textContent=old,900);
      });
    });
  });
})();
</script>
</body>
</html>
