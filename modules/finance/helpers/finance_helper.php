<?php defined('BASEPATH') or exit('No direct script access allowed');

function get_bank_accounts_dropdown($options = [])
{
    $ci = &get_instance();
    $ci->load->database();
    $defaults = [
        'include_blank' => true,
        'blank_text' => '-- Select Bank Account --',
        'status' => 'active',
        'order_by' => 'account_name',
        'order_dir' => 'ASC'
    ];
    $options = array_merge($defaults, $options);
    $ci->db->select('id, account_name, account_number, bank_name, currency, is_primary')
           ->from('fin_bank_accounts')
           ->where('status', $options['status'])
           ->order_by($options['order_by'], $options['order_dir']);
    if (isset($options['account_type']) && $options['account_type']) {
        $ci->db->where('account_type', $options['account_type']);
    }
    if (isset($options['currency']) && $options['currency']) {
        $ci->db->where('currency', $options['currency']);
    }
    if (isset($options['is_primary']) && $options['is_primary'] !== null) {
        $ci->db->where('is_primary', $options['is_primary']);
    }
    $query = $ci->db->get();
    $accounts = $query->result_array();
    $dropdown = [];
    if ($options['include_blank']) {
        $dropdown[''] = $options['blank_text'];
    }
    foreach ($accounts as $account) {
        $label = format_bank_account_label($account);
        $dropdown[$account['id']] = $label;
    }
    return $dropdown;
}

function format_bank_account_label($account)
{
    if (is_int($account)) {
        $ci = &get_instance();
        $ci->load->database();
        $ci->db->select('*')
               ->from('fin_bank_accounts')
               ->where('id', $account)
               ->where('status', 'active');
        $query = $ci->db->get();
        if ($query->num_rows() === 0) {
            return 'Unknown Account';
        }
        $account = $query->row_array();
    }
    
    $label = $account['account_name'] . ' - ' . $account['bank_name'];
    if (!empty($account['account_number'])) {
        $last4 = substr($account['account_number'], -4);
        $label .= ' (****' . $last4 . ')';
    }
    if (!empty($account['currency'])) {
        $label .= ' [' . $account['currency'] . ']';
    }
    if (!empty($account['is_primary'])) {
        $label .= ' ★';
    }
    return $label;
}

if (!function_exists('finance_invoice_templates')) {
    function finance_invoice_templates(): array
    {
        $basePath = FCPATH . 'modules/finance/views/partials/templates/invoice/';
        if (!is_dir($basePath)) {
            return [];
        }
        $templates = [];
        foreach (glob($basePath . '*.php') as $file) {
            $key = basename($file, '.php');
            $label = ucwords(str_replace(['_', '-'], ' ', $key));
            $templates[$key] = [
                'key'   => $key,
                'label'=> $label,
                'path' => 'modules/finance/views/partials/templates/invoice/' . basename($file),
            ];
        }
        ksort($templates);
        return $templates;
    }
}



if (!function_exists('finance_coa_templates')) {
    function finance_coa_templates(): array
    {
        $basePath = FCPATH . 'modules/finance/views/partials/templates/coa/';
        if (!is_dir($basePath)) {
            return [];
        }
        $templates = [];
        foreach (glob($basePath . '*.php') as $file) {
            $key = basename($file, '.php');
            $templates[] = [
                'key'   => $key,
                'label' => ucwords(str_replace(['_', '-'], ' ', $key)),
                'path'  => 'modules/finance/views/partials/templates/coa/' . basename($file),
            ];
        }
        usort($templates, fn($a, $b) => strcmp($a['label'], $b['label']));
        return $templates;
    }
}


