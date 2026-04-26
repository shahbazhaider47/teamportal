<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<form method="post" class="app-form">
<div class="container-fluid">

  <h5 class="mb-3">Edit Client</h5>

  <?php $c = $client; ?>

  <div class="card card-body">
    <div class="row">

      <div class="col-md-3 mb-3">
        <label class="form-label">Client Code *</label>
        <input type="text" name="client_code" class="form-control"
               value="<?= e($c['client_code']); ?>" required>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Practice Name *</label>
        <input type="text" name="practice_name" class="form-control"
               value="<?= e($c['practice_name']); ?>" required>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Legal Name</label>
        <input type="text" name="practice_legal_name" class="form-control"
               value="<?= e($c['practice_legal_name']); ?>">
      </div>

      <!-- repeat fields same as add, all prefilled -->

      <div class="col-md-12 mb-3">
        <label class="form-label">Internal Notes</label>
        <textarea name="internal_notes" class="form-control"
                  rows="3"><?= e($c['internal_notes']); ?></textarea>
      </div>

    </div>
  </div>

  <button type="submit" class="btn btn-primary">Update Client</button>
  <a href="<?= site_url('finance/clients'); ?>" class="btn btn-secondary">Cancel</a>

</div>
</form>
