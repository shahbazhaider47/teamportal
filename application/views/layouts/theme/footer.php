<?php
/**
 * layouts/includes/footer.php
 * ─────────────────────────────────────────────────────────────
 * Nexus CRM — Footer partial for CodeIgniter master layout.
 * Loaded at the bottom of .app-main in master.php.
 */
$app_name = defined('APP_NAME') ? html_escape(APP_NAME) : 'Nexus CRM';
$year     = date('Y');
?>

<footer class="app-footer">
  <div class="footer-inner">

    <span class="footer-copy">
      &copy; <?= $year ?> <?= $app_name ?> &mdash; All rights reserved.
    </span>

    <nav class="footer-links" aria-label="Footer links">
      <a href="<?= site_url('docs') ?>"    class="footer-link">Documentation</a>
      <a href="<?= site_url('support') ?>" class="footer-link">Support Center</a>
      <a href="<?= site_url('privacy') ?>" class="footer-link">Privacy Policy</a>
    </nav>

  </div>
</footer>