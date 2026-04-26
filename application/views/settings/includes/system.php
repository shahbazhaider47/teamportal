<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Ensure existing data is an array
$existing_data = isset($existing_data) && is_array($existing_data) ? $existing_data : [];

// Date formats with live sample display
$date_formats = [
    'Y-m-d'       => 'ISO (2025-06-15)',
    'd-m-Y'       => 'European (15-06-2025)',
    'm/d/Y'       => 'US (06/15/2025)',
    'd M Y'       => 'Short Month (15 Jun 2025)',
    'F j, Y'      => 'Full Month (June 15, 2025)',
    'D, M j, Y'   => 'With Day (Sun, Jun 15, 2025)',
    'l, F j, Y'   => 'Full Day (Sunday, June 15, 2025)',
    'jS F Y'      => 'Ordinal (15th June 2025)',
    'Y.m.d'       => 'Dot Format (2025.06.15)',
    'd/m/Y'       => 'Slash Format (15/06/2025)',
    'M d, Y'      => 'Abbreviated (Jun 15, 2025)',
];

// Time formats with explanations
$time_formats = [
    'H:i'         => '24-hour (23:59)',
    'h:i A'       => '12-hour (11:59 PM)',
    'g:i A'       => '12-hour no zero (11:59 PM)',
    'H:i:s'       => '24-hour with seconds (23:59:59)',
    'h:i:s A'     => '12-hour with seconds (11:59:59 PM)',
    'g:i:s A'     => '12-hour no zero + seconds (11:59:59 PM)',
];

// Role list — consistent with application
$user_roles = ['Admin', 'Manager', 'Team Lead', 'Employee', 'Guest'];

// Popular and categorized timezones
$popular_timezones = [
    'America/New_York', 'America/Chicago', 'America/Denver',
    'America/Los_Angeles', 'Europe/London', 'Europe/Paris',
    'Asia/Tokyo', 'Asia/Shanghai', 'Australia/Sydney'
];

$timezone_regions = [
    'Popular'   => $popular_timezones,
    'Africa'    => DateTimeZone::AFRICA,
    'America'   => DateTimeZone::AMERICA,
    'Asia'      => DateTimeZone::ASIA,
    'Atlantic'  => DateTimeZone::ATLANTIC,
    'Australia' => DateTimeZone::AUSTRALIA,
    'Europe'    => DateTimeZone::EUROPE,
    'Indian'    => DateTimeZone::INDIAN,
    'Pacific'   => DateTimeZone::PACIFIC
];

// Fallback values for clean defaults
$default_values = [
    'date_format'               => 'Y-m-d',
    'time_format'               => 'H:i',
    'default_timezone'          => 'UTC',
    'default_user_role'         => 'Employee',
    'allowed_files'             => 'jpg,png,pdf,docx,xlsx,zip',
    'emp_id_prefix'             => 'EMP',    
    'max_file_size'             => '10', // MB
    'currency_number_format'    => 'US', // one of array keys above
    'currency_decimal_places'   => '2', // 0–4 typically
    'currency_symbol_position'  => 'before', // before | after
];

// Final settings array (user settings override defaults)
$settings = array_merge($default_values, $existing_data);

// ▼ Add this map of common number formats (thousand + decimal separators)
$currency_number_formats = [
    'US' => ['thousand' => ',',  'decimal' => '.', 'label' => '1,234,567.89 (US)'],
    'EU' => ['thousand' => ' ',  'decimal' => ',', 'label' => '1 234 567,89 (EU)'],
    'DE' => ['thousand' => '.',  'decimal' => ',', 'label' => '1.234.567,89 (DE)'],
    'CH' => ['thousand' => "'",  'decimal' => '.', 'label' => "1'234'567.89 (CH)"],
];


?>


