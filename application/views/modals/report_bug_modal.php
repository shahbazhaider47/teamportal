<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal fade" id="reportBugModal" tabindex="-1" aria-labelledby="reportBugLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="bugReportForm" enctype="multipart/form-data" method="post" action="<?= site_url('bugs/report') ?>" class="app-form">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="reportBugLabel"><i class="ti ti-bug me-2"></i>Report a Bug</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div id="bugAlert" class="alert alert-danger d-none small mb-3"></div>

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" maxlength="160" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Severity <span class="text-danger">*</span></label>
              <select name="severity" class="form-select" required>
                <option value="Low">Low</option>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">Page URL <span class="text-danger">* </span> <small class="text-muted small">(Pre-filled with your current page.)</small></label>
              <input type="url" name="page_url" id="bugPageUrl" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">What happened? <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="4" placeholder="Brief description" required></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Steps to Reproduce</label>
              <textarea name="steps" class="form-control" rows="3" placeholder="1) … 2) … 3) …"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Expected vs Actual</label>
              <textarea name="expected_actual" class="form-control" rows="3" placeholder="Expected: …&#10;Actual: …"></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Screenshot / Attachment (optional)</label>
              <input type="file" name="attachment" class="form-control" accept=".png,.jpg,.jpeg,.pdf">
              <div class="form-text small">PNG, JPG, JPEG, or PDF. Max 2 MB.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Browser Info (auto)</label>
              <input type="text" name="user_agent" id="bugUserAgent" class="form-control" readonly>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Close</button>
          <button type="submit" id="bugSubmitBtn" class="btn btn-primary btn-sm">
            <i class="ti ti-send me-1"></i> Send Report
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Prefill current URL and UA
document.addEventListener('DOMContentLoaded', function () {
  var url = window.location.href || '';
  var ua  = navigator.userAgent || '';
  var urlInput = document.getElementById('bugPageUrl');
  var uaInput  = document.getElementById('bugUserAgent');
  if (urlInput) urlInput.value = url;
  if (uaInput)  uaInput.value  = ua;
});

// AJAX submit (no page reload)
(function($){
  $('#bugReportForm').on('submit', function(e){
    e.preventDefault();
    var $form = $(this);
    var $btn  = $('#bugSubmitBtn');
    var $alert= $('#bugAlert');

    $alert.addClass('d-none').text('');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sending…');

    var formData = new FormData(this);

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json'
    }).done(function(resp){
      if (resp && resp.status === 'success') {
        $btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> Send Report');
        $('#reportBugModal').modal('hide');
        // Optional: toast/alert – plug into your app’s alert/toast function if you have one:
        if (typeof window.Toastify === 'function') {
          Toastify({ text: resp.message || 'Bug report sent.', duration: 3000 }).showToast();
        } else {
          alert(resp.message || 'Bug report sent.');
        }
        $form[0].reset();
      } else {
        $alert.removeClass('d-none').text(resp && resp.message ? resp.message : 'Failed to send bug report.');
        $btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> Send Report');
      }
    }).fail(function(xhr){
      $alert.removeClass('d-none').text('Network or server error. Please try again.');
      $btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> Send Report');
    });
  });
})(jQuery);
</script>
