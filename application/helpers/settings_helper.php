<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('get_company')) {
    function get_company(): string
    {
        static $company_name = null;

        if ($company_name !== null) {
            return $company_name;
        }

        $CI =& get_instance();
        $CI->load->model('Company_info_model');

        $company = $CI->Company_info_model->get_all_values();

        $company_name = trim($company['company_name'] ?? '');

        return $company_name !== '' ? $company_name : 'Company';
    }
}

if ( ! function_exists('get_company_field'))
{
    function get_company_field($column)
    {
        $company = get_company_data();
        return isset($company[$column]) ? $company[$column] : '';
    }
}

if ( ! function_exists('get_dark_company_logo'))
{
    function get_dark_company_logo()
    {
        $filename = get_company_field('dark_logo');
        if ( ! empty($filename) )
        {
            $url = base_url('uploads/company/' . $filename);
            echo '<img src="' . html_escape($url)
               . '" alt="Dark Company Logo" style="max-height:80px; width:auto;">';
        }
    }
}

if ( ! function_exists('get_light_company_logo'))
{
    function get_light_company_logo()
    {
        $filename = get_company_field('light_logo');
        if ( ! empty($filename) )
        {
            $url = base_url('uploads/company/' . $filename);
            echo '<img src="' . html_escape($url)
               . '" alt="Light Company Logo" style="max-height:80px; width:auto;">';
        }
    }
}

if ( ! function_exists('get_company_favicon'))
{
    function get_company_favicon()
    {
        $filename = get_company_field('favicon');
        if ( ! empty($filename) )
        {
            $url = base_url('uploads/company/' . $filename);
            echo '<link rel="icon" href="' . html_escape($url) . '" type="image/png" />';
        }
    }
}

if ( ! function_exists('get_company_name'))
{
    function get_company_name()
    {
        $val = get_company_field('company_name');
        return ! empty($val) ? $val : 'Your Company';
    }
}


if ( ! function_exists('get_business_phone'))
{
    function get_business_phone()
    {
        return get_company_field('business_phone');
    }
}

if ( ! function_exists('get_business_email'))
{
    function get_business_email()
    {
        return get_company_field('business_email');
    }
}

if ( ! function_exists('get_allowed_files'))
{
    function get_allowed_files()
    {
        return get_company_field('allowed_files');
    }
}

function get_company_info($key)
{
    $CI =& get_instance();
    $CI->load->model('Company_info_model');
    return $CI->Company_info_model->get_value($key);
}

function render_company_input($key, $label, $type = 'text', $attrs = [])
{
    $CI =& get_instance();
    $CI->load->model('Company_info_model');
    $val = $CI->Company_info_model->get_value($key);
    echo render_input("company_info[{$key}]", $label, $val, $type, $attrs);
}

if (!function_exists('render_input')) {
    function render_input($name, $label, $value = '', $type = 'text', $attrs = [])
    {
        $attr_str = '';
        foreach ($attrs as $k => $v) {
            $attr_str .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
        }

        return <<<HTML
<div class="form-group mb-3">
    <label for="{$name}" class="form-label">{$label}</label>
    <input type="{$type}" name="{$name}" id="{$name}" value="{$value}" class="form-control" {$attr_str} />
</div>
HTML;
    }
}

function render_company_yes_no($key, $label)
{
    $CI =& get_instance();
    $CI->load->model('Company_info_model');
    $val = $CI->Company_info_model->get_value($key);
    echo render_yes_no_option("company_info[{$key}]", $label, $val);
}

