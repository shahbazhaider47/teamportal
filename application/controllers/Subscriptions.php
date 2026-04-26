<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscriptions extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Subscriptions_model', 'subs');

        $this->load->library(['form_validation', 'session', 'encryption']);
        $this->load->helper(['url', 'form', 'file']);

        // Keep null unless you want to enforce allowed values
        $this->statuses_whitelist = null;
    }

    /* ───────────────────────────── Manage / Index ───────────────────────────── */

    public function index()
    {
        $this->guard('view');

        if ($this->input->is_ajax_request()) {
            return $this->list_json();
        }

        $this->load->view('layouts/master', [
          'subview' => 'subscriptions/manage',
          'view_data' => [
            'page_title'    => 'Manage Subscriptions',
            'categories'    => $this->subs->get_categories(),
            'base_currency' => $this->subs->get_base_currency(),
            'assignees'     => $this->subs->get_assignees(),         // implement in model or wire to your staff model
          ],
        ]);
    }

    public function list_json()
    {
        $this->guard('view');
        $result = $this->subs->list(); // no filters
        return $this->json_ok($result);
    }

    /* ───────────────────────────── CRUD: Subscriptions ───────────────────────────── */

    public function view($id)
    {
        $this->guard('view');
        $id  = (int) $id;
        $rec = $this->subs->find($id);
        if (!$rec) {
            return $this->json_err('Subscription not found', 404);
        }

        $data = [
            'subscription' => $rec,
            'payments'     => $this->subs->payments($id),
        ];

        return $this->json_ok($data);
    }

    public function store()
    {
        $this->guard('create');
        $this->validate_subscription();

        if ($this->form_validation->run() === false) {
            return $this->json_err($this->validation_errors_string(), 422);
        }

        $payload                = $this->payload_from_post(false);
        $payload['created_by']  = (int)$this->session->userdata('user_id');

        $id = $this->subs->create($payload);
        if (!$id) {
            return $this->json_err('Failed to create subscription');
        }

        $plain = $this->input->post('account_password_plain', true);
        if (is_string($plain) && $plain !== '') {
            $this->subs->set_account_password($id, $plain);
        }

        return $this->json_ok(['id' => $id, 'message' => 'Subscription created']);
    }

    public function update($id)
    {
        $this->guard('edit');
        $id = (int) $id;

        $this->validate_subscription(true);
        if ($this->form_validation->run() === false) {
            return $this->json_err($this->validation_errors_string(), 422);
        }

        $payload = $this->payload_from_post(true);

        $ok = $this->subs->update($id, $payload);
        if (!$ok) {
            return $this->json_err('Failed to update subscription');
        }

        $plain = $this->input->post('account_password_plain', true);
        if (is_string($plain) && $plain !== '') {
            $this->subs->set_account_password($id, $plain);
        }

        return $this->json_ok(['id' => $id, 'message' => 'Subscription updated']);
    }

    public function delete($id)
    {
        $this->guard('delete');
        $id = (int) $id;

        $ok = $this->subs->delete($id);
        if (!$ok) {
            return $this->json_err('Delete failed');
        }

        // Purge files directory
        $this->delete_directory($this->upload_dir($id));

        return $this->json_ok(['message' => 'Subscription deleted']);
    }

    /* ───────────────────────────── Security / 2FA / Backup Codes ───────────────────────────── */

    public function set_password($id)
    {
        $this->guard('edit');
        $id    = (int) $id;
        $plain = (string) $this->input->post('password', true);

        if ($plain === '') {
            return $this->json_err('Password required', 422);
        }

        $ok = $this->subs->set_account_password($id, $plain);
        if (!$ok) {
            return $this->json_err('Failed to set password');
        }
        return $this->json_ok(['message' => 'Password updated']);
    }

    public function password_plain($id)
    {
    
        $id = (int) $id;
    
        // (Optional) Simple rate limit per user to avoid scraping
        $key = 'pw_reveal_' . $this->session->userdata('user_id');
        $last = (int)$this->session->userdata($key);
        if (time() - $last < 3) { // 3s cooldown
            return $this->json_err('Please wait a moment and try again', 429);
        }
        $this->session->set_userdata($key, time());
    
        $plain = $this->subs->get_account_password_plain($id);
        if ($plain === null) {
            return $this->json_err('No password stored', 404);
        }
    
        // Minimal audit trail
        log_message('info', sprintf(
            'User %s revealed subscription password (id=%d)',
            (string) $this->session->userdata('user_id'),
            $id
        ));
    
        return $this->json_ok(['password' => $plain]);
    }

    public function update_2fa($id)
    {
        $this->guard('edit');
        $id         = (int) $id;
        $tfa_status = $this->input->post('tfa_status');
        $tfa_source = $this->input->post('tfa_source', true);

        $row = [
            'tfa_status' => (int) !empty($tfa_status) ? 1 : 0,
            'tfa_source' => $tfa_source ?: null,
        ];

        $ok = $this->subs->update($id, $row);
        if (!$ok) {
            return $this->json_err('Failed to update 2FA settings');
        }
        return $this->json_ok(['message' => '2FA updated']);
    }

    public function update_backup_codes($id)
    {
        $this->guard('edit');
        $id = (int) $id;

        $codes = $this->input->post('backup_codes');
        if (!is_string($codes) || trim($codes) === '') {
            return $this->json_err('Backup codes required', 422);
        }

        $ok = $this->subs->update($id, ['backup_codes' => trim($codes)]);
        if (!$ok) {
            return $this->json_err('Failed to save backup codes');
        }
        return $this->json_ok(['message' => 'Backup codes updated']);
    }

    /* ───────────────────────────── Payments ───────────────────────────── */

    public function payments($subscription_id)
    {
        $this->guard('view');
        $subscription_id = (int) $subscription_id;
        $rows = $this->subs->payments($subscription_id);
        return $this->json_ok(['payments' => $rows]);
    }

    public function add_payment($subscription_id)
    {
        $this->guard('edit');
        $subscription_id = (int) $subscription_id;

        $data = [
            'payment_date'   => $this->input->post('payment_date', true),
            'amount'         => $this->input->post('amount'),
            'currency'       => $this->input->post('currency', true) ?: $this->subs->get_base_currency(),
            'method'         => $this->input->post('method', true),
            'transaction_id' => $this->input->post('transaction_id', true),
            'notes'          => $this->input->post('notes', true),
            'created_by'     => (int) $this->session->userdata('user_id'),
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        // If a receipt file was chosen, it must upload successfully or we fail
        if (isset($_FILES['receipt']) && !empty($_FILES['receipt']['name'])) {
            $receipt_rel = $this->maybe_upload_file($subscription_id, 'receipt');
            if (!$receipt_rel) {
                return $this->json_err('Receipt upload failed', 422);
            }
            $data['receipt_file'] = $receipt_rel;
        }

        $id = $this->subs->add_payment($subscription_id, $data);
        if (!$id) {
            return $this->json_err('Failed to add payment');
        }

        return $this->json_ok(['payment_id' => $id, 'message' => 'Payment added']);
    }

    public function update_payment($payment_id)
    {
        $this->guard('edit');
        $payment_id = (int) $payment_id;

        $data = [
            'payment_date'   => $this->input->post('payment_date', true),
            'amount'         => $this->input->post('amount'),
            'currency'       => $this->input->post('currency', true),
            'method'         => $this->input->post('method', true),
            'transaction_id' => $this->input->post('transaction_id', true),
            'notes'          => $this->input->post('notes', true),
        ];

        $sub_id = (int) $this->input->post('subscription_id');
        if ($sub_id > 0 && isset($_FILES['receipt']) && !empty($_FILES['receipt']['name'])) {
            $receipt_rel = $this->maybe_upload_file($sub_id, 'receipt');
            if (!$receipt_rel) {
                return $this->json_err('Receipt upload failed', 422);
            }
            $data['receipt_file'] = $receipt_rel;
        }

        $ok = $this->subs->update_payment($payment_id, $data);
        if (!$ok) {
            return $this->json_err('Failed to update payment');
        }

        return $this->json_ok(['message' => 'Payment updated']);
    }

public function delete_payment($payment_id)
{
    $this->guard('delete');
    $payment_id = (int)$payment_id;

    $ok = $this->subs->delete_payment($payment_id);
    if (!$ok) {
        return $this->json_err('Failed to delete payment');
    }

    // Return fresh last_payment_date (optional convenience)
    $payment = null; // deleted, so we need the parent id another way if you prefer
    // If you want to include last_payment_date, you can pass subscription_id from JS
    // and fetch it here. For now, keep response simple and frontend reloads.

    return $this->json_ok(['message' => 'Payment deleted']);
}



    /* ───────────────────────────── Export / Reports ───────────────────────────── */

    public function export($type = 'csv')
    {
        $this->guard('export');
        $rows = $this->subs->list_for_export(); // no filters

        if ($type !== 'csv') {
            show_error('Unsupported export format', 400);
        }

        $filename = 'subscriptions_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'ID','Title','Category','Vendor','Type','Cycle','Next Renewal',
            'Amount','Currency','Auto Renew','Status','Assigned To','Payment Method ID',
            'Account Email','Account Phone','Seats','License Key','TFA Status','TFA Source','Last Payment Date'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['title'],
                $r['category_name'] ?? '',
                $r['vendor'],
                $r['subscription_type'],
                $r['payment_cycle'],
                $r['next_renewal_date'],
                $r['amount'],
                $r['currency'],
                (int) ($r['auto_renew'] ?? 0),
                $r['status'],
                $r['assigned_to'],
                $r['payment_method_id'],
                $r['account_email'] ?? '',
                $r['account_phone'] ?? '',
                $r['seats'] ?? '',
                $r['license_key'] ?? '',
                isset($r['tfa_status']) ? (int)$r['tfa_status'] : 0,
                $r['tfa_source'] ?? '',
                $r['last_payment_date'] ?? '',
            ]);
        }
        fclose($out);
        return;
    }

    public function report()
    {
        $this->guard('view');
        
        $summary = $this->subs->stats_summary();          // no filters
        $byCat   = $this->subs->spend_by_category();      // no filters
        $trend   = $this->subs->spend_over_time();        // no filters
        
        return $this->json_ok([
            'summary'           => $summary,
            'spend_by_category' => $byCat,
            'spend_over_time'   => $trend,
        ]);
    }

    // View links to /subscriptions/reports
    public function reports()
    {
        return $this->report();
    }

    /* ───────────────────────────── Validation / Filters / Helpers ───────────────────────────── */

    private function validate_subscription(bool $is_update = false): void
    {
        $rule_date = 'trim|regex_match[/^\d{4}-\d{2}-\d{2}$/]';

        $this->form_validation->set_rules('title', 'Title', 'trim|required|min_length[2]|max_length[255]');

        $this->form_validation->set_rules('vendor', 'Vendor', 'trim|max_length[255]');
        $this->form_validation->set_rules('vendor_url', 'Account URL', 'trim|valid_url');

        $this->form_validation->set_rules('category_id', 'Category', 'trim|integer');

        if (is_array($this->statuses_whitelist) && !empty($this->statuses_whitelist)) {
            $this->form_validation->set_rules('status','Status','trim|in_list[' . implode(',', $this->statuses_whitelist) . ']');
        } else {
            $this->form_validation->set_rules('status', 'Status', 'trim|max_length[50]');
        }

        $this->form_validation->set_rules('tfa_status', '2FA Status', 'trim|in_list[0,1]');
        $this->form_validation->set_rules('tfa_source', '2FA Source', 'trim|in_list[authenticator,mobile,sms,email,both,other]');
        $this->form_validation->set_rules('account_password', 'Account Password (hash)', 'trim|max_length[255]');
        $this->form_validation->set_rules('account_password_plain', 'Account Password (plain)', 'trim|max_length[255]');
        $this->form_validation->set_rules('backup_codes', 'Backup Codes', 'trim|max_length[5000]');

        $this->form_validation->set_rules('subscription_type', 'Type', 'trim|in_list[recurring,one-time,lifetime]');
        $this->form_validation->set_rules('payment_cycle', 'Payment Cycle', 'trim|in_list[monthly,quarterly,annually,custom]');
        $this->form_validation->set_rules('cycle_days', 'Cycle Days', 'trim|integer');

        $this->form_validation->set_rules('start_date', 'Start Date', $rule_date);
        $this->form_validation->set_rules('next_renewal_date', 'Next Renewal', $rule_date);
        $this->form_validation->set_rules('end_date', 'End Date', $rule_date);

        $this->form_validation->set_rules('amount', 'Amount', 'trim|numeric');
        $this->form_validation->set_rules('currency', 'Currency', 'trim|max_length[10]');
        $this->form_validation->set_rules('seats', 'Seats', 'trim|integer');
        $this->form_validation->set_rules('license_key', 'License Key', 'trim|max_length[255]');

        $this->form_validation->set_rules('reminder_days_before', 'Reminder Days', 'trim|integer');
        $this->form_validation->set_rules('grace_days', 'Grace Days', 'trim|integer');
        $this->form_validation->set_rules('auto_renew', 'Auto Renew', 'trim|in_list[0,1]');

        $this->form_validation->set_rules('payment_method_id', 'Payment Method', 'trim|integer');
        $this->form_validation->set_rules('assigned_to', 'Assigned To', 'trim|integer');

        $this->form_validation->set_rules('account_email', 'Account Email', 'trim|valid_email');
        $this->form_validation->set_rules('account_phone', 'Account Phone', 'trim|max_length[50]');

        $this->form_validation->set_rules('notes', 'Notes', 'trim');
        $this->form_validation->set_rules('meta', 'Meta', 'trim');
    }

    public function _valid_date($str)
    {
        if ($str === null || $str === '') return true;
        $d = DateTime::createFromFormat('Y-m-d', $str);
        return $d && $d->format('Y-m-d') === $str;
    }

    private function payload_from_post(bool $is_update): array
    {
        return [
            'title'                 => $this->input->post('title', true),
            'category_id'           => $this->null_or_int($this->input->post('category_id')),
            'vendor'                => $this->input->post('vendor', true),
            'vendor_url'            => $this->input->post('vendor_url', true),
            'account_email'         => $this->input->post('account_email', true),
            'account_phone'         => $this->input->post('account_phone', true),

            'tfa_status'            => $this->input->post('tfa_status') ? 1 : 0,
            'tfa_source'            => $this->input->post('tfa_source', true),

            'subscription_type'     => $this->input->post('subscription_type', true) ?: 'recurring',
            'payment_cycle'         => $this->input->post('payment_cycle', true),
            'cycle_days'            => $this->null_or_int($this->input->post('cycle_days')),

            'start_date'            => $this->empty_to_null($this->input->post('start_date', true)),
            'next_renewal_date'     => $this->empty_to_null($this->input->post('next_renewal_date', true)),
            'end_date'              => $this->empty_to_null($this->input->post('end_date', true)),

            'reminder_days_before'  => $this->null_or_int($this->input->post('reminder_days_before', true), 7),
            'grace_days'            => $this->null_or_int($this->input->post('grace_days', true), 0),
            'auto_renew'            => $this->input->post('auto_renew') ? 1 : 0,

            'amount'                => $this->null_or_float($this->input->post('amount')),
            'currency'              => $this->input->post('currency', true) ?: $this->subs->get_base_currency(),
            'seats'                 => $this->null_or_int($this->input->post('seats')),
            'license_key'           => $this->input->post('license_key', true),

            'payment_method_id'     => $this->null_or_int($this->input->post('payment_method_id')),
            'assigned_to'           => $this->null_or_int($this->input->post('assigned_to')),
            'status'                => $this->input->post('status', true),

            'notes'                 => $this->input->post('notes', true),
            'meta'                  => $this->input->post('meta', true),
            'backup_codes'          => $this->input->post('backup_codes', true),
        ];
    }

    /* ───────────────────────────── Upload helpers ───────────────────────────── */

