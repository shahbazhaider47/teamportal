<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI      = get_instance();
$taskId  = (int)($task['id'] ?? 0);
$title   = (string)($task['name'] ?? '');
$desc    = (string)($task['description'] ?? ''); // may contain HTML from past; shown as plain text here
?>

<!-- Modal -->
<div class="modal fade" id="modalEditTaskDetails" tabindex="-1" aria-labelledby="modalEditTaskDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="modalEditTaskDetailsLabel">Edit Title & Description</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="post"
            action="<?= site_url('tasks/update_description/'.$taskId) ?>"
            class="app-form" id="formEditTaskDetails">

        <div class="modal-body">

          <div class="mb-3">
            <label for="taskTitle" class="form-label">Task Title</label>
            <input type="text"
                   class="form-control"
                   id="taskTitle"
                   name="name"
                   minlength="3"
                   required
                   placeholder="Enter task title"
                   value="<?= html_escape(set_value('name', $title)) ?>">
          </div>

          <div class="mb-3">
            <label for="taskDescription" class="form-label">Description</label>
            <textarea class="form-control"
                      id="taskDescription"
                      name="description"
                      rows="6"
                      placeholder="Enter task description..."><?= html_escape(set_value('description', strip_tags($desc))) ?></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Optional UX: focus title on open
document.addEventListener('shown.bs.modal', function (e) {
  if (e.target && e.target.id === 'modalEditTaskDetails') {
    const el = document.getElementById('taskTitle');
    if (el) el.focus();
  }
});
</script>
