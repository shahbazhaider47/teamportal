<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h2 class="mb-3">Coding Conventions</h2>
<ul class="small">
  <li>Controllers extend <code>App_Controller</code>; keep them thin</li>
  <li>Business logic lives in Models; views stay presentational</li>
  <li>Escape all output (<code>html_escape</code>, helpers like <code>t_s()</code>)</li>
  <li>Feature flags / settings via options or module settings tabs</li>
  <li>Register permissions per module; gate with <code>staff_can()</code></li>
  <li>Prefer hooks over core edits; add routes & views via module</li>
  <li>AJAX endpoints return JSON; respect CSRF and permission checks</li>
  <li>SQL: use InnoDB, utf8mb4, proper indexes, created_at/updated_at</li>
</ul>
