<!-- Header -->
<div class="card-header bg-light-primary mb-2">
    <div class="d-flex align-items-center justify-content-between">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-map me-2" style="font-size:18px;"></i>
            Company Offices
        </h6>
        <button type="button"
                class="btn btn-primary btn-header"
                data-bs-toggle="modal"
                data-bs-target="#addCompanyOfficeModal">
            <i class="ti ti-plus"></i> New Office
        </button>
    </div>
</div>

    
<!-- OFFICES GRID -->
<div class="row g-3">

<?php foreach ($offices as $o): ?>
    <div class="col-xl-4 col-lg-6 col-md-6">

        <div class="card h-100 border">

            <!-- CARD HEADER -->
            <div class="card-header d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-0 fw-semibold">
                        <?= html_escape($o['office_name']) ?>
                        <?php if ($o['is_head_office']): ?>
                            <span class="badge bg-primary ms-2">HQ</span>
                        <?php endif; ?>
                    </h6>
                    <small class="text-muted">
                        Code: <?= html_escape($o['office_code']) ?>
                    </small>
                </div>

                <span class="badge <?= $o['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                    <?= $o['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </div>

            <!-- CARD BODY -->
            <div class="card-body small">

                <div class="mb-2">
                    <strong>Address</strong> 
                    <?= html_escape($o['address_line_1']) ?>
                    <?php if (!empty($o['address_line_2'])): ?>
                        , <?= html_escape($o['address_line_2']) ?>
                    <?php endif; ?>
                </div>

                <div class="mb-2">
                    <strong>City:</strong> <?= html_escape($o['city']) ?><br>
                    <strong>State:</strong> <?= html_escape($o['state']) ?><br>
                    <strong>Postal Code:</strong> <?= html_escape($o['postal_code']) ?><br>
                    <strong>Country:</strong> <?= html_escape($o['country']) ?>
                </div>

                <div class="mb-2">
                    <strong>Phone:</strong> <?= html_escape($o['phone']) ?><br>
                    <strong>Email:</strong> <?= html_escape($o['email'] ?: '-') ?>
                </div>

                <div class="mb-2">
                    <strong>Timezone:</strong> <?= html_escape($o['timezone']) ?><br>
                    <strong>Currency:</strong> <?= html_escape($o['currency']) ?>
                </div>

            </div>

            <!-- CARD FOOTER -->
            <div class="card-footer d-flex btn-group btn-group-sm" role="group" aria-label="Small button group">

                <button type="button"
                  class="btn btn-outline-secondary edit-office"
                  data-office-id="<?= (int)$o['id'] ?>"
                  data-office-code="<?= html_escape($o['office_code']) ?>"
                  data-office-name="<?= html_escape($o['office_name']) ?>"
                  data-address1="<?= html_escape($o['address_line_1']) ?>"
                  data-address2="<?= html_escape($o['address_line_2']) ?>"
                  data-city="<?= html_escape($o['city']) ?>"
                  data-state="<?= html_escape($o['state']) ?>"
                  data-postal="<?= html_escape($o['postal_code']) ?>"
                  data-country="<?= html_escape($o['country']) ?>"
                  data-phone="<?= html_escape($o['phone']) ?>"
                  data-email="<?= html_escape($o['email']) ?>"
                  data-timezone="<?= html_escape($o['timezone']) ?>"
                  data-currency="<?= html_escape($o['currency']) ?>"
                  data-head="<?= (int)$o['is_head_office'] ?>"
                  data-active="<?= (int)$o['is_active'] ?>">
                    <i class="ti ti-pencil me-1"></i> Edit
                </button>

                <?= delete_link([
                    'url'   => 'admin/setup/delete_office/' . $o['id'],
                    'label' => 'Delete',
                    'class' => 'btn btn-outline-secondary',
                ]) ?>
                            
            </div>


        </div>

    </div>
<?php endforeach; ?>

</div>


<?php $CI =& get_instance(); ?>
<?= $CI->load->view('admin/setup/company/modals/office_add_modal', true); ?>
<?= $CI->load->view('admin/setup/company/modals/office_edit_modal', true); ?>

<script>
document.addEventListener('click', function(e){
  const btn = e.target.closest('.edit-office');
  if(!btn) return;

  const modal = document.getElementById('editCompanyOfficeModal');
  const form  = modal.querySelector('form');

  form.querySelector('[name="office_id"]').value = btn.dataset.officeId;
  form.querySelector('[name="office_code"]').value = btn.dataset.officeCode || '';
  form.querySelector('[name="office_name"]').value = btn.dataset.officeName || '';
  form.querySelector('[name="address_line_1"]').value = btn.dataset.address1 || '';
  form.querySelector('[name="address_line_2"]').value = btn.dataset.address2 || '';
  form.querySelector('[name="city"]').value = btn.dataset.city || '';
  form.querySelector('[name="state"]').value = btn.dataset.state || '';
  form.querySelector('[name="postal_code"]').value = btn.dataset.postal || '';
  form.querySelector('[name="country"]').value = btn.dataset.country || '';
  form.querySelector('[name="phone"]').value = btn.dataset.phone || '';
  form.querySelector('[name="email"]').value = btn.dataset.email || '';
  form.querySelector('[name="timezone"]').value = btn.dataset.timezone || 'UTC';
  form.querySelector('[name="currency"]').value = btn.dataset.currency || 'USD';
  form.querySelector('[name="is_head_office"]').checked = btn.dataset.head === '1';
  form.querySelector('[name="is_active"]').checked = btn.dataset.active === '1';

  if (window.bootstrap && bootstrap.Modal) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  } else if (window.jQuery && typeof jQuery(modal).modal === 'function') {
    jQuery(modal).modal('show');
  }
});
</script>