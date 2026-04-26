<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 header-title"><?= html_escape($page_title) ?></h1>
  </div>

  <div class="row g-3">

    <!-- LEFT: Request Type + Existing -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body app-form">

          <h6 class="mb-3">Request Type</h6>
          <select id="requestType" class="form-select mb-3">
            <option value="">Select request type</option>
            <option value="inventory_request">Inventory Request</option>
            <option value="leave_request">Leave Request</option>
          </select>

          <hr>
        
            <div class="card">
                <div class="card-body">
                  <h6 class="mb-2">Your Existing Requests</h6>
                  <div id="existingRequests" class="small text-muted">
                    <div class="fst-italic">Select a request type to view records.</div>
                  </div>
                </div>
            </div>

        </div>
      </div>
    </div>

    <!-- RIGHT: New Request -->
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">

          <!-- SINGLE, REAL FORM -->
          <form id="newRequestForm"
                method="post"
                action="<?= site_url('requests/store') ?>"
                enctype="multipart/form-data"
                class="app-form">

            <input type="hidden" name="request_type" id="requestTypeHidden">

            <!-- Dynamic form loads here -->
            <div id="requestFormContainer">
                <div class="text-muted fst-italic">
                    Please select a request type to load the form.
                </div>
            </div>

            <!-- Submit (hidden until form loads) -->
            <div class="mt-2 d-none" id="submitWrapper">
              <button type="submit" class="btn btn-primary btn-sm">
                Submit Request
              </button>
            </div>

          </form>

        </div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {

  const typeSelect   = document.getElementById('requestType');
  const typeHidden   = document.getElementById('requestTypeHidden');
  const formBox      = document.getElementById('requestFormContainer');
  const recordsBox   = document.getElementById('existingRequests');
  const submitWrap   = document.getElementById('submitWrapper');

  typeSelect.addEventListener('change', function () {

    const type = this.value;
    typeHidden.value = type;
    submitWrap.classList.add('d-none');

    if (!type) {
      formBox.innerHTML =
        '<div class="text-muted fst-italic">Please select a request type.</div>';
      recordsBox.innerHTML =
        '<div class="fst-italic">Select a request type to view records.</div>';
      return;
    }

    // Load form
    fetch('<?= site_url('requests/load_form'); ?>/' + type)
      .then(res => res.text())
      .then(html => {
        formBox.innerHTML = html;
        submitWrap.classList.remove('d-none');
      });

    // Load existing records
    fetch('<?= site_url('requests/load_existing'); ?>/' + type)
      .then(res => res.text())
      .then(html => recordsBox.innerHTML = html);

  });

})();
</script>
