<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
          $canCreate  = staff_can('create', 'todos');
        ?>
    
        <button type="button"
                class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                title="<?= $canCreate ? 'Add New Todo' : 'No permission to create' ?>"
                <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#todoModal"' : 'disabled aria-disabled="true"' ?>>
          <i class="fas fa-plus me-1"></i> Add New
        </button>
        
        <div class="btn-divider"></div>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'todoTable' ?>">
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

  <div class="card">
    <div class="list-table-header app-scroll text-muted">
      <?php if (empty($todos)): ?>
        <div class="p-4 text-muted text-center">No to-do items yet.</div>
      <?php else: ?>
        <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="todoTable">
            <thead class="bg-light-primary">
              <tr>
                <th style="width:60%;">To Do Title</th>
                <th class="text-center" style="width:10%;">Related To</th>
                <th class="text-center" style="width:5%;">By</th>
                <th class="text-center" style="width:5%;">Status</th>
                <th class="text-center" style="width:10%;">Created On</th>
                <th class="text-center" style="width:10%;">Finished On</th>
              </tr>
            </thead>

          <tbody>
            <?php foreach ($todos as $todo): ?>
                <tr data-id="<?= (int)$todo['id'] ?>">
                  <!-- To-Do Title -->
                  <td>
                    <div class="form-check d-flex align-items-center mb-0">
                      <input 
                        class="form-check-input rounded-circle todo-toggle"
                        type="checkbox"
                        value="1"
                        <?= !empty($todo['is_completed']) ? 'checked' : '' ?>
                        style="width: 1.25em; height: 1.25em;"
                        data-id="<?= (int)$todo['id'] ?>"
                      >
                      <label class="form-check-label ms-3 <?= !empty($todo['is_completed']) ? 'text-muted text-decoration-line-through' : 'text-primary' ?>"
                             style="font-size:1.08em; font-weight:500;">
                        <?= html_escape($todo['todo_name']) ?>
                      </label>
                    </div>
                  </td>
                
                  <!-- Related To -->
                  <td class="text-center">
                    <?php
                      $relDisplay = '<span class="text-muted">—</span>';
                
                      if (!empty($todo['rel_type']) && !empty($todo['rel_id'])) {
                          $relType = strtolower(trim((string)$todo['rel_type']));
                          $relId   = (int)$todo['rel_id'];
                
                          $url   = '';
                          $label = '';
                
                          switch ($relType) {
                              case 'task':
                              case 'tasks':
                                  $url   = site_url('tasks/view/' . $relId);
                                  $label = 'Task #' . $relId;
                                  break;
                
                              case 'project':
                              case 'projects':
                                  $url   = site_url('projects/view/' . $relId);
                                  $label = 'Project #' . $relId;
                                  break;
                
                              default:
                                  // Generic fallback: "Type #ID"
                                  $label = ucfirst($relType) . ' #' . $relId;
                                  // If later you add matching routes, you can extend the switch above.
                                  break;
                          }
                
                          if ($url) {
                              $relDisplay = '<a href="' . html_escape($url) . '" class="text-primary">' . html_escape($label) . '</a>';
                          } else {
                              $relDisplay = html_escape($label);
                          }
                      }
                
                      echo $relDisplay;
                    ?>
                  </td>
                
                    <td class="text-center">
                      <?= !empty($todo['created_by_name']) ? user_profile($todo['created_by_name']) : '<span class="text-muted">—</span>' ?>
                    </td>
                
                  <!-- Status -->
                  <td class="text-center">
                    <?php if (!empty($todo['is_completed'])): ?>
                      <span class="pill pill-success">Completed</span>
                    <?php else: ?>
                      <span class="pill pill-danger">Pending</span>
                    <?php endif; ?>
                  </td>
                
                  <!-- Date Created -->
                  <td class="text-center">
                    <?= $todo['created_at'] ? date('M d, Y', strtotime($todo['created_at'])) : '<span class="text-muted">—</span>' ?>
                  </td>
                
                  <!-- Date Completion -->
                  <td class="text-center">
                    <?php if (!empty($todo['is_completed']) && !empty($todo['completed_at'])): ?>
                      <?= date('M d, Y', strtotime($todo['completed_at'])) ?>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                </tr>

            <?php endforeach; ?>
          </tbody>
        </table>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php $CI = &get_instance(); echo $CI->load->view('modals/todo_modal', [], true); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  $('.todo-toggle').on('change', function() {
    var checkbox = $(this);
    var row = checkbox.closest('tr');
    var todoId = checkbox.data('id');
    var checked = checkbox.is(':checked') ? 1 : 0;

    checkbox.prop('disabled', true); // Prevent double-click

    $.ajax({
      url: '<?= site_url('todo/toggle_status') ?>',
      method: 'POST',
      data: { id: todoId, status: checked },
      dataType: 'json',
      success: function(resp) {
        if (resp.success) {
          // Update label style
          var label = checkbox.siblings('label');
          if (checked) {
            label.removeClass('text-primary').addClass('text-muted text-decoration-line-through');
            row.find('.pill').removeClass('pill-danger').addClass('pill-success').text('Completed');
          } else {
            label.removeClass('text-muted text-decoration-line-through').addClass('text-primary');
            row.find('.pill').removeClass('pill-success').addClass('pill-danger').text('Pending');
          }
        }
        checkbox.prop('disabled', false);
      },
      error: function() {
        alert('Failed to update status. Please try again.');
        checkbox.prop('checked', !checked);
        checkbox.prop('disabled', false);
      }
    });
  });
});
</script>