if (!function_exists('load_settings_sections')) {
    function load_settings_sections(&$sections)
    {
        $CI = &get_instance();

        $featuresPath = defined('FEATURES_PATH') ? FEATURES_PATH : FCPATH . 'features/';
        if (!is_dir($featuresPath)) return;

        foreach (scandir($featuresPath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $featureConfig = $featuresPath . $dir . '/' . $dir . '.php';
            if (!file_exists($featureConfig)) continue;

            $feature = [];
            include $featureConfig;

            if (isset($feature['settings_section']) && is_array($feature['settings_section'])) {
                foreach ($feature['settings_section'] as $group => $panel) {
                    if (!isset($sections[$group])) {
                        $sections[$group] = [
                            'title'    => ucfirst($group),
                            'children' => [],
                        ];
                    }

                    $existingIds = array_column($sections[$group]['children'], 'id');
                    if (!in_array($panel['id'], $existingIds)) {
                        $sections[$group]['children'][] = $panel;
                    }
                }
            }
        }
    }
}


if (!function_exists('get_setting')) {
    function get_setting(string $key, $default = null)
    {
        $CI =& get_instance();

        // Prefer model if available
        if (isset($CI->System_settings_model)) {
            $val = $CI->System_settings_model->get($key, null);
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        // Fallback to DB (module-safe)
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $row = $CI->db
            ->select('value')
            ->from('system_settings')
            ->where('`key`', $key)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? $row['value'] : $default;
    }
}



if (!function_exists('get_system_setting')) {
    function get_system_setting($key, $default = '')
    {
        $CI =& get_instance();
        $CI->load->database();

        $row = $CI->db->where('key', $key)->get('system_settings')->row_array();
        return $row ? $row['value'] : $default;
    }
}

if (!function_exists('get_all_system_settings')) {
    function get_all_system_settings($group = 'system') {
        $CI =& get_instance();
        $CI->load->model('System_settings_model');
        return $CI->System_settings_model->get_all($group);
    }
}

if (!function_exists('get_email_config')) {
    function get_email_config(): array {
        $s = get_all_system_settings('email');

        $protocol   = $s['email_protocol'] ?? 'smtp';
        $smtp_host  = $s['smtp_host']      ?? '';
        $smtp_port  = (int)($s['smtp_port'] ?? 587);
        $smtp_user  = $s['smtp_user']      ?? '';
        $smtp_pass  = $s['smtp_pass']      ?? '';
        $smtp_crypto= $s['smtp_crypto']    ?? 'tls';
        $mailtype   = 'html';
        $charset    = 'utf-8';

        $cfg = [
            'protocol'     => $protocol,
            'mailtype'     => $mailtype,
            'charset'      => $charset,
            'wordwrap'     => true,
            'newline'      => "\r\n",
            'crlf'         => "\r\n",
        ];

        if ($protocol === 'smtp') {
            $cfg['smtp_host']   = $smtp_host;
            $cfg['smtp_port']   = $smtp_port ?: 587;
            $cfg['smtp_user']   = $smtp_user;
            $cfg['smtp_pass']   = $smtp_pass;
            if (!empty($smtp_crypto)) {
                $cfg['smtp_crypto'] = $smtp_crypto;
            }
        } elseif ($protocol === 'sendmail') {
            $cfg['mailpath'] = '/usr/sbin/sendmail';
        }

        return $cfg;
    }
}

if (!function_exists('get_company_setting')) {
    function get_company_setting(string $key, $default = null)
    {
        $CI =& get_instance();

        $row = $CI->db
            ->select('value')
            ->from('company_settings')
            ->where('key', $key)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? $row['value'] : $default;
    }
}

if (!function_exists('get_company_setting_array')) {
    function get_company_setting_array(string $key): array
    {
        $raw = get_company_setting($key, '[]');

        $arr = json_decode((string)$raw, true);

        return is_array($arr)
            ? array_values(array_unique(array_filter($arr, 'strlen')))
            : [];
    }
}

if (!function_exists('company_setting_enabled')) {
    function company_setting_enabled(string $key): bool
    {
        $val = get_company_setting($key);

        if (is_bool($val)) return $val;

        return in_array(strtolower((string)$val), ['1','true','yes','on'], true);
    }
}

if (!function_exists('company_setting')) {

    function company_setting(string $key, $default = null)
    {
        static $cache = null;

        if ($cache === null) {
            $CI = &get_instance();
            $CI->load->model('admin/Company_setup_model', 'setup');
            $cache = (array) $CI->setup->get_company_settings();
        }

        if (!array_key_exists($key, $cache)) {
            return $default;
        }

        $value = $cache[$key];

        if (is_string($value)) {
            $value = strtolower(trim($value));
        }

        if ($value === '0' || $value === 0 || $value === false) {
            return false;
        }

        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        return $cache[$key];
    }
}

////////////////////// Utilities Helpers ///////////////////////////////


if (!function_exists('human_table_label')) {
    function human_table_label($table) {
        $overrides = [
            'tblmodules'   => 'Modules',
            'activity_log' => 'Activity Logs',
            'tblclients'   => 'Clients',
            'tblstaff'     => 'Staff Members',
            'tblprojects'  => 'Projects',
        ];
        
        if (isset($overrides[$table])) {
            return $overrides[$table];
        }
        
        $table = preg_replace('/^tbl/', '', $table);
        $table = preg_replace('/^ci_/', '', $table);
        $table = str_replace('_', ' ', $table);
        $table = str_replace('-', ' ', $table);
        $table = ucwords($table);
        
        return $table;
    }
}

if (!function_exists('format_csv_cell')) {
    function format_csv_cell($value) {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        if ($value === null) {
            return 'NULL';
        }
        
        return (string)$value;
    }
}

if (!function_exists('validate_csv_row')) {
    function validate_csv_row($row, $columns) {
        $errors = [];
        
        foreach ($columns as $column) {
            if (!array_key_exists($column, $row)) {
                $errors[] = "Missing column: $column";
            }
        }
        
        foreach ($row as $key => $value) {
            if (!in_array($key, $columns)) {
                $errors[] = "Extra column not in table: $key";
            }
        }
        
        return $errors;
    }
}

if (!function_exists('add_style')) {
    function add_style($path) {
        $CI =& get_instance();
        if (!isset($CI->styles)) $CI->styles = [];
        $CI->styles[] = $path;
    }
}

if (!function_exists('get_styles')) {
    function get_styles() {
        $CI =& get_instance();
        return isset($CI->styles) ? $CI->styles : [];
    }
}

if (!function_exists('add_script')) {
    function add_script($path) {
        $CI =& get_instance();
        if (!isset($CI->scripts)) $CI->scripts = [];
        $CI->scripts[] = $path;
    }
}

if (!function_exists('get_scripts')) {
    function get_scripts() {
        $CI =& get_instance();
        return isset($CI->scripts) ? $CI->scripts : [];
    }
}

if ( ! function_exists('app_currency_settings')) {
    function app_currency_settings(): array
    {
        $fmtKey     = function_exists('get_system_setting') ? (get_system_setting('currency_number_format') ?: 'US') : 'US';
        $dp         = function_exists('get_system_setting') ? (int)(get_system_setting('currency_decimal_places') ?: 2) : 2;
        $position   = function_exists('get_system_setting') ? (get_system_setting('currency_symbol_position') ?: 'before') : 'before';
        $baseCurRaw = function_exists('get_system_setting') ? (get_system_setting('base_currency') ?: '') : '';

        $dp = max(0, min(8, (int)$dp));
        $position = strtolower($position) === 'after' ? 'after' : 'before';

        $symbol = '';
        $code   = '';
        if ($baseCurRaw) {
            $decoded = json_decode($baseCurRaw, true);
            if (is_array($decoded)) {
                $symbol = isset($decoded['symbol']) ? (string)$decoded['symbol'] : '';
                $code   = isset($decoded['code'])   ? (string)$decoded['code']   : '';
            }
        }

        return [
            'currency_number_format'   => in_array($fmtKey, ['US','EU','DE','CH'], true) ? $fmtKey : 'US',
            'currency_decimal_places'  => $dp,
            'currency_symbol_position' => $position,
            'currency_symbol_space'    => true,
            'currency_symbol'          => $symbol,
            'currency_code'            => $code,
        ];
    }
}

if ( ! function_exists('c_format')) {
    function c_format($amount, array $overrides = [], ?array $settings = null): string
    {
        if ($amount === null || $amount === '') { $amount = 0; }
        $amount = (float)$amount;

        $s = $settings ?: app_currency_settings();
        foreach ($overrides as $k => $v) { $s[$k] = $v; }

        $fmtKey   = in_array(($s['currency_number_format'] ?? 'US'), ['US','EU','DE','CH'], true) ? $s['currency_number_format'] : 'US';
        $dp       = max(0, min(8, (int)($s['currency_decimal_places'] ?? 2)));
        $pos      = strtolower($s['currency_symbol_position'] ?? 'before') === 'after' ? 'after' : 'before';
        $space    = array_key_exists('currency_symbol_space', $s) ? (bool)$s['currency_symbol_space'] : true;
        $symbol   = (string)($s['currency_symbol'] ?? '');
        $code     = (string)($s['currency_code'] ?? '');
        $showCode = (bool)($s['show_code'] ?? false);
        $codePos  = strtolower($s['code_position'] ?? 'after');

        [$thousand, $decimal] = _money_separators($fmtKey);

        $neg = $amount < 0;
        $abs = abs($amount);

        $tmp = number_format($abs, $dp, '.', ',');
        $tmp = str_replace([',', '.'], ['{T}', '{D}'], $tmp);
        $tmp = str_replace(['{T}', '{D}'], [$thousand, $decimal], $tmp);

        $sp = $space ? ' ' : '';
        $money = $tmp;
        if ($symbol !== '') {
            $money = ($pos === 'after') ? ($tmp . $sp . $symbol) : ($symbol . $sp . $tmp);
        }

        if ($showCode && $code !== '') {
            if ($codePos === 'before') {
                $money = $code . $sp . $money;
            } else {
                $money = $money . $sp . $code;
            }
        }

        return $neg ? ('-' . $money) : $money;
    }
}

if ( ! function_exists('_money_separators')) {
    function _money_separators(string $fmtKey): array
    {
        switch ($fmtKey) {
            case 'EU': return [' ', ','];
            case 'DE': return ['.', ','];
            case 'CH': return ["'", '.'];
            case 'US':
            default:   return [',', '.'];
        }
    }
}

function get_base_currency_symbol() {
    $data = json_decode(get_system_setting('base_currency'), true);
    return $data['symbol'] ?? '$';
}

if (!function_exists('emp_id_prefix')) {
    function emp_id_prefix(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $CI = get_instance();

        if (function_exists('get_system_setting')) {
            $val = (string) get_system_setting('emp_id_prefix');
        } else {
            if (!isset($CI->System_settings_model)) {
                $CI->load->model('System_settings_model');
            }
            if (method_exists($CI->System_settings_model, 'get_setting')) {
                $val = (string) ($CI->System_settings_model->get_setting('emp_id_prefix') ?? '');
            } else {
                $all = $CI->System_settings_model->get_all();
                $val = (string) ($all['emp_id_prefix'] ?? '');
            }
        }

        $cached = trim($val);
        return $cached;
    }
}

if (!function_exists('emp_id_display')) {
    function emp_id_display($id, bool $escape = true): string
    {
        $raw = (string) ($id ?? '');
        if ($raw === '') {
            return '';
        }

        $prefix = (string) (function_exists('emp_id_prefix') ? emp_id_prefix() : '');
        $prefix = trim($prefix);

        if ($prefix === '') {
            $out = ltrim($raw, "- \t");
            return $escape ? html_escape($out) : $out;
        }

        $prefixRegex = '/^(' . preg_quote($prefix, '/') . ')(-?)(.*)$/i';
        if (preg_match($prefixRegex, $raw, $m)) {
            $rest = ltrim($m[3], "- \t");
            $out  = ($rest === '') ? $prefix : ($prefix . '-' . $rest);
        } else {
            $out = $prefix . '-' . ltrim($raw, "- \t");
        }

        return $escape ? html_escape($out) : $out;
    }
}

if (!function_exists('get_leave_status_badge')) {
    function get_leave_status_badge($status)
    {
        switch (strtolower($status)) {
            case 'approved': return 'success';
            case 'pending': return 'warning';
            case 'rejected': return 'danger';
            case 'hold': return 'secondary';
            default: return 'light';
        }
    }
}

if (!function_exists('resolve_emp_title')) {
    function resolve_emp_title($empTitle): string
    {
        if ($empTitle === null || $empTitle === '') {
            return '';
        }

        if (is_numeric($empTitle)) {
            static $posCache = []; 
            $id = (int) $empTitle;

            if (isset($posCache[$id])) {
                return $posCache[$id];
            }

            $CI = get_instance();
            if (method_exists($CI->db, 'table_exists') && !$CI->db->table_exists('hrm_positions')) {
                return '';
            }

            $row = $CI->db->select('title')
                          ->from('hrm_positions')
                          ->where('id', $id)
                          ->limit(1)
                          ->get()
                          ->row_array();

            $posCache[$id] = isset($row['title']) ? (string)$row['title'] : '';
            return $posCache[$id];
        }

        return (string) $empTitle;
    }
}

if (!function_exists('get_emp_department')) {
    function get_emp_department($depName): string
    {
        if ($depName === null || $depName === '') {
            return '';
        }

        if (is_numeric($depName)) {
            static $posCache = [];
            $id = (int) $depName;

            if (isset($posCache[$id])) {
                return $posCache[$id];
            }

            $CI = get_instance();
            if (method_exists($CI->db, 'table_exists') && !$CI->db->table_exists('departments')) {
                return '';
            }

            $row = $CI->db->select('name')
                          ->from('departments')
                          ->where('id', $id)
                          ->limit(1)
                          ->get()
                          ->row_array();

            $posCache[$id] = isset($row['name']) ? (string)$row['name'] : '';
            return $posCache[$id];
        }

        return (string) $depName;
    }
}



if (!function_exists('get_department_name')) {
    function get_department_name(?int $department_id): string
    {
        $department_id = (int)$department_id;
        if ($department_id <= 0) {
            return '';
        }

        $CI =& get_instance();

        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $row = $CI->db
            ->select('name')
            ->from('departments')
            ->where('id', $department_id)
            ->limit(1)
            ->get()
            ->row();

        return $row && !empty($row->name) ? $row->name : '';
    }
}

if (!function_exists('get_position_title')) {
    function get_position_title(?int $position_id): string
    {
        $position_id = (int)$position_id;
        if ($position_id <= 0) {
            return '';
        }

        $CI =& get_instance();

        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $row = $CI->db
            ->select('title')
            ->from('hrm_positions')
            ->where('id', $position_id)
            ->limit(1)
            ->get()
            ->row();

        return $row && !empty($row->title) ? $row->title : '';
    }
}

if (!function_exists('department_dropdown_options')) {
    function department_dropdown_options($selected_id = null): string
    {
        $CI =& get_instance();

        $rows = $CI->db
            ->select('id, name')
            ->from('departments')
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        if (empty($rows)) {
            return '<option value="">No departments found</option>';
        }

        $html = '<option value="">Select Department</option>';

        foreach ($rows as $row) {
            $selected = ((int)$selected_id === (int)$row['id']) ? ' selected' : '';
            $html .= sprintf(
                '<option value="%d"%s>%s</option>',
                (int)$row['id'],
                $selected,
                html_escape($row['name'])
            );
        }

        return $html;
    }
}


if (!function_exists('positions_dropdown_options')) {
    function positions_dropdown_options($selected_id = null): string
    {
        $CI =& get_instance();

        $rows = $CI->db
            ->select('id, title')
            ->from('hrm_positions')
            ->order_by('title', 'ASC')
            ->get()
            ->result_array();

        if (empty($rows)) {
            return '<option value="">No positions found</option>';
        }

        $html = '<option value="">Select Position</option>';

        foreach ($rows as $row) {
            $selected = ((int)$selected_id === (int)$row['id']) ? ' selected' : '';
            $html .= sprintf(
                '<option value="%d"%s>%s</option>',
                (int)$row['id'],
                $selected,
                html_escape($row['title'])
            );
        }

        return $html;
    }
}

if (!function_exists('roles_dropdown_options')) {
    function roles_dropdown_options($selected_id = null): string
    {
        $CI =& get_instance();

        $rows = $CI->db
            ->select('id, role_name')
            ->from('roles')
            ->order_by('role_name', 'ASC')
            ->get()
            ->result_array();

        if (empty($rows)) {
            return '<option value="">No staff roles found</option>';
        }

        $html = '<option value="">Select Staff Role</option>';

        foreach ($rows as $row) {
            $selected = ((int)$selected_id === (int)$row['id']) ? ' selected' : '';
            $html .= sprintf(
                '<option value="%d"%s>%s</option>',
                (int)$row['id'],
                $selected,
                html_escape($row['role_name'])
            );
        }

        return $html;
    }
}

if (!function_exists('get_company_offices')) {
    function get_company_offices(array $options = []): array
    {
        $CI =& get_instance();

        $opts = array_merge([
            'active' => true,
            'format' => 'array',
        ], $options);

        $CI->db->from('company_offices');

        if ($opts['active'] && $CI->db->field_exists('is_active', 'company_offices')) {
            $CI->db->where('is_active', 1);
        }

        $CI->db->order_by('office_name', 'ASC');
        $rows = $CI->db->get()->result_array();

        if ($opts['format'] !== 'dropdown') {
            return $rows;
        }

        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['office_name'];
        }

        return $out;
    }
}

if (!function_exists('get_company_shifts')) {
    function get_company_shifts(array $options = []): array
    {
        $CI =& get_instance();

        $opts = array_merge([
            'active' => true,
            'format' => 'array',
        ], $options);

        $CI->db->from('work_shifts');

        if ($opts['active'] && $CI->db->field_exists('is_active', 'work_shifts')) {
            $CI->db->where('is_active', 1);
        }

        $CI->db->order_by('name', 'ASC');
        $rows = $CI->db->get()->result_array();
        
        if ($opts['format'] !== 'dropdown') {
            return $rows;
        }
        
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['name'];
        }

        return $out;
    }
}

