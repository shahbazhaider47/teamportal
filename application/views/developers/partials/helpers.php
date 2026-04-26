<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h2 class="mb-3">Helpers & Libraries</h2>
<p class="text-muted">Put common utilities into helpers (stateless) or libraries (stateful/services).</p>

<h3 class="mt-3">Helper Example</h3>
<pre class="small bg-light p-3 rounded-3 border"><code>&lt;?php // application/helpers/global_helper.php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('t_s')) {
  function t_s($v) { return is_scalar($v) ? html_escape((string)$v) : ''; }
}</code></pre>

<h3 class="mt-3">Library Example</h3>
<pre class="small bg-light p-3 rounded-3 border"><code>&lt;?php // application/libraries/Notifier.php
defined('BASEPATH') or exit('No direct script access allowed');

class Notifier {
  protected $CI;
  public function __construct() { $this->CI =& get_instance(); }
  public function inapp($userId, $message) {
    // ... insert into notifications table, trigger socket, etc.
  }
}</code></pre>

<p class="small text-muted mb-0">Load with <code>$this->load->helper('global');</code> or <code>$this->load->library('Notifier');</code>.</p>
