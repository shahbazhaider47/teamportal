<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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

function get_leave_types(array $filters = []): array
{
    $CI =& get_instance();

    $CI->db
        ->select('
            id,
            name,
            code,
            color,
            type,
            limit,
            unit,
            description,
            attachment_required,
            based_on,
            allowed_annually,
            allowed_monthly
        ')
        ->from('leave_types')
        ->where('deleted_at IS NULL', null, false)
        ->order_by('name', 'ASC');
        
    $rows = $CI->db->get()->result_array();

    return $rows ?: [];
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

if (!function_exists('get_leave_approver')) {
    function get_leave_approver(
        ?int $selected_id = null,
        array $exclude_roles = []
    ): string {
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
        $excludeNames = array_map('strtolower', $exclude_roles);
        $html = '';
        foreach ($rows as $row) {
            if (
                in_array((int)$row['id'], $exclude_roles, true) ||
                in_array(strtolower($row['role_name']), $excludeNames, true)
            ) {
                continue;
            }
            $selected = ($selected_id !== null && (int)$selected_id === (int)$row['id'])
                ? ' selected'
                : '';
            $html .= sprintf(
                '<option class="capital" value="%d"%s>%s</option>',
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

if (!function_exists('vault_types')) {
    function vault_types(): array
    {
        return [
            'website'             => 'Website',
            'email'               => 'Email',
            'server'              => 'Server',
            'database'            => 'Database',
            'hosting'             => 'Hosting',
            'domain'              => 'Domain',
            'ftp'                 => 'FTP',
            'ssh'                 => 'SSH',
            'cpanel'              => 'cPanel',
            'whm'                 => 'WHM',
            'plesk'               => 'Plesk',
            'cloud'               => 'Cloud',
            'aws'                 => 'AWS',
            'google_cloud'        => 'Google Cloud',
            'azure'               => 'Azure',
            'vpn'                 => 'VPN',
            'wifi'                => 'WiFi',
            'router'              => 'Router',
            'firewall'            => 'Firewall',
            'crm'                 => 'CRM',
            'erp'                 => 'ERP',
            'social_media'        => 'Social Media',
            'payment_gateway'     => 'Payment Gateway',
            'banking'             => 'Banking',
            'api'                 => 'API',
            'software_license'    => 'Software License',
            'desktop_application' => 'Desktop Application',
            'mobile_application'  => 'Mobile Application',
            'admin_panel'         => 'Admin Panel',
            'portal'              => 'Portal',
            'medical_portal'      => 'Medical Portal',
            'insurance_portal'    => 'Insurance Portal',
            'emr_ehr'             => 'EMR / EHR',
            'clearinghouse'       => 'Clearinghouse',
            'billing_system'      => 'Billing System',
            'remote_desktop'      => 'Remote Desktop',
            'other'               => 'Other',
        ];
    }
}

if (!function_exists('vault_types_dropdown')) {
    function vault_types_dropdown(
        bool $include_blank = true,
        string $blank_label = '-- Select Login Type --'
    ): array {
        $types = vault_types();
        $dropdown = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($types as $key => $label) {
            $dropdown[$key] = $label;
        }

        return $dropdown;
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


if (!function_exists('get_emp_age')) {
    function get_emp_age($emp_dob, $short = false)
    {
        if (empty($emp_dob) || $emp_dob === '0000-00-00') {
            return '—';
        }

        try {
            $dob = new DateTime($emp_dob);
            $now = new DateTime();
            if ($dob > $now) {
                return '—';
            }

            $diff = $dob->diff($now);
            if ($short) {
                return sprintf(
                    '%dy %dm %dd',
                    $diff->y,
                    $diff->m,
                    $diff->d
                );
            }
            return sprintf(
                '%d Years, %d Months, %d Days',
                $diff->y,
                $diff->m,
                $diff->d
            );
        } catch (Exception $e) {
            return '—';
        }
    }
}


if (!function_exists('get_user_total_allowances')) {

    /**
     * Calculate total allowance amount for a user
     *
     * @param int|array $user User ID or user row array
     * @return float
     */
    function get_user_total_allowances($user)
    {
        $CI =& get_instance();

        // Resolve user row
        if (is_numeric($user)) {
            $CI->load->model('User_model');
            $user = $CI->User_model->get_user_by_id((int)$user);
        }

        if (!is_array($user) || empty($user['allowances'])) {
            return 0.0;
        }

        // Decode JSON allowances
        $allowanceIds = is_array($user['allowances'])
            ? $user['allowances']
            : json_decode($user['allowances'], true);

        if (empty($allowanceIds) || !is_array($allowanceIds)) {
            return 0.0;
        }

        // Normalize IDs (numeric only)
        $allowanceIds = array_map('intval', $allowanceIds);
        $allowanceIds = array_filter($allowanceIds);

        if (empty($allowanceIds)) {
            return 0.0;
        }

        // Fetch allowance amounts
        $CI->db->select_sum('amount');
        $CI->db->from('hrm_allowances');
        $CI->db->where_in('id', $allowanceIds);
        $CI->db->where('deleted_at IS NULL', null, false);

        $row = $CI->db->get()->row_array();

        return isset($row['amount']) ? (float)$row['amount'] : 0.0;
    }
}

