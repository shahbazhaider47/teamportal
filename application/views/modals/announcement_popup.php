<!-- Global Auto-Popup Announcement Modal (for dismiss tracking) -->
<div class="modal fade" id="autoAnnouncementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">New Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="autoAnnouncementBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Optional: explicit Dismiss button (does the same as closing) -->
        <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">
          Dismiss
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  if (window.NO_GLOBAL_ANNOUNCEMENT_POPUP) return;

  // If Bootstrap isn't ready, abort quietly
  if (typeof bootstrap === 'undefined' || !('Modal' in bootstrap)) return;

  const modalEl   = document.getElementById('autoAnnouncementModal');
  const modalBody = document.getElementById('autoAnnouncementBody');
  if (!modalEl || !modalBody) return;

  const VIEW_URL    = "<?= site_url('announcements/popup_latest') ?>";
  const DISMISS_URL = "<?= site_url('announcements/dismiss') ?>";

  function spinner() {
    modalBody.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>`;
  }

  function show(json) {
    modalBody.innerHTML = json.html || '<div class="alert alert-warning mb-0">No content.</div>';

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();

    // Mark dismissed once, and clean up any stray backdrops/body flags
    function onClose() {
      try {
        fetch(DISMISS_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'announcement_id=' + encodeURIComponent(json.id)
        });
      } catch (_) {}

      // Cleanup in case multiple modals/backdrops got out of sync
      if (!document.querySelector('.modal.show')) {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
      }

      modalEl.removeEventListener('hidden.bs.modal', onClose);
    }

    modalEl.addEventListener('hidden.bs.modal', onClose, { once: true });
  }

  document.addEventListener('DOMContentLoaded', function () {
    spinner();

    fetch(VIEW_URL, { cache: 'no-store' })
      .then(resp => {
        if (resp.status === 204) return null; // nothing to show
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        return resp.json();
      })
      .then(data => { if (data) show(data); })
      .catch(() => {
        // On error, ensure modal isn't left half-initialized
        modalBody.innerHTML = '';
      });
  });
})();
</script>

