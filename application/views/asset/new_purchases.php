<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView   = staff_can('view', 'assets');
          $canDelete = staff_can('delete', 'assets');          
          $canCreate = staff_can('view', 'assets');          
          $canExport = staff_can('export', 'general');
          $canPrint  = staff_can('print', 'general');
        ?>

        <a href="<?= $canView ? base_url('asset') : 'javascript:void(0);' ?>"
           class="btn <?= $canView ? 'btn-outline-primary' : 'btn-disabled' ?> btn-header">
           <i class="fas fa-boxes me-1"></i> Assets
        </a>

        <a href="<?= $canView ? base_url('asset/inventory') : 'javascript:void(0);' ?>"
           class="btn <?= $canView ? 'btn-outline-primary' : 'btn-disabled' ?> btn-header">
           <i class="ti ti-tools"></i> Inventory
        </a>

        <a href="<?= $canView ? base_url('asset/new_purchases') : 'javascript:void(0);' ?>"
           class="btn <?= $canView ? 'btn-primary' : 'btn-disabled' ?> btn-header">
           <i class="ti ti-shopping-cart-plus"></i> New Purchases
        </a>
        
        <div class="btn-divider"></div>

        <!-- Add Purchase Button -->
        <button type="button"
                class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                data-bs-toggle="modal"
                data-bs-target="#purchaseModal"
                onclick="clearPurchaseForm()"
                <?= $canCreate ? '' : 'disabled' ?>>
          <i class="ti ti-plus me-1"></i> Request New
        </button>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." aria-label="Search"
                 data-table-target="<?= $table_id ?? 'purchaseTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
     
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel" data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>

  <!-- Table of Purchases -->
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bottom-border small align-middle table-hover" id="purchaseTable">
          <thead class="bg-light-primary">
            <tr>
              <th>Title</th>
              <th>Type</th>
              <th>Quantity</th>
              <th>Cost/Item</th>
              <th>Total Amount</th>
              <th>Date Required</th>
              <th>Date Requested</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($purchases as $p): ?>
              <tr>
                <td><?= html_escape($p['purchase_title']) ?></td>
                <td><?= html_escape($p['asset_type']) ?></td>
                <td><?= (int)$p['required_quantity'] ?></td>
                <td><?= get_base_currency_symbol() . number_format($p['cost_per_item'], 2) ?></td>
                <td><?= get_base_currency_symbol() . number_format($p['total_amount'], 2) ?></td>
                <td><?= html_escape($p['date_required']) ?></td>
                <td><?= html_escape($p['created_at']) ?></td>
                <td>
                  <span class="badge bg-<?= ($p['purchase_status'] == 'Purchased') ? 'success' : 'warning' ?>">
                    <?= html_escape($p['purchase_status']) ?>
                  </span>
                </td>
<td>
  <div class="btn-group btn-group-sm">
    <!-- View Details -->
    <button type="button" class="btn btn-outline-secondary"
            onclick='viewPurchase(<?= json_encode($p) ?>)'>
      <i class="ti ti-eye"></i>
    </button>

    <!-- Edit -->
    <button type="button" class="btn btn-outline-secondary"
            onclick='editPurchase(<?= json_encode($p) ?>)'>
      <i class="ti ti-edit"></i>
    </button>

    <!-- Delete Button -->
    <?php if ($canDelete): ?>
        <?= delete_link([
        'url' => 'asset/delete_purchase/' . $p['id'],
        'label' => '',
        'class' => 'btn btn-outline-secondary',
        'message' => '',                                             
        ]) ?>
    <?php endif; ?>


    <!-- Add to Inventory (only if Purchased) -->
    <?php if ($p['purchase_status'] === 'Purchased'): ?>
      <button type="button" 
              class="btn btn-outline-success"
              onclick="confirmAddToInventory(<?= $p['id'] ?>)">
        <i class="ti ti-plus"></i>
      </button>
    <?php endif; ?>
  </div>
</td>


              </tr>
            <?php endforeach; ?>
            <?php if (empty($purchases)): ?>
              <tr><td colspan="10" class="text-center">No purchase records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<!-- View Purchase Modal -->
