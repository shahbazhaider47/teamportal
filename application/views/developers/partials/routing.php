<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h2 class="mb-3">Routing & Controllers</h2>
<p class="text-muted">Keep routes declarative and controllers thin; push data logic to models.</p>

<h3 class="mt-3">Routes</h3>
<pre class="small bg-light p-3 rounded-3 border"><code>// application/config/routes.php
$route['mymodule'] = 'mymodule/index';
$route['mymodule/view/(:num)'] = 'mymodule/view/$1';</code></pre>

<h3 class="mt-3">Controller Skeleton</h3>
<pre class="small bg-light p-3 rounded-3 border"><code>&lt;?php defined('BASEPATH') or exit('No direct script access allowed');

class MyModule extends App_Controller
{
  public function __construct() {
    parent::__construct();
    $this->load->model('mymodule/MyModule_model', 'mm');
  }

  public function index() {
    $rows = $this->mm->list();
    $layout_data = [
      'page_title' => 'My Module',
      'subview'    => 'mymodule/manage',
      'view_data'  => compact('rows'),
    ];
    $this->load->view('layouts/master', $layout_data);
  }
}</code></pre>
