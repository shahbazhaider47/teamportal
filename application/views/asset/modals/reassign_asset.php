<div class="modal fade" id="reassignAssetModal" tabindex="-1" aria-labelledby="reassignAssetLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" action="<?= site_url('asset/reassign') ?>" id="reassignForm" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="reassignAssetLabel"><i class="ti ti-refresh"></i> Re-Assign Asset</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="asset_id" id="reassign_asset_id">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="reassign_type">Assign To</label>
              <select class="form-control" name="assign_type" id="reassign_type" required>
                <option value="">-- Select --</option>
                <option value="user">User</option>
                <option value="department">Department</option>
              </select>
            </div>

            <div class="col-md-6 mb-3" id="reassignTargetField" style="display:none;">
              <label id="reassignTargetLabel"></label>
              <select class="form-control" name="assign_id" id="reassignTargetSelect" required>
                <option value="">-- Select --</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Re-Assign</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openReassignModal(assetId, currentUser, currentDept) {
  $('#reassign_asset_id').val(assetId);
  $('#reassign_type').val('');
  $('#reassignTargetField').hide();
  $('#reassignTargetSelect').empty().append('<option value="">-- Select --</option>');
  $('#reassignAssetModal').modal('show');
}

$(function() {
  $('#reassign_type').on('change', function() {
    let type = $(this).val();
    let $field = $('#reassignTargetField');
    let $label = $('#reassignTargetLabel');
    let $select = $('#reassignTargetSelect');

    $select.empty().append('<option value="">-- Select --</option>');

    if (type === 'user') {
      $label.text('Select User');
      $field.show();

      $.getJSON('<?= site_url("asset/get_users") ?>', function(data) {
        if (data && data.length > 0) {
          $.each(data, function(i, user) {
            $select.append('<option value="'+user.id+'">'+user.firstname+' '+user.lastname+'</option>');
          });
        }
      });

    } else if (type === 'department') {
      $label.text('Select Department');
      $field.show();

      $.getJSON('<?= site_url("asset/get_departments") ?>', function(data) {
        if (data && data.length > 0) {
          $.each(data, function(i, dept) {
            $select.append('<option value="'+dept.id+'">'+dept.name+'</option>');
          });
        }
      });

    } else {
      $field.hide();
    }
  });
});
</script>