if (!function_exists('finance_currencies')) {
    function finance_currencies(): array
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

if (!function_exists('finance_currency_dropdown')) {
    function finance_currency_dropdown(string $selected = 'USD'): array
    {
        $list = [];

        foreach (finance_currencies() as $code => $c) {
            $list[$code] = sprintf(
                '%s (%s)',
                $c['name'],
                $c['symbol']
            );
        }

        return $list;
    }
}

if (!function_exists('invc_format')) {
    function invc_format(
        float $amount,
        ?string $currency = null,
        bool $with_symbol = true
    ): string {
        if ($currency === null) {
            $currency = get_system_setting('finance_base_currency', 'USD');
        }
        $currencies = finance_currencies();
        if (!isset($currencies[$currency])) {
            return number_format($amount, 2);
        }
        $c = $currencies[$currency];
        $formatted = number_format(
            $amount,
            $c['decimals'],
            $c['decimal'],
            $c['thousand']
        );
        return $with_symbol
            ? trim($c['symbol'] . ' ' . $formatted)
            : $formatted;
    }
}

if (!function_exists('expc_format')) {
    function expc_format(
        float $amount,
        ?string $currency = null,
        bool $with_symbol = true
    ): string {
        if ($currency === null) {
            $currency = get_system_setting('finance_expense_currency', 'USD');
        }
        $currencies = finance_currencies();
        if (!isset($currencies[$currency])) {
            return number_format($amount, 2);
        }
        $c = $currencies[$currency];
        $formatted = number_format(
            $amount,
            $c['decimals'],
            $c['decimal'],
            $c['thousand']
        );
        return $with_symbol
            ? trim($c['symbol'] . ' ' . $formatted)
            : $formatted;
    }
}

if (!function_exists('finance_payment_methods')) {
    function finance_payment_methods(): array
    {
        return [
            'cash'            => [
                'label' => 'Cash',
                'icon'  => 'ti ti-cash',
            ],
            'bank_transfer'   => [
                'label' => 'Bank Transfer',
                'icon'  => 'ti ti-building-bank',
            ],
            'credit_card'     => [
                'label' => 'Credit Card',
                'icon'  => 'ti ti-credit-card',
            ],
            'debit_card'      => [
                'label' => 'Debit Card',
                'icon'  => 'ti ti-credit-card',
            ],
            'paypal'          => [
                'label' => 'PayPal',
                'icon'  => 'ti ti-brand-paypal',
            ],
            'stripe'          => [
                'label' => 'Stripe',
                'icon'  => 'ti ti-brand-stripe',
            ],
            'square'          => [
                'label' => 'Square',
                'icon'  => 'ti ti-square',
            ],
            'check'           => [
                'label' => 'Check',
                'icon'  => 'ti ti-file-invoice',
            ],
            'wire_transfer'   => [
                'label' => 'Wire Transfer',
                'icon'  => 'ti ti-transfer-in',
            ],
            'mobile_payment'  => [
                'label' => 'Mobile Payment',
                'icon'  => 'ti ti-device-mobile',
            ],
        ];
    }
}

if (!function_exists('finance_expense_categories')) {
    function finance_expense_categories(): array
    {
        return [
            'office_supplies' => [
                'label' => 'Office Supplies',
                'icon'  => 'ti ti-clipboard',
            ],
            'travel' => [
                'label' => 'Travel',
                'icon'  => 'ti ti-plane',
            ],
            'meals' => [
                'label' => 'Meals & Entertainment',
                'icon'  => 'ti ti-bowl-spoon',
            ],
            'software' => [
                'label' => 'Software & Subscriptions',
                'icon'  => 'ti ti-code',
            ],
            'utilities' => [
                'label' => 'Utilities',
                'icon'  => 'ti ti-bolt',
            ],
            'rent' => [
                'label' => 'Rent',
                'icon'  => 'ti ti-building',
            ],
            'internet' => [
                'label' => 'Internet & Communication',
                'icon'  => 'ti ti-wifi',
            ],
            'marketing' => [
                'label' => 'Marketing & Advertising',
                'icon'  => 'ti ti-speakerphone',
            ],
            'training' => [
                'label' => 'Training & Education',
                'icon'  => 'ti ti-school',
            ],
            'maintenance' => [
                'label' => 'Repairs & Maintenance',
                'icon'  => 'ti ti-tool',
            ],
            'miscellaneous' => [
                'label' => 'Miscellaneous',
                'icon'  => 'ti ti-dots',
            ],
        ];
    }
}

if (!function_exists('finance_expense_categories_dropdown')) {
    function finance_expense_categories_dropdown(
        bool $include_blank = true,
        string $blank_label = 'Select Expense Category'
    ): array {
        $categories = finance_expense_categories();
        $dropdown   = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($categories as $key => $data) {
            $dropdown[$key] = $data['label'];
        }

        return $dropdown;
    }
}


/**
 * Get all invoice statuses
 */
if (!function_exists('get_invoice_statuses')) {
    function get_invoice_statuses(): array
    {
        return [
            'draft'     => 'Draft',
            'sent'      => 'Sent',
            'viewed'    => 'Viewed',
            'partial'   => 'Partial',
            'paid'      => 'Paid',
            'overdue'   => 'Overdue',
            'cancelled' => 'Cancelled',
        ];
    }
}

/**
 * Dropdown options for forms
 */
if (!function_exists('get_invoice_status_dropdown')) {
    function get_invoice_status_dropdown($include_all = false): array
    {
        $statuses = get_invoice_statuses();

        if ($include_all) {
            return ['' => 'All Status'] + $statuses;
        }

        return $statuses;
    }
}

/**
 * Badge class using your custom CSS
 */
if (!function_exists('get_invoice_status_class')) {
    function get_invoice_status_class(string $status): string
    {
        $map = [
            'draft'     => 'inv-status-draft',
            'sent'      => 'inv-status-sent',
            'viewed'    => 'inv-status-viewed',
            'partial'   => 'inv-status-partial',
            'paid'      => 'inv-status-paid',
            'overdue'   => 'inv-status-overdue',
            'cancelled' => 'inv-status-cancelled',
        ];

        return $map[$status] ?? 'inv-status-draft';
    }
}

/**
 * Status icon
 */
if (!function_exists('get_invoice_status_icon')) {
    function get_invoice_status_icon(string $status): string
    {
        $map = [
            'draft'     => 'file',
            'sent'      => 'send',
            'viewed'    => 'eye',
            'partial'   => 'adjustments',
            'paid'      => 'check',
            'overdue'   => 'alert-circle',
            'cancelled' => 'x',
        ];

        return $map[$status] ?? 'circle';
    }
}

/**
 * Render full badge HTML with overdue info
 */
if (!function_exists('render_invoice_status_badge')) {
    function render_invoice_status_badge(string $status, ?string $due_date = null): string
    {
        $label = get_invoice_statuses()[$status] ?? ucfirst($status);
        $class = get_invoice_status_class($status);
        $icon  = get_invoice_status_icon($status);

        $extra = '';

        if ($due_date) {
            $due_timestamp = strtotime($due_date);
            $now = time();

            // Only if the due date is in the past and invoice not paid or cancelled
            if ($due_timestamp < $now && !in_array($status, ['paid','cancelled'])) {
                $overdue_seconds = $now - $due_timestamp;
                $overdue_days = (int) ceil($overdue_seconds / 86400); // round up
                $extra = ' (by ' . $overdue_days . ' day' . ($overdue_days > 1 ? 's' : '') . ')';
            }
        }

        return '<span class="badge inv-status-badge ' . html_escape($class) . '">
                    <i class="ti ti-' . html_escape($icon) . ' me-1"></i>'
                    . html_escape($label) . html_escape($extra) .
               '</span>';
    }
}

if (!function_exists('generate_invoice_number')) {

    function generate_invoice_number(): string
    {
        $CI = &get_instance();
        $CI->load->database();
        $rows = $CI->db
            ->select('key, value')
            ->from('system_settings')
            ->where('group_key', 'finance')
            ->where_in('key', ['finance_invoice_prefix', 'finance_invoice_start_num'])
            ->get()
            ->result_array();
        $settings = array_column($rows, 'value', 'key');
        $prefix = isset($settings['finance_invoice_prefix']) && trim($settings['finance_invoice_prefix']) !== ''
            ? trim($settings['finance_invoice_prefix'])
            : 'INV-';
        $start_num = isset($settings['finance_invoice_start_num']) && (int) $settings['finance_invoice_start_num'] > 0
            ? (int) $settings['finance_invoice_start_num']
            : 1;
        $prefix_len = strlen($prefix);
        $numbers = $CI->db
            ->select('invoice_number')
            ->from('fin_invoices')
            ->where("invoice_number LIKE '" . $CI->db->escape_like_str($prefix) . "%' ESCAPE '!'")
            ->where('deleted_at IS NULL')
            ->get()
            ->result_array();
        $max_num = 0;
        foreach ($numbers as $r) {
            $inv_num = $r['invoice_number'];
            if (strncmp($inv_num, $prefix, $prefix_len) !== 0) {
                continue;
            }
            $suffix   = substr($inv_num, $prefix_len);
            $trailing = (int) preg_replace('/^.*?(\d+)$/', '$1', $suffix);

            if ($trailing > $max_num) {
                $max_num = $trailing;
            }
        }
        $next_num = max($max_num + 1, $start_num);
        return $prefix . $next_num;
    }
}