if (!function_exists('get_company_office_name')) {
    function get_company_office_name($office_id): string
    {
        if (empty($office_id)) {
            return '—';
        }

        $CI =& get_instance();

        $CI->db->select('office_name');
        $CI->db->from('company_offices');
        $CI->db->where('id', (int)$office_id);
        $row = $CI->db->get()->row_array();

        return $row['office_name'] ?? '—';
    }
}

if (!function_exists('top_countries_list')) {
    function top_countries_list(): array
    {
        return [
            'PK' => [
                'name'       => 'Pakistan',
                'dial_code'  => '+92',
                'phone_len'  => 10,
            ],
            'US' => [
                'name'       => 'United States',
                'dial_code'  => '+1',
                'phone_len'  => 10,
            ],
            'UK' => [
                'name'       => 'United Kingdom',
                'dial_code'  => '+44',
                'phone_len'  => 10,
            ],
            'AE' => [
                'name'       => 'United Arab Emirates',
                'dial_code'  => '+971',
                'phone_len'  => 9,
            ],
            'IN' => [
                'name'       => 'India',
                'dial_code'  => '+91',
                'phone_len'  => 10,
            ],            
        ];
    }
}

if (!function_exists('nationality_list')) {
    function nationality_list(): array
    {
        return [
            'PK' => [
                'name' => 'Pakistani',
            ],
            'US' => [
                'name' => 'American',
            ],
            'GB' => [
                'name' => 'British',
            ],
            'AE' => [
                'name' => 'Emirati',
            ],
            'IN' => [
                'name' => 'Indian',
            ],
        ];
    }
}

