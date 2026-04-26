<?php
$canView   = staff_can('view_global', 'todos') || staff_can('view_own', 'todos');
$canCreate = staff_can('create', 'todos');

if ($canView):
  $completed      = array_filter($todos ?? [], fn($t) => (int)$t['is_completed'] === 1);
  $not_completed  = array_filter($todos ?? [], fn($t) => (int)$t['is_completed'] === 0);
?>
<div class="card">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold text-primary">
            <i class="ti ti-list-check me-1"></i>
            My To-Do List
        </span>
    <div class="d-flex align-items-center">
      <a href="<?= site_url('todo') ?>" class="small text-primary me-3">View All</a>

      <a href="<?= $canCreate ? 'javascript:void(0);' : 'javascript:void(0);' ?>"
         class="badge <?= $canCreate ? 'bg-primary text-white' : 'bg-secondary text-white opacity-75 disabled' ?> small align-middle"
         <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#todoModal"' : 'aria-disabled="true"' ?>
         title="<?= $canCreate ? 'Add New' : 'No permission to add' ?>">
        Add New
      </a>
    </div>
  </div>

  <div class="card-body">
    <?php if (!empty($not_completed)): ?>
      <ul class="list-group small list-group-flush mb-2">
        <?php foreach ($not_completed as $todo): ?>
          <li class="list-group-item px-0 py-2 d-flex align-items-center">
            <input 
              type="checkbox" 
              class="form-check-input rounded-circle me-3 todo-widget-toggle"
              data-id="<?= (int)$todo['id'] ?>"
              style="width:1.1em;height:1.1em;"
              <?= (int)$todo['is_completed'] === 1 ? 'checked' : '' ?>
            >
            <span class="text-primary fw-medium"><?= html_escape($todo['todo_name']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($completed)): ?>
      <ul class="list-group small list-group-flush">
        <?php foreach ($completed as $todo): ?>
          <li class="list-group-item px-0 py-2 d-flex align-items-center">
            <input 
              type="checkbox" 
              class="form-check-input rounded-circle me-3 todo-widget-toggle"
              data-id="<?= (int)$todo['id'] ?>"
              style="width:1.1em;height:1.1em;"
              <?= (int)$todo['is_completed'] === 1 ? 'checked' : '' ?>
            >
            <span class="text-success fw-medium text-decoration-line-through"><?= html_escape($todo['todo_name']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (empty($not_completed) && empty($completed)): ?>
      <p class="text-muted mb-0">No to-do items found.</p>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  $('.todo-widget-toggle').on('change', function() {
    var checkbox = $(this);
    var todoId   = checkbox.data('id');
    var checked  = checkbox.is(':checked') ? 1 : 0;
    checkbox.prop('disabled', true);

    $.ajax({
      url: '<?= site_url('todo/toggle_status') ?>',
      method: 'POST',
      data: { id: todoId, status: checked },
      dataType: 'json',
      success: function(resp) {
        if (resp.success) {
          var span = checkbox.siblings('span');
          if (checked) {
            span.removeClass('text-primary').addClass('text-success text-decoration-line-through');
          } else {
            span.removeClass('text-success text-decoration-line-through').addClass('text-primary');
          }
        } else {
          checkbox.prop('checked', !checked);
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
<?php endif; // $canView ?>
