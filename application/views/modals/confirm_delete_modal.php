<div class="modal fade" id="appDeleteConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-sm modal-dialog-top">
    <div class="modal-content">

      <div class="modal-header bg-danger text-white p-2">
        <h5 class="modal-title text-white small">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="mb-0 small" id="appDeleteConfirmText">
          This action cannot be undone.
        </p>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light-primary btn-header" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-light-danger btn-header" id="appDeleteConfirmBtn">Delete</button>
      </div>

    </div>
  </div>
</div>


<script>
(function () {

  let deleteUrl = null;

  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.app-delete-trigger');
    if (!trigger) return;

    e.preventDefault();

    deleteUrl = trigger.dataset.deleteUrl;

    const msg = trigger.dataset.deleteMessage ||
      'This action cannot be undone. Are you sure?';

    document.getElementById('appDeleteConfirmText').textContent = msg;

    new bootstrap.Modal(
      document.getElementById('appDeleteConfirmModal')
    ).show();
  });

  document.getElementById('appDeleteConfirmBtn').addEventListener('click', function () {
    if (!deleteUrl) return;

    window.location.href = deleteUrl;
  });

})();
</script>