<div class="modal fade" id="viewPurchaseModal" tabindex="-1" aria-labelledby="viewPurchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title text-white fw-bold" id="viewPurchaseModalLabel">
          <i class="bi bi-receipt me-2"></i>Purchase Details
        </h5>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-header bg-light-primary py-2">
                <h6 class="mb-0 fw-semibold text-primary"><i class="bi bi-info-circle me-1"></i>Basic Information</h6>
              </div>
              <div class="card-body p-3">
                <dl class="row mb-0">
                  <dt class="col-sm-5 fw-normal text-muted small">Title</dt>
                  <dd class="col-sm-7 small" id="view_title">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Type</dt>
                  <dd class="col-sm-7 small" id="view_type">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Description</dt>
                  <dd class="col-sm-7 small" id="view_description">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Date Required</dt>
                  <dd class="col-sm-7 small" id="view_date_required">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Requested On</dt>
                  <dd class="col-sm-7 small" id="view_created_at">-</dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-header bg-light-primary py-2">
                <h6 class="mb-0 fw-semibold text-primary"><i class="bi bi-currency-dollar me-1"></i>Financial Details</h6>
              </div>
              <div class="card-body p-3">
                <dl class="row mb-0">
                  <dt class="col-sm-5 fw-normal text-muted small">Required Quantity</dt>
                  <dd class="col-sm-7 small" id="view_quantity">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Cost Per Item</dt>
                  <dd class="col-sm-7 small" id="view_cost">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Total Amount</dt>
                  <dd class="col-sm-7 small" id="view_total">-</dd>

                  <dt class="col-sm-5 fw-normal text-muted small">Payment Method</dt>
                  <dd class="col-sm-7 small" id="view_payment_method">-</dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-light-primary py-2">
                <h6 class="mb-0 fw-semibold text-primary"><i class="bi bi-person-check me-1"></i>Process Information</h6>
              </div>
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-md-4">
                    <dl class="mb-2">
                      <dt class="fw-normal text-muted small">Status</dt>
                      <dd>
                        <span class="badge bg-success" id="view_status">-</span>
                      </dd>
                    </dl>
                  </div>
                  <div class="col-md-4">
                    <dl class="mb-2">
                      <dt class="fw-normal text-muted small">Requested By</dt>
                      <dd class="small" id="view_created_by">-</dd>
                    </dl>
                  </div>
                  <div class="col-md-4">
                    <dl class="mb-2">
                      <dt class="fw-normal text-muted small">Purchase Source</dt>
                      <dd class="small" id="view_source">-</dd>
                    </dl>
                  </div>
                  <div class="col-md-4">
                    <dl class="mb-2">
                      <dt class="fw-normal text-muted small">Purchased By</dt>
                      <dd class="small" id="view_purchased_by">-</dd>
                    </dl>
                  </div>
                  <div class="col-md-4">
                    <dl class="mb-0">
                      <dt class="fw-normal text-muted small">Payment By</dt>
                      <dd class="small" id="view_payment_by">-</dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addToInventoryModal" tabindex="-1" aria-labelledby="addToInventoryLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title" id="addToInventoryLabel"><i class="ti ti-plus"></i> Add to Inventory</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-3">Add this item to inventory?</p>
        <button type="button" class="btn btn-success btn-sm" id="confirmAddBtn">Yes, Add</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No</button>
      </div>
    </div>
  </div>
</div>

