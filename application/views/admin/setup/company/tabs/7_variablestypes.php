<style>
.type-chip .btn-close {
  filter: invert(18%) sepia(96%) saturate(7464%) hue-rotate(357deg) brightness(95%) contrast(160%);
  opacity: 1;
  font-size: 5px;
}
    
</style>
<?php

function read_types_array($key)
{
    $json = '[]';

    if (function_exists('get_company_setting')) {
        $raw  = get_company_setting($key, '[]');
        $json = (string) ($raw ?? '[]');
    }

    $arr = json_decode($json, true);

    return is_array($arr)
        ? array_values(array_unique(array_filter($arr, 'strlen')))
        : [];
}

$sections = [
  'employment_types'        => ['label' => 'Employment Types',     'icon' => 'ti ti-briefcase'],
  'contract_types'          => ['label' => 'Contract Types',       'icon' => 'ti ti-file-text'],
  'work_location_types'     => ['label' => 'Work Location Types',  'icon' => 'ti ti-map-pin'],
  'relationship_types'      => ['label' => 'Relationship Types',   'icon' => 'ti ti-users'],
  'blood_group_types'       => ['label' => 'Blood Group Types',    'icon' => 'ti ti-first-aid-kit'], 
  'employee_grades'         => ['label' => 'Employee Grades',      'icon' => 'ti ti-stars'],
  'qualifications_list'     => ['label' => 'Qualifications List',  'icon' => 'ti ti-tie'],
  'bank_names'              => ['label' => 'Bank Names List',      'icon' => 'ti ti-building-bank'],
];

$lists = [];
foreach ($sections as $key => $meta) {
  $lists[$key] = read_types_array($key);
}
$first_section_key = array_key_first($sections);
?>

<div class="innerboxgen app-form">
<?= form_open(
    site_url('admin/setup/company/save_variable_types'),
    ['method' => 'post', 'autocomplete' => 'off']
) ?>
    
<div class="card-header bg-light-primary mb-2">
  <div class="d-flex align-items-center justify-content-between">
    <div class="justify-content-between gap-2">
        <h6 class="card-title text-primary mb-0"><i class="ti ti-list me-2" style="font-size:18px;"></i> Default Variable Types</h6>
        <span class="text-muted small">
          Configure variable types used across forms to dynamically populate dropdown selections.<br>
          Changes to these types will not impact existing user records.
        </span>

    </div>
    
          <div class="d-flex align-items-center gap-2 mt-3" id="simpleAddToolbar">
            <select class="form-select form-select app-form" id="section_picker" style="min-width:auto">
                <option value="">--Selet Type--</option>
              <?php foreach ($sections as $key => $meta): ?>
                <option value="<?= e($key) ?>"><?= e($meta['label']) ?></option>
              <?php endforeach; ?>
            </select>

            <input type="text"
                   class="form-control form-control app-form"
                   id="new_value_input"
                   placeholder="Type value and press Add"
                   style="min-width:300px" />

            <button type="button" class="btn btn-sm btn-primary" style="min-width:100px" id="add_value_btn">
              <i class="ti ti-plus"></i> Add
            </button>
          </div>
          
  </div>
</div>


    <div class="row g-3">
      <?php foreach ($sections as $key => $meta): ?>
        <div class="col-lg-3">
          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-1 bg-light-primary">
              <div class="d-flex align-items-center gap-2 p-1">
                <i class="<?= e($meta['icon']) ?> text-primary"></i>
                <strong class="small mb-0"><?= e($meta['label']) ?></strong>
              </div>
            </div>

            <div class="card-body">
              <!-- Visible chips -->
              <div class="type-chip-wrap" id="<?= e($key) ?>_chips">
                <?php if (!empty($lists[$key])): ?>
                  <?php foreach ($lists[$key] as $val): ?>
                    <span class="badge bg-light-primary me-1 mb-1 type-chip"
                          data-value="<?= e($val) ?>" data-section="<?= e($key) ?>">
                      <span class="me-1"><?= e($val) ?></span>
                      <button type="button" class="btn-close text-danger ms-1 remove-chip" aria-label="Remove"></button>
                    </span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-muted small empty-hint">No entries yet</div>
                <?php endif; ?>
              </div>

              <input type="hidden"
                     name="settings[<?= e($key) ?>]"
                     id="<?= e($key) ?>_json"
                     value='<?= e(json_encode($lists[$key], JSON_UNESCAPED_UNICODE)) ?>' />
            </div>
            
          </div>
        </div>
      <?php endforeach; ?>
    </div>

        <div class="card-footer bg-white border-top mt-3">
          <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="ti ti-device-floppy me-1"></i> Save Changes
            </button>
          </div>
        </div>

  <?= form_close() ?>
