<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">

        <a href="<?= site_url('users/profile') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-user-circle"></i> Profile
        </a>
        
        <a href="<?= site_url('users/activity') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-activity"></i> Activity
        </a>
        
        <div class="btn-divider"></div>

        <a href=""
           class="btn btn-primary btn-header">
            <i class="ti ti-settings"></i> Settings
        </a>
        
      </div>
    </div>
    
<?php $CI =& get_instance(); ?>
<div class="settings-card bg-white p-4 mb-4 rounded-3 shadow-sm">
  <h5 class="form-section-title text-primary mb-4">
    <i class="fas fa-user-circle me-2"></i> Profile Settings
  </h5>
  
  <form action="<?= site_url('users/update_settings') ?>" method="post" enctype="multipart/form-data" class="needs-validation app-form" novalidate>
    <div class="row g-4">
    <!-- Profile Image Section -->
    <div class="col-md-3">
      <div class="d-flex flex-column align-items-center">
    
        <div class="position-relative mb-3" style="width: 156px; height: 156px;">
          <img
            src="<?= user_avatar_url($user['profile_image'] ?? null) ?>"
            class="rounded-circle object-fit-cover border border-3 border-light shadow-sm w-100 h-100"
            id="profile-img-preview"
            alt="<?= html_escape(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?> profile photo"
            width="156" height="156"
            onerror="this.onerror=null;this.src='<?= base_url('assets/images/default.png') ?>';">
        </div>
    
        <!-- Hidden file input -->
        <input
          type="file"
          name="profile_image"
          id="profile-image-input"
          class="d-none"
          accept="image/jpeg,image/png"
          data-max-bytes="<?= 2 * 1024 * 1024 ?>">
    
        <!-- Inline actions -->
        <div class="d-flex align-items-center gap-2">
          <button type="button"
                  class="btn btn-outline-primary btn-sm"
                  onclick="document.getElementById('profile-image-input').click()">
            <i class="ti ti-camera me-1"></i> Change
          </button>
    
          <?php if (!empty($user['profile_image']) && strtolower($user['profile_image']) !== 'default.png'): ?>
            <button type="button"
                    class="btn btn-outline-danger btn-sm"
                    onclick="confirmDeletePhoto()">
              <i class="ti ti-trash me-1"></i> Remove
            </button>
          <?php endif; ?>
        </div>
    
        <small class="text-muted mt-2">JPG or PNG • Max 2&nbsp;MB</small>
    
        <!-- Optional hidden flag to remove on submit -->
        <input type="hidden" name="remove_profile_image" id="remove-profile-image-flag" value="0">
      </div>
    </div>

      <!-- Personal Information Section -->
      <div class="col-md-9">
        <div class="row g-3">
          <div class="col-md-4">
            <label for="firstname" class="form-label fw-semibold">
              First Name <span class="text-danger">*</span>
            </label>
            <input type="text" 
                   id="firstname"
                   name="firstname" 
                   value="<?= html_escape($user['firstname']); ?>" 
                   class="form-control" 
                   required
                   pattern="[A-Za-z ]{2,}"
                   title="Please enter at least 2 alphabetical characters">
            <div class="invalid-feedback">
              Please provide a valid first name.
            </div>
          </div>

          <div class="col-md-4">
            <label for="lastname" class="form-label fw-semibold">Last Name</label>
            <input type="text" 
                   id="lastname"
                   name="lastname" 
                   value="<?= html_escape($user['lastname']); ?>" 
                   class="form-control"
                   pattern="[A-Za-z ]*"
                   title="Please enter alphabetical characters only">
          </div>

          <div class="col-md-4">
            <label for="fullname" class="form-label fw-semibold">Full Name</label>
            <input type="text" 
                   id="fullname"
                   name="fullname" 
                   value="<?= html_escape($user['fullname']); ?>" 
                   class="form-control"
                   pattern="[A-Za-z ]*"
                   title="Please enter alphabetical characters only">
          </div>
          
          <div class="col-md-4">
            <label for="email" class="form-label fw-semibold">
              Email Address <span class="text-danger">*</span>
            </label>
            <input type="email" 
                   id="email"
                   name="email" 
                   value="<?= html_escape($user['email']); ?>" 
                   class="form-control" 
                   required>
            <div class="invalid-feedback">
              Please provide a valid email address.
            </div>
          </div>

          <div class="col-md-4">
            <label for="phone" class="form-label fw-semibold">Phone Number</label>
            <input type="tel" 
                   id="phone"
                   name="emp_phone" 
                   value="<?= html_escape($user['emp_phone'] ?? ''); ?>" 
                   class="form-control"
                   pattern="[0-9]{10,15}"
                   title="Please enter a valid phone number">
          </div>
          
          <div class="col-md-4">
            <label class="form-label fw-semibold">Username</label> <small class="text-muted small">(Cannot be changed)</small>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-at"></i></span>
              <input type="text" 
                     value="<?= html_escape($user['username']); ?>" 
                     class="form-control" 
                     disabled
                     aria-label="Username">
            </div>
          </div>
          
          <div class="col-md-4">
            <label for="birthdate" class="form-label fw-semibold">Date of Birth</label>
            <input type="date" 
                   id="birthdate"
                   name="emp_dob" 
                   value="<?= html_escape($user['emp_dob'] ?? ''); ?>" 
                   class="form-control"
                   max="<?= date('Y-m-d'); ?>">
          </div>

            <div class="col-md-4">    
              <div class="mb-3">
                <label for="blood_group" class="form-label">Blood Group</label>
                <select class="form-select" id="blood_group" name="blood_group">
                  <option value="">Select Blood Group</option>
                  <?php foreach ($blood_group_types as $bgt): ?>
                    <option value="<?= e($bgt) ?>" <?= (($user['blood_group'] ?? '') === $bgt) ? 'selected' : '' ?>>
                      <?= e($bgt) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($blood_group_types)): ?>
                  <div class="form-text text-muted">No Blood Group Types configured in System Options.</div>
                <?php endif; ?>
              </div>
            </div>
        
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
      <button type="submit" class="btn btn-primary btn-sm px-4">
        <i class="fas fa-save me-2"></i> Save Changes
      </button>
    </div>
  </form>