<script>
  function viewPurchase(purchase) {
    const cur = "<?= get_base_currency_symbol(); ?>"; // Inject PHP currency symbol

    document.getElementById('view_title').innerText          = purchase.purchase_title ?? '-';
    document.getElementById('view_type').innerText           = purchase.asset_type ?? '-';
    document.getElementById('view_description').innerText    = purchase.description ?? '-';
    document.getElementById('view_quantity').innerText       = purchase.required_quantity ?? '-';
    document.getElementById('view_cost').innerText           = cur + ' ' + (purchase.cost_per_item ? parseFloat(purchase.cost_per_item).toFixed(2) : '0.00');
    document.getElementById('view_total').innerText          = cur + ' ' + (purchase.total_amount ? parseFloat(purchase.total_amount).toFixed(2) : '0.00');
    document.getElementById('view_date_required').innerText  = purchase.date_required ?? '-';
    document.getElementById('view_status').innerText         = purchase.purchase_status ?? '-';
    document.getElementById('view_created_by').innerText = (purchase.created_firstname ? purchase.created_firstname + ' ' + purchase.created_lastname : '-') ?? '-';
    document.getElementById('view_source').innerText         = purchase.purchase_source ?? '-';
    document.getElementById('view_purchased_by').innerText   = (purchase.purchaser_firstname ? purchase.purchaser_firstname + ' ' + purchase.purchaser_lastname : '-') ?? '-';
    document.getElementById('view_payment_by').innerText     = (purchase.payment_firstname ? purchase.payment_firstname + ' ' + purchase.payment_lastname : '-') ?? '-';
    document.getElementById('view_payment_method').innerText = purchase.payment_method ?? '-';
    document.getElementById('view_created_at').innerText     = purchase.created_at ?? '-';

    var modal = new bootstrap.Modal(document.getElementById('viewPurchaseModal'));
    modal.show();
  }
</script>


<!-- Single Modal (Add/Edit Unified) -->
<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('asset/modals/purchase_modal', [
  'asset_types' => $asset_types,
  'users' => $users
], true); ?>

<script>
  function clearPurchaseForm() {
    document.getElementById('purchaseForm').reset();
    document.getElementById('purchase_id').value = '';
    document.getElementById('purchaseModalLabel').innerHTML = '<i class="ti ti-plus me-1"></i> Add New Purchase';
    document.getElementById('purchasedFields').classList.add('d-none');
  }

  function editPurchase(purchase) {
    clearPurchaseForm();
    document.getElementById('purchaseModalLabel').innerHTML = '<i class="ti ti-edit me-1"></i> Edit Purchase';

    document.getElementById('purchase_id').value       = purchase.id;
    document.getElementById('purchase_title').value    = purchase.purchase_title;
    document.getElementById('asset_type_id').value     = purchase.asset_type_id;
    document.getElementById('description').value       = purchase.description ?? '';
    document.getElementById('required_quantity').value = purchase.required_quantity;
    document.getElementById('cost_per_item').value     = purchase.cost_per_item;
    document.getElementById('total_amount').value      = purchase.total_amount;
    document.getElementById('date_required').value     = purchase.date_required;
    document.getElementById('purchase_status').value   = purchase.purchase_status;

    if (purchase.purchase_status === 'Purchased') {
      document.getElementById('purchase_source').value = purchase.purchase_source ?? '';
      document.getElementById('payment_method').value  = purchase.payment_method ?? '';
      document.getElementById('purchased_by').value    = purchase.purchased_by ?? '';
      document.getElementById('payment_user').value    = purchase.payment_user ?? '';
      document.getElementById('purchasedFields').classList.remove('d-none');
    }

    var modal = new bootstrap.Modal(document.getElementById('purchaseModal'));
    modal.show();
  }
  
  
let selectedPurchaseId = null;

function confirmAddToInventory(purchaseId) {
  selectedPurchaseId = purchaseId;
  var modal = new bootstrap.Modal(document.getElementById('addToInventoryModal'));
  modal.show();
}

document.getElementById('confirmAddBtn').addEventListener('click', function () {
  if (!selectedPurchaseId) return;

  $.post('<?= site_url("asset/add_to_inventory") ?>/' + selectedPurchaseId, function (res) {
    if (res.success) {
      // ✅ Update row in table
      let row = $("#purchaseTable").find("tr").filter(function () {
        return $(this).find("td:first").text().trim() === res.data.purchase_title;
      });

      if (row.length) {
        row.find("td:nth-child(8)").html('<span class="badge bg-secondary">Added</span>');
        row.find("td:nth-child(9)").find(".btn-outline-success").remove(); // remove add button
      }

      alert("Item added to inventory successfully.");
    } else {
      alert("Failed to add item to inventory.");
    }
  }, 'json');

  var modalEl = document.getElementById('addToInventoryModal');
  var modal = bootstrap.Modal.getInstance(modalEl);
  modal.hide();
});
  
  
</script>