</div>

<script>
(function(){
  function parseJsonField(id) {
    try { return JSON.parse(document.getElementById(id).value || '[]') || []; }
    catch(e){ return []; }
  }
  function writeJsonField(id, arr) {
    document.getElementById(id).value = JSON.stringify(arr);
  }
  function normalize(val) { return (val || '').trim().replace(/\s+/g, ' '); }
  function existsCaseInsensitive(arr, val) {
    var n = String(val).toLowerCase();
    return arr.some(function(x){ return String(x).toLowerCase() === n; });
  }
  function renderChips(section, arr) {
    var wrap = document.getElementById(section + '_chips');
    if (!wrap) return;
    wrap.innerHTML = '';
    if (!arr.length) {
      wrap.innerHTML = '<div class="text-muted small empty-hint">No entries yet.</div>';
      return;
    }
    arr.forEach(function(val){
      var html =
        '<span class="badge bg-light-secondary me-2 mb-2 type-chip" '+
        'data-value="'+ String(val).replace(/"/g,'&quot;') +'" data-section="'+ section +'">' +
          '<span class="me-1">'+ String(val).replace(/</g,'&lt;').replace(/>/g,'&gt;') +'</span>' +
          '<button type="button" class="btn-close text-danger ms-1 remove-chip" aria-label="Remove"></button>' +
        '</span>';
      wrap.insertAdjacentHTML('beforeend', html);
    });
  }

  <?php foreach (array_keys($sections) as $sec): ?>
  (function(){
    var arr = parseJsonField('<?= e($sec) ?>_json');
    renderChips('<?= e($sec) ?>', arr);
  })();
  <?php endforeach; ?>

  var sectionPicker = document.getElementById('section_picker');
  var input = document.getElementById('new_value_input');
  var addBtn = document.getElementById('add_value_btn');

  function addValue() {
    var sec = sectionPicker ? sectionPicker.value : '';
    var val = normalize(input ? input.value : '');
    if (!sec || !val) { if (input) input.focus(); return; }

    var fieldId = sec + '_json';
    var arr = parseJsonField(fieldId);

    if (!existsCaseInsensitive(arr, val)) {
      arr.push(val);
      arr.sort(function(a,b){ return a.toLowerCase().localeCompare(b.toLowerCase()); });
      writeJsonField(fieldId, arr);
      renderChips(sec, arr);
    } else {
      if (input) input.focus();
    }

    if (input) { input.value = ''; input.focus(); }
  }

  if (addBtn) addBtn.addEventListener('click', addValue);
  if (input) {
    input.addEventListener('keydown', function(e){
      if (e.key === 'Enter') { e.preventDefault(); addValue(); }
    });
  }

  document.addEventListener('click', function(e){
    if (!e.target.classList.contains('remove-chip')) return;
    var chip = e.target.closest('.type-chip');
    if (!chip) return;

    var sec = chip.dataset.section;
    var val = chip.dataset.value;
    var fieldId = sec + '_json';
    var arr = parseJsonField(fieldId)
      .filter(function(x){ return String(x).toLowerCase() !== String(val).toLowerCase(); });

    writeJsonField(fieldId, arr);
    renderChips(sec, arr);
  });
})();
</script>