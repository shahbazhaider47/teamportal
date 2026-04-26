<div class="modal fade" id="viewAssetModal" tabindex="-1" aria-labelledby="viewAssetLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="viewAssetLabel">
          <i class="ti ti-eye me-1"></i> Asset Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <!-- Left: Image -->
          <div class="col-md-4 text-center">
            <img id="view_image" src="" 
                 class="img-fluid rounded shadow-sm mb-3 border" 
                 style="max-height:220px; object-fit:cover;" 
                 alt="Asset Image">
          </div>

          <!-- Right: Details -->
          <div class="col-md-8">
            <dl class="row mb-0">
              <dt class="col-sm-4">Serial No</dt><dd class="col-sm-8" id="view_serial_no"></dd>
              <dt class="col-sm-4">Name</dt><dd class="col-sm-8" id="view_name"></dd>
              <dt class="col-sm-4">Type</dt><dd class="col-sm-8" id="view_type"></dd>
              <dt class="col-sm-4">Value</dt><dd class="col-sm-8" id="view_price"></dd>
              <dt class="col-sm-4">Assigned To</dt>
              <dd class="col-sm-8">
                <span id="view_assigned" class="badge bg-light text-dark px-2 py-1"></span>
              </dd>
              <dt class="col-sm-4">Status</dt>
              <dd class="col-sm-8">
                <span id="view_status" class="badge px-2 py-1"></span>
              </dd>
              <dt class="col-sm-4">Purchased</dt><dd class="col-sm-8" id="view_purchase_date"></dd>
              <dt class="col-sm-4">Warranty</dt><dd class="col-sm-8" id="view_guarantee_date"></dd>
              <dt class="col-sm-4">Description</dt><dd class="col-sm-8" id="view_description"></dd>
              <dt class="col-sm-4">Created At</dt><dd class="col-sm-8" id="view_created_at"></dd>
              <dt class="col-sm-4">Updated At</dt><dd class="col-sm-8" id="view_updated_at"></dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function viewAsset(assetId) {
  $.get('<?= site_url('asset/get') ?>/' + assetId, function(res) {
    if (res && res.id) {
      $('#view_serial_no').text(res.serial_no || '-');
      $('#view_name').text(res.name || '-');
      $('#view_type').text(res.asset_type || '-');
      $('#view_price').text(res.price > 0 ? '<?= get_base_currency_symbol() ?>' + parseFloat(res.price).toFixed(2) : '-');

      // Assigned to: user or department
      if (res.firstname && res.lastname) {
        $('#view_assigned').text(res.firstname + ' ' + res.lastname)
                           .removeClass().addClass('badge bg-primary');
      } else if (res.department_name) {
        $('#view_assigned').text(res.department_name)
                           .removeClass().addClass('badge bg-info text-dark');
      } else {
        $('#view_assigned').text('Unassigned')
                           .removeClass().addClass('badge bg-secondary');
      }

      // Status badge
      let status = res.status || '-';
      let statusClass = 'bg-secondary';
      if (status === 'available') statusClass = 'bg-success';
      if (status === 'in-use') statusClass = 'bg-warning text-dark';
      if (status === 'retired') statusClass = 'bg-danger';
      $('#view_status').text(status).removeClass().addClass('badge ' + statusClass);

      $('#view_purchase_date').text(res.purchase_date || '-');
      $('#view_guarantee_date').text(res.guarantee_date || '-');
      $('#view_description').text(res.description || '-');
      $('#view_created_at').text(res.created_at || '-');
      $('#view_updated_at').text(res.updated_at || '-');

      // Image
      if (res.image) {
        $('#view_image').attr('src', '<?= base_url("uploads/asset/images/") ?>' + res.image);
      } else {
        $('#view_image').attr('src', 'https://via.placeholder.com/400x220?text=No+Image');
      }

      $('#viewAssetModal').modal('show');
    } else {
      alert('Unable to fetch asset details');
    }
  }, 'json').fail(function() {
    alert('Error fetching asset data');
  });
}
</script>
