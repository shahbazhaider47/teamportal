<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canAdd    = staff_can('create', 'contracts');          
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
        
        <a href="javascript:void(0);"
           class="btn btn-header <?= $canAdd ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           <?= $canAdd ? 'data-bs-toggle="modal" data-bs-target="#documentModal" onclick="clearDocumentForm()"' : '' ?>
           title="Add Staff">
           <i class="ti ti-plus"></i> Add New
        </a>
        
        <div class="btn-divider"></div>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'hrmusersTable' ?>">
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
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover small align-middle" id="hrmusersTable">
                    <thead class="bg-light-primary">
                        <tr>
                            <th>ID #</th>
                            <th>Document Title</th>
                            <th>Associated With</th>
                            <th>Doc Type</th>
                            <th>Attached File</th>
                            <th>Created At</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($documents): ?>
                            <?php foreach ($documents as $i => $doc): ?>
                            <tr>
                                <td><?= html_escape($doc['id']) ?></td>
                                <td>
                                    <strong><?= html_escape($doc['title']) ?></strong>
                                    <?php if($doc['description']): ?>
                                        <div class="text-muted small">
                                            <?= html_escape(
                                                mb_strimwidth($doc['description'], 0, 35, (strlen($doc['description']) > 35 ? '...' : ''))
                                            ) ?>
                                        </div>

                                    <?php endif ?>
                                </td>
                                
                                <td>
                                <?php if (empty($doc['user_id'])): ?>
                                    <div class="d-flex align-items-center gap-2 fw-semibold text-primary">
                                       <span class="text-light-primary h-25 w-25 d-flex-center b-r-50">
                                        <i class="ti ti-building"></i>
                                       </span>
                                        Company
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <?= user_profile_image($doc['user_id']) ?>
                                    </div>
                                <?php endif; ?>
                                </td>
                                
                                <td><?= html_escape($doc['doc_type']) ?></td>
                                <td>
                                    <?php if($doc['file_path']): ?>
                                        <div class="text-muted small mt-1">
                                            <?= strtoupper(substr($doc['file_path'], 1)) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No file</span>
                                    <?php endif ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($doc['created_at'])) ?></td>
                                <td>
                                    <?php if($doc['expiry_date']): ?>
                                        <?php 
                                            $expiry_date = new DateTime($doc['expiry_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($expiry_date);
                                            
                                            $class = '';
                                            if($expiry_date < $today) {
                                                $class = 'text-danger';
                                            } elseif($interval->days <= 30) {
                                                $class = 'text-warning';
                                            }
                                        ?>
                                        <span class="<?= $class ?>">
                                            <?= date('M d, Y', strtotime($doc['expiry_date'])) ?>
                                            <?php if($expiry_date < $today): ?>
                                                <span class="badge bg-danger">Expired</span>
                                            <?php elseif($interval->days <= 30): ?>
                                                <span class="badge bg-warning">Expiring</span>
                                            <?php endif ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        
                                        <?php if($doc['file_path']): ?>
                                            <a href="<?= base_url('uploads/hrm/documents/'.$doc['file_path']) ?>"
                                               target="_blank"
                                               class="btn btn-ssm btn-light-info"
                                               title="View Document">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        <?php endif ?>

                                        <button class="btn btn-ssm btn-light-primary" title="Edit Document" onclick="editDocument(<?= $doc['id'] ?>)">
                                            <i class="ti ti-edit"></i>
                                        </button>

                                        <a href="<?= site_url('users/delete_document/'.$doc['id']) ?>" onclick="return confirm('Are you sure you want to delete this document?')" class="btn btn-ssm btn-light-danger" title="Delete Document">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No documents found</td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($this->pagination->create_links()): ?>
                <div class="mt-3">
                    <?= $this->pagination->create_links() ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

<?php $CI =& get_instance(); ?>
<?= $CI->load->view('users/modals/add_document_modal', ['users' => $users], true); ?>


<script>

function toggleEmployeeSelect(scope) {
    if (scope === 'company') {
        $('#employeeSelectBox').hide();
        $('#user_id').prop('required', false).val('');
    } else {
        $('#employeeSelectBox').show();
        $('#user_id').prop('required', true);
    }
}

// On modal open, set field visibility based on current value
$('#documentModal').on('show.bs.modal', function() {
    const currentScope = $('#doc_scope').val();
    toggleEmployeeSelect(currentScope);
});

// On scope change (already in your select)
$('#doc_scope').on('change', function() {
    toggleEmployeeSelect(this.value);
});

    
function clearDocumentForm() {
    $('#documentForm')[0].reset();
    $('#doc_id').val('');
    $('#documentModalLabel').text('Add Document');
}

function editDocument(id) {
    $.get('<?= site_url('users/get_document') ?>/' + id, function(res) {
        if (res) {
            $('#doc_id').val(res.id);
            $('[name="user_id"]').val(res.user_id);
            $('#doc_title').val(res.title);
            $('#doc_type').val(res.doc_type);
            $('#doc_description').val(res.description);
            $('#doc_expiry_date').val(res.expiry_date);
            
            $('#documentModalLabel').text('Edit Document');
            $('#documentModal').modal('show');
        } else {
            alert('Failed to load document!');
        }
    }, 'json');
}

// Handle form submission with AJAX
$('#documentForm').submit(function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert(response.message);
                $('#documentModal').modal('hide');
                window.location.reload();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('An error occurred while processing your request.');
        }
    });
});
</script>