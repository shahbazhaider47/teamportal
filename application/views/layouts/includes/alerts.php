<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Alerts -->
<link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/alerts.css')?>">

<?php $alerts = get_alerts(); ?>
<?php if (!empty($alerts)): ?>
<div id="alert-system" class="alert-system">
  <?php foreach ($alerts as $alert): ?>
    <div class="alert alert--<?= html_escape($alert['type']); ?>"
         role="alert"
         aria-live="assertive"
         aria-atomic="true"
         tabindex="0"
         data-autohide="true"
         data-duration="<?= (int)($alert['duration'] ?? 5000) ?>">

      <div class="alert__content">
        <p class="alert__message"><?= html_escape($alert['message']); ?></p>
      </div>

      <button type="button"
              class="alert__close"
              aria-label="Dismiss notification">
        &times;
      </button>

    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('alert-system');
  if (!container) return;

  container.querySelectorAll('.alert').forEach(alert => {
    const duration = parseInt(alert.dataset.duration || 5000, 10);
    const closeBtn = alert.querySelector('.alert__close');

    let hideTimer;
    let start;
    let remaining = duration;

    const show = () => {
      requestAnimationFrame(() => alert.classList.add('show'));
      startTimer();
    };

    const dismiss = () => {
      clearTimeout(hideTimer);
      alert.classList.remove('show');
      alert.style.transform = 'translateX(120%)';
      setTimeout(() => alert.remove(), 300);
    };

    const startTimer = () => {
      start = performance.now();
      hideTimer = setTimeout(dismiss, remaining);
    };

    const pauseTimer = () => {
      clearTimeout(hideTimer);
      remaining -= performance.now() - start;
    };

    const resumeTimer = () => {
      start = performance.now();
      hideTimer = setTimeout(dismiss, remaining);
    };

    closeBtn.addEventListener('click', dismiss);
    alert.addEventListener('mouseenter', pauseTimer);
    alert.addEventListener('mouseleave', resumeTimer);
    alert.addEventListener('keydown', e => e.key === 'Escape' && dismiss());

    show();
  });
});
</script>