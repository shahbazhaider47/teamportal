<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal-header">
  <h5 class="modal-title">Feedback Details</h5>
</div>

<div class="modal-body">

<?php foreach ($schema['questions'] as $q): ?>
  <div class="mb-2">
    <strong><?= html_escape($q['label']); ?></strong><br>
    <?= html_escape($answers[$q['id']] ?? '-'); ?>
  </div>
<?php endforeach; ?>

<hr>
<p><strong>Average Score:</strong> <?= $submission['average_score']; ?></p>

</div>
