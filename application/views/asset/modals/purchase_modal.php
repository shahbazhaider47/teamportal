<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="purchaseModalLabel">
          <i class="ti ti-plus me-1"></i> Add New Purchase
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="purchaseForm" method="post" action="<?= site_url('asset/save_purchase') ?>" class="app-form">
        <div class="modal-body">
          <input type="hidden" name="id" id="purchase_id" />

          <div class="row g-3">
            <!-- Basic fields -->
            <div class="col-md-6">
              <label class="form-label">Purchase Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="purchase_title" id="purchase_title" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Asset Type</label>
              <select class="form-select" name="asset_type_id" id="asset_type_id" required>
                <option value="">-- Select Type --</option>
                <?php foreach ($asset_types as $t): ?>
                  <option value="<?= $t['id'] ?>"><?= html_escape($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="description" rows="2"></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Required Quantity</label>
              <input type="number" class="form-control" name="required_quantity" id="required_quantity" min="1">
            </div>

            <div class="col-md-4">
              <label class="form-label">Cost Per Item</label>
              <input type="number" step="0.01" class="form-control" name="cost_per_item" id="cost_per_item">
            </div>

            <div class="col-md-4">
              <label class="form-label">Total Amount</label>
              <input type="number" step="0.01" class="form-control" name="total_amount" id="total_amount" readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label">Date Required</label>
              <input type="date" class="form-control" name="date_required" id="date_required">
            </div>

            <div class="col-md-6">
              <label class="form-label">Purchase Status</label>
              <select class="form-select" name="purchase_status" id="purchase_status">
                <option value="Pending">Pending</option>
                <option value="Hold">Hold</option>
                <option value="Scheduled">Scheduled</option>
                <option value="Canceled">Canceled</option>
                <option value="Purchased">Purchased</option>
                <option value="Delayed">Delayed</option>
              </select>
            </div>
          </div>

          <!-- Conditional fields (hidden unless Purchased is selected) -->
          <div id="purchasedFields" class="row g-3 mt-3 d-none">
            <div class="col-md-6">
              <label class="form-label">Purchase Source</label>
              <input type="text" class="form-control" name="purchase_source" id="purchase_source">
            </div>

            <div class="col-md-6">
              <label class="form-label">Payment Method</label>
              <select class="form-select" name="payment_method" id="payment_method">
                <option value="">-- Select Method --</option>
                <option value="Cash">Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Mobile Wallet">Mobile Wallet</option>
                <option value="Cheque">Cheque</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Purchased By</label>
              <select class="form-select" name="purchased_by" id="purchased_by">
                <option value="">-- Select User --</option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= $u['id'] ?>"><?= html_escape($u['firstname'].' '.$u['lastname']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Payment By</label>
              <select class="form-select" name="payment_user" id="payment_user">
                <option value="">-- Select User --</option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= $u['id'] ?>"><?= html_escape($u['firstname'].' '.$u['lastname']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4">
            <i class="ti ti-check me-1"></i> Save Purchase
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Auto-calc total
  document.getElementById('cost_per_item').addEventListener('input', calcTotal);
  document.getElementById('required_quantity').addEventListener('input', calcTotal);

  function calcTotal() {
    const cost = parseFloat(document.getElementById('cost_per_item').value) || 0;
    const qty  = parseInt(document.getElementById('required_quantity').value) || 0;
    document.getElementById('total_amount').value = (cost * qty).toFixed(2);
  }

  // Toggle purchased fields
  document.getElementById('purchase_status').addEventListener('change', function() {
    if (this.value === 'Purchased') {
      document.getElementById('purchasedFields').classList.remove('d-none');
    } else {
      document.getElementById('purchasedFields').classList.add('d-none');
    }
  });

  // Clear modal for Add
  function clearPurchaseForm() {
    document.getElementById('purchaseForm').reset();
    document.getElementById('purchase_id').value = '';
    document.getElementById('purchaseModalLabel').innerHTML = '<i class="ti ti-plus me-1"></i> Add New Purchase';
    document.getElementById('purchasedFields').classList.add('d-none');
  }

  // Load data for Edit
  function editPurchase(purchase) {
    clearPurchaseForm();
    document.getElementById('purchaseModalLabel').innerHTML = '<i class="ti ti-edit me-1"></i> Edit Purchase';
    document.getElementById('purchase_id').value        = purchase.id;
    document.getElementById('purchase_title').value     = purchase.purchase_title;
    document.getElementById('asset_type_id').value      = purchase.asset_type_id;
    document.getElementById('description').value        = purchase.description ?? '';
    document.getElementById('required_quantity').value  = purchase.required_quantity;
    document.getElementById('cost_per_item').value      = purchase.cost_per_item;
    document.getElementById('total_amount').value       = purchase.total_amount;
    document.getElementById('date_required').value      = purchase.date_required;
    document.getElementById('purchase_status').value    = purchase.purchase_status;

    if (purchase.purchase_status === 'Purchased') {
      document.getElementById('purchase_source').value  = purchase.purchase_source ?? '';
      document.getElementById('payment_method').value   = purchase.payment_method ?? '';
      document.getElementById('purchased_by').value     = purchase.purchased_by ?? '';
      document.getElementById('payment_user').value     = purchase.payment_user ?? '';
      document.getElementById('purchasedFields').classList.remove('d-none');
    }

    var modal = new bootstrap.Modal(document.getElementById('purchaseModal'));
    modal.show();
  }
</script>
