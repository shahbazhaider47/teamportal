<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView    = staff_can('view', 'assets');
          $canDelete = staff_can('delete', 'assets');
          $canCreate  = staff_can('view', 'assets');          
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>

        <a href="<?= $canView ? base_url('asset') : 'javascript:void(0);' ?>"
           class="btn <?= $canView ? 'btn-primary' : 'btn-disabled' ?> btn-header"
           title="Go to Inventory"
           <?= $canView ? '' : 'disabled' ?>>
           <i class="fas fa-boxes me-1"></i> Assets
        </a>

        <a href="<?= $canView ? base_url('asset/inventory') : 'javascript:void(0);' ?>"
           class="btn <?= $canView ? 'btn-outline-primary' : 'btn-disabled' ?> btn-header"
           title="Go to Inventory"
           <?= $canView ? '' : 'disabled' ?>>
           <i class="ti ti-tools"></i> Inventory
        </a>

        <a href="<?= $canView ? base_url('asset/new_purchases') : 'javascript:void(0);' ?>"
           class="btn <?= $canView ? 'btn-outline-primary' : 'btn-disabled' ?> btn-header"
           title="New Purchases"
           <?= $canView ? '' : 'disabled' ?>>
           <i class="ti ti-shopping-cart-plus"></i> New Purchases
        </a>
        
        <div class="btn-divider"></div>

        <!-- Assign Asset -->
        <button type="button"
                class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#assignAssetModal"' : 'disabled' ?>
                title="Assign Asset">
          <i class="fas fa-plus me-1"></i> Assign Asset
        </button>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'assetsTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
     
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Table of asset -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bottom-border small align-middle table-hover" id="assetsTable">
                    <thead class="bg-light-primary">
                        <tr>
                            <th>Serial No</th>
                            <th>Asset Name</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $i => $a): ?>
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
                                <td>
                                  <?php if (!empty($a['firstname'])): ?>
                                      <?= html_escape($a['firstname'] . ' ' . $a['lastname']) ?>
                                  <?php elseif (!empty($a['department_name'])): ?>
                                      <span class="badge bg-light-primary"><?= html_escape($a['department_name']) ?></span>
                                  <?php else: ?>
                                      <span class="text-muted">Unassigned</span>
                                  <?php endif; ?>
                                </td>

                                <td class="text-capitalize"><?= html_escape($a['status']) ?></td>
                                <td><?= html_escape($a['asset_type']) ?></td>
                                <td>
                                  <?= isset($a['price']) && $a['price'] > 0
                                      ? html_escape(get_base_currency_symbol()) . number_format($a['price'])
                                      : '-' ?>
                                </td>
<td>
  <div class="btn-group btn-group-sm" role="group" aria-label="Asset Actions">
    <!-- View -->
    <button class="btn btn-outline-secondary btn-sm" onclick="viewAsset(<?= $a['id'] ?>)">
      <i class="ti ti-eye"></i>
    </button>

    <!-- Reassign -->
    <button type="button" class="btn btn-outline-secondary btn-sm" 
            onclick="openReassignModal(<?= $a['id'] ?>)">
      <i class="ti ti-refresh"></i>
    </button>

    <!-- Status Dropdown -->
<div class="btn-group btn-group-sm dropdown" role="group">
  <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
          data-bs-toggle="dropdown" 
          aria-expanded="false">
    <i class="ti ti-arrows-sort"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-sm shadow">
    <li><a class="dropdown-item small text-success" href="javascript:void(0);" onclick="updateAssetStatus(<?= $a['id'] ?>, 'available')">Available</a></li>
    <li><a class="dropdown-item small text-warning" href="javascript:void(0);" onclick="updateAssetStatus(<?= $a['id'] ?>, 'maintenance')">Maintenance</a></li>
    <li><a class="dropdown-item small text-danger" href="javascript:void(0);" onclick="updateAssetStatus(<?= $a['id'] ?>, 'lost')">Lost</a></li>
    <li><a class="dropdown-item small text-muted" href="javascript:void(0);" onclick="updateAssetStatus(<?= $a['id'] ?>, 'retired')">Retired</a></li>
  </ul>
</div>



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
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('asset/modals/assign_asset', [
  'available'   => isset($available) ? $available : [],
  'users'       => isset($users) ? $users : [],
  'departments' => isset($departments) ? $departments : [] // <-- added
], true); ?>

<?php echo $CI->load->view('asset/modals/reassign_asset', [
  'users'       => isset($users) ? $users : [],
  'departments' => isset($departments) ? $departments : []
], true); ?>

<?php echo $CI->load->view('asset/modals/view_asset', [], true); ?>

<style>
/* Ensure dropdown shows correctly inside table */
.table-responsive {
  overflow: visible !important;
}

.dropdown-menu-sm {
  min-width: 120px;   /* smaller width */
  font-size: 0.8rem;  /* smaller text */
  padding: 0.25rem 0;
}
    
</style>
<script>
function updateAssetStatus(assetId, newStatus) {
  $.ajax({
    url: '<?= site_url("asset/update_status") ?>/' + assetId,
    type: 'POST',
    data: {status: newStatus},
    dataType: 'json',
    success: function(res) {
      if (res.success) {
        let row = $('#assetsTable').find('tr').filter(function() {
          return $(this).find('td:first').text().trim() == res.serial_no;
        });

        if (row.length > 0) {
          // update Status badge
          let statusBadge = '';
          switch(res.new_status) {
            case 'available': statusBadge = '<span class="badge bg-success">Available</span>'; break;
            case 'in-use':    statusBadge = '<span class="badge bg-primary">In Use</span>'; break;
            case 'damaged':   statusBadge = '<span class="badge bg-danger">Damaged</span>'; break;
            case 'retired':   statusBadge = '<span class="badge bg-secondary">Retired</span>'; break;
            default:          statusBadge = '<span class="badge bg-light text-dark">'+res.new_status+'</span>'; break;
          }
          row.find('td:nth-child(4)').html(statusBadge);

          // clear "Assigned To"
          row.find('td:nth-child(3)').html('<span class="text-muted">Unassigned</span>');
        }

        toastr.success('Asset status updated and unassigned.');
      } else {
        toastr.error('Failed to update status');
      }
    },
    error: function() {
      toastr.error('Error while updating status');
    }
  });
}

</script>
