<!-- Manage Leave Types Modal -->
<div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-labelledby="leaveTypeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="leaveTypeForm" autocomplete="off">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="leaveTypeModalLabel">Add Leave Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="leaveTypeId">
          <div class="mb-3">
            <label class="form-label">Type Name</label>
            <input type="text" class="form-control" name="name" id="leaveTypeName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" class="form-control" name="description" id="leaveTypeDescription">
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="leaveTypeStatus">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Type</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>

$('#leaveTypeForm').submit(function(e){
    e.preventDefault();

    var $form = $(this);
    var $btn  = $form.find('[type="submit"]');
    $btn.prop('disabled', true).text('Saving...');

    $.ajax({
        url: '<?= site_url("attendance/ajax_save_leave_type") ?>',
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                $('#leaveTypeModal').modal('hide');
                $form[0].reset();
                // You may want to refresh your leave types list here via AJAX
                Toastify({ text: "Leave type saved successfully!", duration: 3000, gravity: "top", backgroundColor: "#28a745" }).showToast();
            } else {
                alert(res.message || 'Something went wrong.');
            }
        },
        error: function(xhr) {
            alert('AJAX error! Try again.');
        },
        complete: function() {
            $btn.prop('disabled', false).text('Save Type');
        }
    });
});
    
</script>