<!-- View Announcement Modal -->
<div class="modal fade" id="viewAnnouncementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="viewAnnouncementTitle">Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="announcementViewBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const modalEl   = document.getElementById('viewAnnouncementModal');
  const modalBody = document.getElementById('announcementViewBody');
  const titleEl   = document.getElementById('viewAnnouncementTitle');

  if (!modalEl || !modalBody || !titleEl) return; // modal not present

  // Don’t run on the announcements index page if that view already controls it.
  // (Optional) You can remove this guard if you want it everywhere unconditionally.
  // if (document.body.dataset.page === 'announcements-index') return;

  function spinner() {
    modalBody.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>`;
  }

  function show(json) {
    titleEl.textContent = json.title || 'Announcement';
    modalBody.innerHTML = json.html || '<div class="alert alert-warning mb-0">No content.</div>';
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // When closed once, mark dismissed so it won’t auto-open again
    modalEl.addEventListener('hidden.bs.modal', function onClose(){
      fetch("<?= site_url('announcements/dismiss') ?>", {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'announcement_id=' + encodeURIComponent(json.id)
      });
      modalEl.removeEventListener('hidden.bs.modal', onClose);
    }, { once: true });
  }

  document.addEventListener('DOMContentLoaded', function(){
    spinner();
    fetch("<?= site_url('announcements/popup_latest') ?>", { cache: 'no-store' })
      .then(resp => {
        if (resp.status === 204) return null; // nothing to show
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        return resp.json();
      })
      .then(data => { if (data) show(data); })
      .catch(() => { /* silently ignore on non-announcement pages */ });
  });
})();
</script>
<script>
(function(){
  const modalEl   = document.getElementById('viewAnnouncementModal');
  const modalBody = document.getElementById('announcementViewBody');
  const titleEl   = document.getElementById('viewAnnouncementTitle');

  function spinner() {
    modalBody.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>`;
  }

  function openModal() {
    new bootstrap.Modal(modalEl).show();
  }

  // AJAX route & dismiss route from PHP
  const VIEW_URL    = "<?= site_url('announcements/view_ajax/') ?>";
  const DISMISS_URL = "<?= site_url('announcements/dismiss') ?>";

  // Load by ID (preferred)
  function openById(id) {
    spinner();
    fetch(VIEW_URL + encodeURIComponent(id))
      .then(r => r.text())
      .then(html => {
        modalBody.innerHTML = html;
        // Title is rendered by view_ajax; if you want to keep the header title in sync:
        const h5 = modalBody.querySelector('h5');
        if (h5) titleEl.textContent = h5.textContent || 'Announcement';
        openModal();

        // Mark dismissed once the user closes the modal
        modalEl.addEventListener('hidden.bs.modal', function onClose(){
          fetch(DISMISS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'announcement_id=' + encodeURIComponent(id)
          });
          modalEl.removeEventListener('hidden.bs.modal', onClose);
        }, { once: true });
      })
      .catch(() => {
        modalBody.innerHTML = '<div class="alert alert-danger">Failed to load announcement.</div>';
        openModal();
      });
  }

  // Fallback: fill from data-* attributes (legacy dashboard links without data-id)
  function fillFromDataset(btn) {
    const title    = btn.dataset.title || 'Announcement';
    const message  = btn.dataset.message || '';
    const category = btn.dataset.category || 'General';
    const catColor = btn.dataset.categoryColor || '#6c757d';
    const sender   = btn.dataset.sender || 'System';
    const priority = btn.dataset.priority || 'Medium';
    const attachment = btn.dataset.attachment || '';
    const created  = btn.dataset.created || '';

    titleEl.textContent = title;

    modalBody.innerHTML = `
      <p id="viewAnnouncementMessage"></p>
      <span class="badge mt-2 mb-3" id="viewAnnouncementCategory"></span>
      <div id="viewAnnouncementAttachment" class="mb-3"></div>
      <div class="text-muted small" id="viewAnnouncementMeta"></div>
    `;

    document.getElementById('viewAnnouncementMessage').innerText = message;
    const catEl = document.getElementById('viewAnnouncementCategory');
    catEl.innerText = category;
    catEl.style.backgroundColor = catColor;

    const box = document.getElementById('viewAnnouncementAttachment');
    box.innerHTML = attachment
      ? `<a href="${attachment}" target="_blank" class="btn btn-outline-secondary btn-sm">
           <i class="fas fa-paperclip"></i> View Attachment
         </a>`
      : '';

    document.getElementById('viewAnnouncementMeta').innerText =
      `By ${sender} ${created ? ' | ' + created : ''} | Priority: ${priority}`;
  }

  // Event delegation so it works for dynamically added buttons too
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-view-announcement');
    if (!btn) return;

    const id = btn.dataset.id;
    if (id) {
      openById(id);
    } else {
      fillFromDataset(btn);
      openModal();
    }
  });
})();
</script>