private function maybe_upload_file(int $subscription_id, string $field)
{
    if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'])) {
        return null; // nothing selected
    }

    $dir = $this->upload_dir($subscription_id);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true)) {
            log_message('error', 'Upload dir create failed: ' . $dir);
            return null;
        }
    }

    $config = [
        'upload_path'      => $dir,
        'allowed_types'    => 'pdf|png|jpg|jpeg|gif|webp|doc|docx|xls|xlsx|csv|txt',
        'max_size'         => 10240, // 10 MB
        'encrypt_name'     => true,
        // 'detect_mime'    => true,   // (default true in CI3)
        // 'file_ext_tolower'=> true,
    ];

    // Important: initialize explicitly to avoid reusing old config
    $this->load->library('upload');
    $this->upload->initialize($config, true);

    if (!$this->upload->do_upload($field)) {
        // log the real reason for debugging (permissions, size, type, etc.)
        log_message('error', 'Receipt upload failed: ' . $this->upload->display_errors('', ''));
        return null;
    }

    $data = $this->upload->data(); // array with file_name, full_path, etc.
    return $this->relative_path($dir . $data['file_name']);
}


    private function upload_dir(int $subscription_id): string
    {
        return rtrim(FCPATH, '/\\') . '/uploads/subscriptions/' . $subscription_id . '/';
    }

    private function relative_path(string $absolute): string
    {
        $root = rtrim(FCPATH, '/\\');
        $path = str_replace(['\\', '//'], '/', $absolute);
        $rel  = ltrim(str_replace($root, '', $path), '/');
        return $rel;
    }

    private function delete_directory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /* ───────────────────────────── Security / JSON helpers ───────────────────────────── */

    private function guard(string $action): void
    {
        // Normalize the required permission
        $requiredPerm = ($action === 'view') ? 'view' : $action;
    
        // Allow if permission granted
        if (function_exists('staff_can') && staff_can($requiredPerm, 'subscriptions')) {
            return;
        }
    
        // Deny with JSON for AJAX calls
        if ($this->input->is_ajax_request()) {
            $this->json_err('Access denied', 403);
        }
    
        // Deny with your custom HTML 403 page for non-AJAX
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    private function json_ok($payload = [], int $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(['status' => 'success', 'data' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    private function json_err($message, int $code = 400, $raw = false)
    {
        $resp = $raw ? $message : ['message' => $message];
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(['status' => 'error', 'error' => $resp], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    private function validation_errors_string(): string
    {
        $errs = trim(strip_tags(validation_errors()));
        return $errs !== '' ? $errs : 'Validation error';
    }

    private function empty_to_null($v)
    {
        return ($v === '' || $v === null) ? null : $v;
    }

    private function null_or_int($v, $default = null)
    {
        if ($v === '' || $v === null) return $default;
        return (int) $v;
    }

    private function null_or_float($v, $default = 0.0)
    {
        if ($v === '' || $v === null) return $default;
        return (float) $v;
    }
}