</div>
</div>


<script>
// Profile image preview functionality
document.getElementById('profile-image-input').addEventListener('change', function(e) {
  const [file] = e.target.files;
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      alert('File size exceeds 2MB limit');
      return;
    }
    
    const preview = document.getElementById('profile-img-preview');
    preview.src = URL.createObjectURL(file);
    preview.onload = () => URL.revokeObjectURL(preview.src);
  }
});

function confirmDeletePhoto() {
  if (confirm('Are you sure you want to remove your profile photo?')) {
    // Implement photo removal logic here
    window.location.href = '<?= site_url('users/remove_profile_photo') ?>';
  }
}

// Form validation
(() => {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>

<style>
.profile-img-container {
  width: 150px;
  height: 150px;
}

.profile-img-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.form-section-title {
  font-size: 1.25rem;
  font-weight: 600;
  position: relative;
  padding-bottom: 0.5rem;
}

.form-section-title:after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 50px;
  height: 3px;
  background: linear-gradient(90deg, #0d6efd, transparent);
  border-radius: 3px;
}
</style>
<script>
  (function () {
    const input   = document.getElementById('profile-image-input');
    const preview = document.getElementById('profile-img-preview');

    if (!input || !preview) return;

    input.addEventListener('change', function (e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;

      const allowedTypes = ['image/jpeg', 'image/png'];
      const maxSize      = 2 * 1024 * 1024; // 2MB

      if (!allowedTypes.includes(file.type)) {
        alert('Only JPG and PNG files are allowed.');
        input.value = '';
        return;
      }
      if (file.size > maxSize) {
        alert('File size should not exceed 2MB.');
        input.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (evt) {
        preview.src = evt.target.result;
      };
      reader.readAsDataURL(file);
    });

    window.confirmDeletePhoto = function () {
      if (confirm('Are you sure you want to remove your profile photo?')) {
        window.location.href = '<?= site_url('users/remove_profile_photo') ?>';
      }
    };
  })();

  // Bootstrap-style client-side validation (yours was fine; keeping it)
  (function () {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
</script>

