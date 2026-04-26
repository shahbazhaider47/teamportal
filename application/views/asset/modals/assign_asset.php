<div class="modal fade" id="assignAssetModal" tabindex="-1" aria-labelledby="assignAssetLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" action="<?= site_url('asset/assign') ?>" id="assignForm" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="assignAssetLabel">
            <i class="ti ti-link"></i> Assign Asset
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row">
            <!-- Asset Selection (Full width) -->
            <div class="col-md-12 mb-3">
              <label for="asset_id" class="form-label">Select Available Asset</label>
              <select class="form-control" name="asset_id" id="asset_id" required>
                <option value="">-- Select --</option>
                <?php foreach($available as $a): ?>
                  <option value="<?= $a['id'] ?>"><?= $a['serial_no'] ?> - <?= $a['name'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row">
            <!-- Assign Type -->
            <div class="col-md-6 mb-3">
              <label for="assign_type" class="form-label">Assign To</label>
              <select class="form-control" name="assign_type" id="assign_type" required>
                <option value="">-- Select --</option>
                <option value="user">User</option>
                <option value="department">Department</option>
              </select>
            </div>

            <!-- Dynamic Target Field -->
            <div class="col-md-6 mb-3" id="assignTargetField" style="display:none;">
              <label id="assignTargetLabel" class="form-label"></label>
              <select class="form-control" name="assign_id" id="assignTargetSelect" required>
                <option value="">-- Select --</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check"></i> Assign
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
  $('#assign_type').on('change', function() {
    let type = $(this).val();
    let $field = $('#assignTargetField');
    let $label = $('#assignTargetLabel');
    let $select = $('#assignTargetSelect');

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
