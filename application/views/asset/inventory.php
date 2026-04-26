<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= $page_title ?></h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canView    = staff_can('view', 'assets');
        $canDelete  = staff_can('delete', 'assets');
        $canCreate  = staff_can('view', 'assets');          
        $canExport  = staff_can('export', 'general');
        $canPrint   = staff_can('print', 'general');
      ?>

      <a href="<?= $canView ? base_url('asset') : 'javascript:void(0);' ?>"
         class="btn <?= $canView ? 'btn-outline-primary' : 'btn-disabled' ?> btn-header"
         title="Go to Assets">
         <i class="fas fa-boxes me-1"></i> Assets
      </a>

      <a href="<?= $canView ? base_url('asset/inventory') : 'javascript:void(0);' ?>"
         class="btn <?= $canView ? 'btn-primary' : 'btn-disabled' ?> btn-header"
         title="Go to Inventory">
         <i class="ti ti-tools"></i> Inventory
      </a>

      <a href="<?= $canView ? base_url('asset/new_purchases') : 'javascript:void(0);' ?>"
         class="btn <?= $canView ? 'btn-outline-primary' : 'btn-disabled' ?> btn-header"
         title="New Purchases">
         <i class="ti ti-shopping-cart-plus"></i> New Purchases
      </a>
      
      <div class="btn-divider"></div>

      <!-- Add Inventory -->
      <button type="button"
              id="btn-add-user"
              class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
              <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#inventoryModal"' : 'disabled' ?>
              onclick="clearAssetForm()"
              title="Add New Inventory">
        <i class="fas fa-plus me-1"></i> Add Inventory
      </button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover small align-middle" id="inventoryTable">
          <thead class="bg-light-primary">
            <tr>
              <th>Serial No</th>
              <th>Name / Title</th>
              <th>Type</th>
              <th>Value</th>
              <th>Date Purchased</th>
              <th>Warranty Till</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assets as $a): ?>
              <tr>
                <td><?= html_escape($a['serial_no']) ?></td>
                <td>
                  <?php if (!empty($a['image'])): ?>
                    <img src="<?= base_url('uploads/asset/images/' . $a['image']) ?>" alt="Asset" class="rounded me-2" width="32" height="32" style="object-fit:cover;">
                  <?php else: ?>
                    <span class="avatar-placeholder me-2"><i class="fas fa-box-open"></i></span>
                  <?php endif; ?>
                  <?= html_escape($a['name']) ?>
                </td>
                <td><?= html_escape($a['asset_type']) ?></td>
                <td><?= $a['price'] > 0 ? html_escape(get_base_currency_symbol()) . number_format($a['price'], 2) : '-' ?></td>
                <td><?= !empty($a['purchase_date']) ? date('Y-m-d', strtotime($a['purchase_date'])) : '-' ?></td>
                <td><?= !empty($a['guarantee_date']) ? date('Y-m-d', strtotime($a['guarantee_date'])) : '-' ?></td>
                <td>
                  <span class="badge 
                    <?php if($a['status'] === 'available') echo 'bg-success';
                          elseif($a['status'] === 'in-use') echo 'bg-primary';
                          elseif($a['status'] === 'maintenance') echo 'bg-warning text-dark';
                          elseif($a['status'] === 'lost') echo 'bg-danger';
                          else echo 'bg-secondary'; ?>">
                    <?= ucfirst(html_escape($a['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary btn-sm" onclick="viewAsset(<?= $a['id'] ?>)">
                      <i class="ti ti-eye"></i>
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="editAsset(<?= $a['id'] ?>)">
                      <i class="ti ti-edit"></i>
                    </button>

                    <!-- Delete Button -->
                    <?php if ($canDelete): ?>
                        <?= delete_link([
                        'url' => 'asset/delete/' . $a['id'],
                        'label' => '',
                        'class' => 'btn btn-outline-secondary',
                        'message' => '',                                             
                        ]) ?>
                    <?php endif; ?>

                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<!-- View Inventory Modal -->
<div class="modal fade" id="viewInventoryModal" tabindex="-1" aria-labelledby="viewInventoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      
      <!-- Header -->
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="viewInventoryModalLabel">
          <i class="ti ti-box me-2"></i> Inventory Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      
      <!-- Body -->
      <div class="modal-body">
        <div class="row g-4 align-items-start">
          
          <!-- Image -->
          <div class="col-md-4 text-center">
            <img id="view_image" src="https://via.placeholder.com/300x200?text=No+Image" 
                 alt="Inventory Image" 
                 class="img-fluid rounded shadow-sm border mb-3" 
                 style="max-height:250px; object-fit:cover;">
            Inventory Status
            <span class="badge bg-primary w-100 py-2" id="view_status"></span>
          </div>

          <!-- Details -->
          <div class="col-md-8">
            <h5 class="fw-bold mb-2" id="view_name"></h5>
            <p class="text-muted small mb-3" id="view_description"></p>
            
            <div class="row g-2">
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Serial No:</strong>
                  <div id="view_serial_no" class="text-dark"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Type:</strong>
                  <div id="view_type" class="text-dark"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Value:</strong>
                  <div id="view_price" class="text-dark"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Date Purchased:</strong>
                  <div id="view_purchase_date" class="text-dark"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Warranty Till:</strong>
                  <div id="view_guarantee_date" class="text-dark"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Created At:</strong>
                  <div id="view_created_at" class="text-dark"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light-primary">
                  <strong>Updated At:</strong>
                  <div id="view_updated_at" class="text-dark"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


  <?php $CI =& get_instance(); ?>
  <?php echo $CI->load->view('asset/modals/add_inventory', [], true); ?>
</div>

<script>
function viewAsset(assetId) {
  $.get('<?= site_url('asset/get') ?>/' + assetId, function(res) {
    if (res && res.id) {
      $('#view_serial_no').text(res.serial_no || '-');
      $('#view_name').text(res.name || '-');
      $('#view_type').text(res.asset_type || '-');
      $('#view_price').text(res.price > 0 ? '<?= get_base_currency_symbol() ?>' + parseFloat(res.price).toFixed(2) : '-');
      $('#view_status').text(res.status ? res.status.charAt(0).toUpperCase() + res.status.slice(1) : '-');
      $('#view_purchase_date').text(res.purchase_date || '-');
      $('#view_guarantee_date').text(res.guarantee_date || '-');
      $('#view_description').text(res.description || '-');
      $('#view_created_at').text(res.created_at || '-');
      $('#view_updated_at').text(res.updated_at || '-');

      if (res.image) {
        $('#view_image').attr('src', '<?= base_url("uploads/asset/images/") ?>' + res.image).show();
      } else {
        $('#view_image').attr('src', 'https://via.placeholder.com/400x200?text=No+Image').show();
      }

      $('#viewInventoryModal').modal('show');
    } else {
      alert('Unable to fetch inventory details');
    }
  }, 'json').fail(function() {
    alert('Error fetching inventory data');
  });
}
</script>