if (!function_exists('get_vault_types')) {
    function get_vault_types(): array
    {
        $CI =& get_instance();

        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $row = $CI->db->select('value')
            ->from('system_settings')
            ->where('`key`', 'login_vault_types')
            ->where('group_key', 'other')
            ->limit(1)
            ->get()
            ->row_array();

        $types = [];

        if (!empty($row['value'])) {
            $decoded = json_decode($row['value'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach ($decoded as $k => $v) {
                    if (is_string($v)) {
                        $types[] = trim($v);
                    } elseif (is_string($k)) {
                        $types[] = trim($k);
                    }
                }

                $types = array_values(array_unique(array_filter($types)));
            }
        }

        if (empty($types)) {
            $types = [
                "website",
                "web_portal",
                "social",
                "email",
                "vpn",
                "wifi",
                "server",
                "other"
            ];
        }

        return $types;
    }
}

if (!function_exists('_startsWith')) {
    function _startsWith($haystack, $needle)
    {
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}

if (!function_exists('startsWith')) {
    function startsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}



if (!function_exists('app_currencies')) {
    function app_currencies(): array
    {
        return [
            'USD' => [
                'name'     => 'US Dollar',
                'symbol'   => '$',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'PKR' => [
                'name'     => 'Pakistani Rupee',
                'symbol'   => 'Rs',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'EUR' => [
                'name'     => 'Euro',
                'symbol'   => '€',
                'decimals' => 2,
                'thousand' => '.',
                'decimal'  => ',',
            ],
            'GBP' => [
                'name'     => 'British Pound',
                'symbol'   => '£',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'JPY' => [
                'name'     => 'Japanese Yen',
                'symbol'   => '¥',
                'decimals' => 0,
                'thousand' => ',',
                'decimal'  => '',
            ],
            'CAD' => [
                'name'     => 'Canadian Dollar',
                'symbol'   => 'C$',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'AUD' => [
                'name'     => 'Australian Dollar',
                'symbol'   => 'A$',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'INR' => [
                'name'     => 'Indian Rupee',
                'symbol'   => '₹',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'CNY' => [
                'name'     => 'Chinese Yuan',
                'symbol'   => '¥',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
        ];
    }
}

if (!function_exists('app_currency_dropdown')) {
    function app_currency_dropdown(string $selected = 'USD'): array
    {
        $list = [];

        foreach (app_currencies() as $code => $c) {
            $list[$code] = sprintf(
                '%s (%s)',
                $c['name'],
                $c['symbol']
            );
        }

        return $list;
    }
}