<!-- Modal for Add/Edit Inventory -->
<div class="modal fade" id="inventoryModal" tabindex="-1" aria-labelledby="inventoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" action="<?= site_url('asset/save') ?>" enctype="multipart/form-data" id="inventoryForm" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="inventoryModalLabel">Add New Inventory</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="inventory_id">

          <div class="row g-3">
            <div class="col-md-12">
              <label for="serial_no" class="form-label">Serial No <small>(Auto-generated)</small></label>
              <input type="text" class="form-control" name="serial_no" id="serial_no" readonly placeholder="Auto-generated">
            </div>

            <div class="col-md-4">
              <label for="inventory_name" class="form-label">Inventory Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="inventory_name" required>
            </div>

            <div class="col-md-4">
              <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" id="status" class="form-control" required>
                <option value="">Select</option>
                <option value="available">Available</option>
                <option value="maintenance">Maintenance</option>
                <option value="lost">Lost</option>
                <option value="retired">Retired</option>                
              </select>
            </div>

            <div class="col-md-4">
              <label for="asset_type_select" class="form-label">Inventory Type <span class="text-danger">*</span></label>
              <div class="input-group">
                <select name="type_id" id="asset_type_select" class="form-control" required>
                  <option value="">Select</option>
                  <?php foreach($asset_types as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= html_escape($t['name']) ?></option>
                  <?php endforeach ?>
                </select>
                <button type="button" class="btn btn-light-primary btn-sm" id="addTypeBtn">
                  <i class="ti ti-plus"></i> Add Type
                </button>
              </div>
            </div>

            <div class="col-md-4">
              <label for="asset_price" class="form-label">Value</label>
              <div class="input-group">
                <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                <input type="number" class="form-control" name="price" id="asset_price" value="0" step="0.01">
              </div>
            </div>

            <div class="col-md-4">
              <label for="purchase_date" class="form-label">Purchase Date</label>
              <input type="date" class="form-control" name="purchase_date" id="purchase_date">
            </div>

            <div class="col-md-4">
              <label for="guarantee_date" class="form-label">Guarantee Date</label>
              <input type="date" class="form-control" name="guarantee_date" id="guarantee_date">
            </div>

            <div class="col-md-12">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" name="description" id="description" rows="3"></textarea>
            </div>

            <div class="col-md-12">
              <label for="image" class="form-label">Inventory Image</label>
              <input type="file" class="form-control" name="image" id="image" accept="image/*">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Inventory</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal for Adding Inventory Type -->
<div class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('asset/add_type') ?>" id="typeForm" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-light-primary">
          <h5 class="modal-title text-primary" id="typeModalLabel"><i class="ti ti-plus"></i> Add Inventory/Asset Type</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label for="type_name" class="form-label">Type Name</label>
          <input type="text" class="form-control" id="type_name" name="name" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Type</button>
        </div>
      </div>
    </form>
  </div>
</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {

    // Clear and open Add Inventory modal
    window.clearAssetForm = function() {
        $('#inventoryForm')[0].reset();
        $('#inventory_id').val('');
        $('#inventoryModalLabel').text('Add New Inventory');
    
        // fetch auto serial from server
        $.get('<?= site_url("asset/get_next_serial") ?>', function(res) {
            if (res && res.serial_no) {
                $('#serial_no').val(res.serial_no);
            }
        }, 'json');
    
        $('#inventoryModal').modal('show');
    };


    // Edit inventory (AJAX load)
    window.editAsset = function(assetId) {
        $.get('<?= site_url('asset/get') ?>/' + assetId, function(res) {
            if (res && res.id) {
                $('#inventory_id').val(res.id);
                $('#serial_no').val(res.serial_no);
                $('#inventory_name').val(res.name);
                $('#status').val(res.status);
                $('#asset_type_select').val(res.type_id);
                $('#asset_price').val(res.price);
                $('#purchase_date').val(res.purchase_date);
                $('#guarantee_date').val(res.guarantee_date);
                $('#description').val(res.description);

                $('#inventoryModalLabel').text('Edit Inventory');
                $('#inventoryModal').modal('show');
            } else {
                alert('Failed to load inventory details!');
            }
        }, 'json').fail(function() {
            alert('Error loading inventory data');
        });
    };

    // Show Add Type modal
    $('#addTypeBtn').on('click', function(e) {
        e.preventDefault();
        $('#typeModal').modal('show');
    });

    // Handle Add Type form submission
    $('#typeForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var typeName = $form.find('[name="name"]').val();
                    $('#asset_type_select')
                        .append($('<option>', {
                            value: resp.id,
                            text: typeName,
                            selected: true
                        }));
                    $('#typeModal').modal('hide');
                    $form[0].reset();
                } else {
                    alert(resp.message || 'Failed to add type');
                }
            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseText);
            }
        });
    });
});

</script>
