<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <div class="bg-light-secondary page-header px-3 py-2 mb-3 rounded-3 shadow-sm d-flex align-items-center justify-content-between">
    <h1 class="h6 m-0">Map Users — Device #<?= (int)$device_id ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('attendance/biometric') ?>">Back</a>
  </div>

  <div class="card p-3 mb-3">
    <h6>Add Mapping</h6>
    <?= form_open(site_url('attendance/biometric/upsert_mapping/'.$device_id)) ?>
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">Device User ID</label>
          <input class="form-control" name="device_user_id" required placeholder="e.g., 12">
        </div>
        <div class="col-md-5">
          <label class="form-label">System User</label>
          <select class="form-select" name="user_id" required>
            <option value="">Select…</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= html_escape($u['fullname'] ?? ($u['firstname'].' '.$u['lastname'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">User Code (optional)</label>
          <input class="form-control" name="user_code" placeholder="RCM-001">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button class="btn btn-primary w-100">Save</button>
        </div>
      </div>
    <?= form_close() ?>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>#</th><th>Device User</th><th>System User</th><th>Code</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($mappings)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No mappings yet.</td></tr>
          <?php else: foreach ($mappings as $m): ?>
            <tr>
              <td><?= (int)$m['id'] ?></td>
              <td><?= html_escape($m['device_user_id']) ?></td>
              <td><?= html_escape($m['fullname']) ?></td>
              <td><?= html_escape($m['user_code'] ?? '') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-danger" href="<?= site_url('attendance/biometric/delete_mapping/'.$m['id'].'/'.$device_id) ?>" onclick="return confirm('Remove mapping?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