<div class="innerboxgen app-form">
    <?= form_open('', ['method' => 'post']) ?>
    <input type="hidden" name="settings_submit" value="1" />

    <div class="row g-3">
        
        <div class="col-md-4">
        <label for="notification_dropdown_limit" class="form-label small">Notification Dropdown Limit</label>
        <select id="notification_dropdown_limit" name="settings[notification_dropdown_limit]" class="form-control">
            <?php foreach ([3, 5, 10, 15, 20] as $v): ?>
              <option value="<?= $v ?>" <?= get_system_setting('notification_dropdown_limit') == $v ? 'selected' : '' ?>>
                <?= $v ?> Notifications
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
      <!-- Date Format -->
      <div class="col-md-4">
        <label for="date_format" class="form-label small">Date Format</label>
        <select name="settings[date_format]" id="date_format" class="form-control">
          <?php foreach ($date_formats as $format => $label): ?>
            <option value="<?= html_escape($format) ?>" <?= ($settings['date_format'] ?? '') === $format ? 'selected' : '' ?>>
              <?= html_escape($label) ?> – <?= date($format) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Time Format -->
      <div class="col-md-4">
        <label for="time_format" class="form-label small">Time Format</label>
        <select name="settings[time_format]" id="time_format" class="form-control">
          <?php foreach ($time_formats as $format => $label): ?>
            <option value="<?= html_escape($format) ?>" <?= ($settings['time_format'] ?? '') === $format ? 'selected' : '' ?>>
              <?= html_escape($label) ?> – <?= date($format) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Timezone -->
      <div class="col-md-4 dropdown">
        <label for="default_timezone" class="form-label small">Default Timezone</label>
        <select name="settings[default_timezone]" id="default_timezone" class="form-control">
          <?php foreach ($timezone_regions as $region => $timezones): ?>
            <?php if ($region === 'Popular'): ?>
              <optgroup label="Popular Timezones">
                <?php foreach ($timezones as $tz): ?>
                  <option value="<?= html_escape($tz) ?>" <?= ($settings['default_timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                    <?= str_replace(['_', '/'], [' ', ' / '], $tz) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php else: ?>
              <optgroup label="<?= html_escape($region) ?>">
                <?php foreach (DateTimeZone::listIdentifiers($timezones) as $tz): ?>
                  <option value="<?= html_escape($tz) ?>" <?= ($settings['default_timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                    <?= str_replace(['_', '/'], [' ', ' / '], $tz) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Default Role -->
      <div class="col-md-4">
        <label for="default_user_role" class="form-label small">Default User Role</label>
        <select name="settings[default_user_role]" id="default_user_role" class="form-control">
          <?php foreach ($user_roles as $role): ?>
            <option value="<?= html_escape($role) ?>" <?= ($settings['default_user_role'] ?? '') === $role ? 'selected' : '' ?>>
              <?= ucfirst(html_escape($role)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Default EMP ID Prefix-->
        <div class="col-md-4">
          <label for="emp_id_prefix" class="form-label small">Default EMP ID Prefix <small>(Uppercase Only)</small></label>
          <input
            type="text"
            name="settings[emp_id_prefix]"
            id="emp_id_prefix"
            maxlength="3"
            minlength="3"
            pattern="[A-Z]{1,3}"
            class="form-control"
            style="text-transform: uppercase"
            value="<?= html_escape($settings['emp_id_prefix'] ?? 'EMP') ?>"
            placeholder="A-Z e.g; RCM"
            autocomplete="off"
          >
          <div class="invalid-feedback">Use 1–3 uppercase letters (A–Z).</div>
        </div>
      
      <!-- Allowed File Types -->
      <div class="col-md-4">
        <label for="allowed_files" class="form-label small">Allowed File Types</label>
        <input type="text" name="settings[allowed_files]" id="allowed_files"
               class="form-control"
               value="<?= html_escape($settings['allowed_files'] ?? 'jpg,png,pdf,docx') ?>">
      </div>

      <!-- Max File Size -->
      <div class="col-md-4">
        <label for="max_file_size" class="form-label small">Max Upload Size (MB)</label>
        <input type="number" name="settings[max_file_size]" id="max_file_size"
               class="form-control"
               value="<?= html_escape($settings['max_file_size'] ?? '10') ?>"
               min="1" max="50">
      </div>

<?php
// Expanded country/currency list
$country_currency_list = [
    'United States'          => ['code' => 'USD', 'symbol' => '$'],
    'United Kingdom'         => ['code' => 'GBP', 'symbol' => '£'],
    'European Union'         => ['code' => 'EUR', 'symbol' => '€'],
    'Canada'                 => ['code' => 'CAD', 'symbol' => '$'],
    'Australia'              => ['code' => 'AUD', 'symbol' => '$'],
    'Japan'                  => ['code' => 'JPY', 'symbol' => '¥'],
    'China'                  => ['code' => 'CNY', 'symbol' => '¥'],
    'India'                  => ['code' => 'INR', 'symbol' => '₹'],
    'Pakistan'               => ['code' => 'PKR', 'symbol' => '₨'],
    'United Arab Emirates'   => ['code' => 'AED', 'symbol' => 'د.إ'],
    'Saudi Arabia'           => ['code' => 'SAR', 'symbol' => '﷼'],
    'South Africa'           => ['code' => 'ZAR', 'symbol' => 'R'],
    'Brazil'                 => ['code' => 'BRL', 'symbol' => 'R$'],
    'Mexico'                 => ['code' => 'MXN', 'symbol' => '$'],
    'Singapore'              => ['code' => 'SGD', 'symbol' => '$'],
    'Hong Kong'              => ['code' => 'HKD', 'symbol' => '$'],
    'Switzerland'            => ['code' => 'CHF', 'symbol' => 'CHF'],
    'Norway'                 => ['code' => 'NOK', 'symbol' => 'kr'],
    'Sweden'                 => ['code' => 'SEK', 'symbol' => 'kr'],
    'Denmark'                => ['code' => 'DKK', 'symbol' => 'kr'],
    'New Zealand'            => ['code' => 'NZD', 'symbol' => '$'],
    'South Korea'            => ['code' => 'KRW', 'symbol' => '₩'],
    'Turkey'                 => ['code' => 'TRY', 'symbol' => '₺'],
    'Russia'                 => ['code' => 'RUB', 'symbol' => '₽'],
    'Egypt'                  => ['code' => 'EGP', 'symbol' => '£'],
    'Nigeria'                => ['code' => 'NGN', 'symbol' => '₦'],
    'Kenya'                  => ['code' => 'KES', 'symbol' => 'KSh'],
];

// Get current currency data
$current_currency = isset($settings['base_currency']) ? 
    json_decode($settings['base_currency'], true) : null;
?>

<!-- Country Selector -->
<div class="col-md-4">
  <label for="base_country" class="form-label small">Base Country</label>
  <select id="base_country" class="form-control">
    <option value="">Select Country</option>
    <?php foreach ($country_currency_list as $country => $data): ?>
      <option value="<?= html_escape($country) ?>"
              data-code="<?= html_escape($data['code']) ?>"
              data-symbol="<?= html_escape($data['symbol']) ?>"
              <?= ($current_currency['country'] ?? '') === $country ? 'selected' : '' ?>>
        <?= $country ?> (<?= $data['code'] ?>)
      </option>
    <?php endforeach; ?>
  </select>
  
  <!-- Hidden field that will store the complete JSON -->

  <input type="hidden" name="settings[base_currency]" id="base_currency_json" 
         value="<?= html_escape($settings['base_currency'] ?? '') ?>">
         
</div>

<!-- Currency Number Format -->
<div class="col-md-4">
  <label for="currency_number_format" class="form-label small">Currency Number Format</label>
  <select name="settings[currency_number_format]" id="currency_number_format"
          class="form-control">
    <?php foreach ($currency_number_formats as $key => $fmt): ?>
      <option value="<?= html_escape($key) ?>"
        <?= ($settings['currency_number_format'] ?? '') === $key ? 'selected' : '' ?>>
        <?= html_escape($fmt['label']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <div class="form-text">Controls thousand and decimal separators.</div>
</div>

<!-- Currency Decimal Places -->
<div class="col-md-4">
  <label for="currency_decimal_places" class="form-label small">Currency Decimal Places</label>
  <select name="settings[currency_decimal_places]" id="currency_decimal_places"
          class="form-control">
    <?php foreach (['0','1','2','3','4'] as $dp): ?>
      <option value="<?= $dp ?>" <?= (string)($settings['currency_decimal_places'] ?? '2') === $dp ? 'selected' : '' ?>>
        <?= $dp ?>
      </option>
    <?php endforeach; ?>
  </select>
  <div class="form-text">Choose how many digits appear after the decimal.</div>
</div>

<!-- Currency Symbol Placement -->
<div class="col-md-4">
  <label for="currency_symbol_position" class="form-label small">Currency Sign Placement</label>
  <select name="settings[currency_symbol_position]" id="currency_symbol_position"
          class="form-control">
    <option value="before" <?= (($settings['currency_symbol_position'] ?? 'before') === 'before') ? 'selected' : '' ?>>
      Before amount (e.g., $ 1,234.56)
    </option>
    <option value="after" <?= (($settings['currency_symbol_position'] ?? 'before') === 'after') ? 'selected' : '' ?>>
      After amount (e.g., 1,234.56 $)
    </option>
  </select>
  <div class="form-text">Controls where the currency sign appears.</div>
</div>

<!-- Display Only Fields -->
<div class="col-md-4">
  <label class="form-label small">Currency Selected</label>
  <div class="input-group">
    <input type="text" id="currency_code_display" class="form-control" 
           value="<?= $current_currency['code'] ?? '' ?>" readonly>
    <input type="text" id="currency_symbol_display" class="form-control" 
           value="<?= $current_currency['symbol'] ?? '' ?>" readonly>
  </div>
</div>

<!-- Live Money Preview -->
<div class="col-md-4">
  <label class="form-label small">Preview</label>
  <input type="text" id="currency_preview" class="form-control form-control-sm" readonly>
</div>


    </div>

    <?= form_close() ?>
</div>

<!-- jQuery and Script -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  // Initialize from existing data
  function initializeFromExisting() {
    var existingJson = $('#base_currency_json').val();
    if (existingJson) {
      try {
        var data = JSON.parse(existingJson);
        if (data.country) {
          $('#base_country').val(data.country);
          $('#currency_code_display').val(data.code);
          $('#currency_symbol_display').val(data.symbol);
        }
      } catch (e) {
        console.error('Error parsing currency data:', e);
      }
    }
  }

  // Update all currency fields
  function updateCurrencyData() {
    var selected = $('#base_country').find(':selected');
    if (selected.val()) {
      var currencyData = {
        country: selected.val(),
        code: selected.data('code'),
        symbol: selected.data('symbol')
      };
      
      $('#base_currency_json').val(JSON.stringify(currencyData));
      $('#currency_code_display').val(currencyData.code);
      $('#currency_symbol_display').val(currencyData.symbol);
    } else {
      $('#base_currency_json').val('');
      $('#currency_code_display').val('');
      $('#currency_symbol_display').val('');
    }
  }

  // Set up event handlers
  $('#base_country').on('change', updateCurrencyData);
  
  // Ensure data is updated before form submission
  $('form').on('submit', function() {
    updateCurrencyData();
  });

  // Initialize on page load
  initializeFromExisting();
});
</script>
<script>
$(function () {
  $('#default_timezone').select2({
    placeholder: 'Search timezone...',
    allowClear: true,
    width: '100%',
    minimumResultsForSearch: 5,
    dropdownAutoWidth: true,
    templateResult: formatTimezone,
    templateSelection: formatTimezoneSelection,
    escapeMarkup: function (m) { return m; }
  });

  function formatTimezone(tz) {
    if (!tz.id) return tz.text;
    return '<span>' + tz.text.replace(/\//g, ' / ') + '</span>';
  }

  function formatTimezoneSelection(tz) {
    if (!tz.id) return tz.text;
    let parts = tz.text.split('/');
    return parts[parts.length - 1].replace(/_/g, ' ').trim();
  }
});


</script>

<script>
$(function () {
  // --- Map of number formats from PHP into JS ---
  var numberFormats = <?= json_encode($currency_number_formats, JSON_UNESCAPED_UNICODE) ?>;

  // --- Helpers ---
  function formatAmount(amount, thousand, decimal, decimals) {
    decimals = parseInt(decimals, 10);
    var negative = amount < 0;
    amount = Math.abs(Number(amount) || 0);

    // Build fixed decimal
    var fixed = amount.toFixed(decimals);

    var parts = fixed.split('.');
    var intPart = parts[0];
    var decPart = parts[1] || '';

    // Thousand separators
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(intPart)) {
      intPart = intPart.replace(rgx, '$1' + thousand + '$2');
    }

    var out = intPart;
    if (decimals > 0) out += decimal + decPart;
    return (negative ? '-' : '') + out;
  }

  function currentSymbol() {
    // Prefer the display input, fallback to hidden JSON
    var sym = $('#currency_symbol_display').val();
    if (!sym) {
      var existingJson = $('#base_currency_json').val();
      if (existingJson) {
        try {
          var cur = JSON.parse(existingJson);
          sym = cur && cur.symbol ? cur.symbol : '';
        } catch(e){}
      }
    }
    return sym || '';
  }

  function buildMoney(sym, amountStr) {
    var pos = ($('#currency_symbol_position').val() || 'before').toLowerCase();
    if (!sym) return amountStr;
    return (pos === 'after') ? (amountStr + ' ' + sym) : (sym + ' ' + amountStr);
  }

  // --- Main preview updater ---
  function updatePreview() {
    var fmtKey = $('#currency_number_format').val() || 'US';
    var dp     = $('#currency_decimal_places').val() || '2';
    var fmt    = numberFormats[fmtKey] || numberFormats['US'];
    var sym    = currentSymbol();

    var sample = 1234567.89; // demo amount
    var formatted = formatAmount(sample, fmt.thousand, fmt.decimal, dp);
    $('#currency_preview').val(buildMoney(sym, formatted));
  }

  // --- Event hooks ---
  $('#currency_number_format, #currency_decimal_places, #base_country, #currency_symbol_position')
    .on('change', function () {
      // If country changed, ensure the hidden JSON/display fields are synced first
      if (this.id === 'base_country') {
        // trigger the updater you already registered
        var selected = $('#base_country').find(':selected');
        if (selected.val()) {
          var currencyData = {
            country: selected.val(),
            code: selected.data('code'),
            symbol: selected.data('symbol')
          };
          $('#base_currency_json').val(JSON.stringify(currencyData));
          $('#currency_code_display').val(currencyData.code);
          $('#currency_symbol_display').val(currencyData.symbol);
        }
      }
      updatePreview();
    });

  // Initial render after the earlier initializeFromExisting() has run
  // (your earlier <script> calls initializeFromExisting on DOM ready)
  setTimeout(updatePreview, 0);
});
</script>

