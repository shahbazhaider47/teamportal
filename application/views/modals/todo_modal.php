<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal fade" id="todoModal" tabindex="-1" aria-labelledby="todoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-md modal-dialog-top">
    <form action="<?= site_url('todo/add') ?>" method="post" class="modal-content app-form">
      <!-- Header -->
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="todoModalLabel">
          <i class="fas fa-check-square me-2"></i>Add New To-Do
        </h5>
        <button type="button" class="btn-close m-0 fs-5 btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- NEW: generic relation fields (hidden) -->
        <input type="hidden" name="rel_type" id="todo_rel_type" value="<?= isset($rel_type) ? html_escape($rel_type) : '' ?>">
        <input type="hidden" name="rel_id"   id="todo_rel_id"   value="<?= isset($rel_id)   ? (int)$rel_id          : '' ?>">

        <div class="row">
          <div class="col-12">
            <div class="mb-3">
              <label for="todo_name" class="form-label">To-Do Name <span class="text-danger">*</span></label>
              <input 
                type="text"
                class="form-control"
                name="todo_name"
                id="todo_name"
                placeholder="Enter a task..."
                required
                autofocus
                maxlength="255"
                autocomplete="off"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-plus me-1"></i>Add To-Do
        </button>
      </div>
    </form>
  </div>
</div>


<script>
(function($) {
  $('#todoModal').on('show.bs.modal', function (event) {
    var button  = $(event.relatedTarget); // element that triggered the modal
    var relType = button.data('rel-type') || '';
    var relId   = button.data('rel-id')   || '';

    $('#todo_rel_type').val(relType);
    $('#todo_rel_id').val(relId);
  });
})(jQuery);
</script>
