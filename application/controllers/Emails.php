<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emails extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Emails_model', 'emails');

        // New mailer & merge fields libraries
        $this->load->library('App_mailer', null, 'app_mailer');
        $this->load->library('Merge_fields', null, 'merge_fields');

    }

    /* =========================================================================
     * Index — list templates
     * ========================================================================= */
    public function index()
    {
        $this->guard_global_view();

        $view_data = [
            'templates_by_type' => $this->emails->get_grouped_by_type(),
            'hasPermissionEdit' => staff_can('edit', 'email_templates'),
        ];

        $layout_data = [
            'page_title' => 'Email Templates',
            'subview'    => 'emails/email_templates',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /* =========================================================================
     * Edit single template
     * ========================================================================= */
public function email_template($id = null)
{
    $this->guard_global_view();

    $id = (int)$id;
    if ($id <= 0) { redirect(site_url('emails')); return; }

    // Save
    if ($this->input->post()) {
        $this->guard_edit();

        // Accept HTML body; standard filter for the rest
        $tmp = $this->input->post(null, false);

        $save = [
            'subject'   => trim((string)($tmp['subject']   ?? '')),
            'fromname'  => trim((string)($tmp['fromname']  ?? '')),
            'fromemail' => trim((string)($tmp['fromemail'] ?? '')),
            'message'   => (string)($tmp['message']        ?? ''), // HTML
            'plaintext' => isset($tmp['plaintext']) ? 1 : 0,
            'active'    => isset($tmp['disabled']) ? 0 : 1,
        ];

        $ok = $this->emails->update_single($id, $save);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Email template updated successfully.' : 'No changes saved.');
        redirect(site_url('emails/email_template/' . $id));
        return;
    }

    // Load
    $tpl = $this->emails->get_email_template_by_id($id);
    if (!$tpl) { show_404(); return; }
    // Force fresh scan of providers before building the sidebar
    $this->merge_fields->catalog(true);
    
    [$primary, $additional] = $this->merge_field_groups_for_template($tpl);
    
    $available_merge_fields = $this->catalog_for_view($primary, $additional);

    $view_data = [
        'template'               => $tpl,
        'hasPermissionEdit'      => staff_can('edit','email_templates'),
        'title'                  => (string)$tpl->name,
        'available_merge_fields' => $available_merge_fields,
    ];

    $layout_data = [
        'page_title' => 'Edit Email Template',
        'subview'    => 'emails/template',
        'view_data'  => $view_data,
    ];

    $this->load->view('layouts/master', $layout_data);
}

    /* =========================================================================
     * Enable/Disable (single and by type)
     * ========================================================================= */
    public function enable($id)
    {
        $this->guard_edit();
        $tpl = $this->emails->get_email_template_by_id((int)$id);
        if ($tpl) { $this->emails->mark_as($tpl->slug, 1); }
        redirect(site_url('emails'));
    }

    public function disable($id)
    {
        $this->guard_edit();
        $tpl = $this->emails->get_email_template_by_id((int)$id);
        if ($tpl) { $this->emails->mark_as($tpl->slug, 0); }
        redirect(site_url('emails'));
    }

    public function enable_by_type($type)
    {
        $this->guard_edit();
        $this->emails->mark_as_by_type((string)$type, 1);
        redirect(site_url('emails'));
    }

    public function disable_by_type($type)
    {
        $this->guard_edit();
        $this->emails->mark_as_by_type((string)$type, 0);
        redirect(site_url('emails'));
    }

    /* =========================================================================
     * SMTP Test
     * ========================================================================= */
    public function sent_smtp_test_email()
    {
        if (!$this->input->post()) { show_404(); return; }
        $this->guard_settings_edit();

        $to = trim((string)$this->input->post('test_email'));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            set_alert('danger', 'Invalid test email address.');
            redirect(site_url('settings?group=email')); return;
        }

        $ok = $this->app_mailer->send([
            'to'        => $to,
            'subject'   => 'SMTP Setup Testing',
            'view'      => 'emails/system/test_smtp_html',
            'view_data' => [
                'brand' => $this->app_mailer->brand_name(),
                'time'  => date('Y-m-d H:i:s'),
            ],
        ]);

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'SMTP appears to be configured correctly. Check your inbox.' :
                  'SMTP test failed. Check application logs for details.'
        );

        redirect(site_url('settings?group=email'));
    }

    /* =========================================================================
     * Queue deletion stub (optional)
     * ========================================================================= */
    public function delete_queued_email($id)
    {
        $this->guard_settings_edit();
        // $this->email_queue->delete((int)$id);
        set_alert('success', 'Email queue item deleted.');
        redirect(site_url('settings?group=email&tab=email_queue'));
    }

    /* =========================================================================
     * Preview (raw HTML – for iframe)
     * ========================================================================= */
    public function preview($id = null)
    {
        $this->guard_global_view();

        $id  = (int)$id;
        $tpl = $this->emails->get_email_template_by_id($id);
        if (!$tpl) { show_404(); return; }

        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Preview</title></head><body>';
        echo $tpl->message; // raw HTML; your JS wraps it with an email shell already
        echo '</body></html>';
    }

    /* =========================================================================
     * AJAX: Merge fields for a template type (feeds sidebar)
     * ========================================================================= */
    public function merge_fields_json($id = null)
    {
        $this->guard_global_view();
        $this->output->set_content_type('application/json');

        $id  = (int)$id;
        $tpl = $this->emails->get_email_template_by_id($id);
        if (!$tpl) {
            $this->output->set_status_header(404)->set_output(json_encode(['ok'=>false,'error'=>'Template not found'])); return;
        }

        $this->merge_fields->catalog(true);
        [$primary, $additional] = $this->merge_field_groups_for_template($tpl);
        $available_merge_fields = $this->catalog_for_view($primary, $additional);

        $this->output->set_output(json_encode([
            'ok'    => true,
            'groups'=> $available_merge_fields,
        ]));
    }

    /* =========================================================================
     * AJAX: Render preview with merge-field replacements
     * Body accepts: user_id, ticket_id, project_id, signoff_id, company=1 (optional)
     * ========================================================================= */
/* =========================================================================
 * AJAX: Render preview with merge-field replacements
 * Body accepts: user_id, ticket_id, project_id, signoff_id, company=1 (optional)
 * ========================================================================= */
public function render_preview($id = null)
{
    $this->guard_global_view();
    $this->output->set_content_type('application/json');

    $id  = (int)$id;
    $tpl = $this->emails->get_email_template_by_id($id);
    if (!$tpl) {
        $this->output->set_status_header(404)->set_output(json_encode(['ok'=>false,'error'=>'Template not found'])); 
        return;
    }

    // Collect preview context
    $ctx = [
        'company'   => (bool)$this->input->post('company') ?: true, // default include company
        'user_id'   => (int)($this->input->post('user_id') ?: $this->session->userdata('user_id')),
        'ticket_id' => (int)$this->input->post('ticket_id'),
        'project_id'=> (int)$this->input->post('project_id'),
        'signoff_id'=> (int)$this->input->post('signoff_id'),
    ];

    // Use current template content from POST if provided, otherwise use saved template
    $subject = $this->input->post('subject') ?: (string)$tpl->subject;
    $message = $this->input->post('message') ?: (string)$tpl->message;

    try {
        // Build map & replace
        $map = $this->merge_fields->context($ctx)->map();
        
        list($processed_subject, $processed_body) = $this->merge_fields->replace($subject, $message, $map);

        $this->output->set_output(json_encode([
            'ok'      => true,
            'subject' => $processed_subject,
            'html'    => $processed_body,
            'map'     => $map, // for debugging
        ]));
        
    } catch (\Throwable $e) {
        log_message('error', 'Preview render failed: ' . $e->getMessage());
        $this->output->set_status_header(500)->set_output(json_encode([
            'ok'    => false,
            'error' => 'Preview generation failed: ' . $e->getMessage()
        ]));
    }
}

    /* =========================================================================
     * Helpers
     * ========================================================================= */

    /** Permission: global view */
    private function guard_global_view()
    {
        if (! staff_can('view_global','users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    }

    /** Permission: edit email templates */
    private function guard_edit()
    {
        if (! staff_can('edit', 'email_templates')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    }

    /** Permission: settings edit (for SMTP test / queue ops) */
    private function guard_settings_edit()
    {
        if (! staff_can('edit', 'settings')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    }

    /**
     * Decide which merge-field groups to surface based on template type/slug.
     * Returns [primary[], additional[]]
     */
    /**
     * Decide which merge-field groups to surface based on template type/slug.
     * Returns [primary[], additional[]]
     */
    private function merge_field_groups_for_template($tpl)
    {
        $type = strtolower((string)($tpl->type ?? 'other'));
        $slug = strtolower((string)($tpl->slug ?? ''));
    
        // Company fields should always be available for all templates
        $primary = ['company'];
        
        // Add user fields to primary for most templates
        $primary[] = 'user';
    
        // Additional fields based on template type
        $additional = [];
        
        switch ($type) {
            case 'ticket':
            case 'support':
                $additional = ['ticket', 'other'];
                break;
            case 'project':
                $additional = ['project', 'other'];
                break;
            case 'signoff':
                $additional = ['signoff', 'other'];
                break;
            default:
                $additional = ['ticket', 'project', 'signoff', 'other'];
                break;
        }
    
        // Special cases based on slug
        if (strpos($slug, 'ticket') !== false) {
            $additional = array_merge(['ticket'], array_diff($additional, ['ticket']));
        }
        if (strpos($slug, 'project') !== false) {
            $additional = array_merge(['project'], array_diff($additional, ['project']));
        }
    
        return [$primary, $additional];
    }

    /**
     * Filter catalog into the groups we want to show in the UI.
     */
    private function catalog_for_view(array $primary, array $additional): array
    {
        $catalog = $this->merge_fields->catalog(); // full catalog: [group => [fields...]]
        $want    = array_unique(array_merge($primary, $additional));
        $out     = [];

        foreach ($want as $group) {
            if (!isset($catalog[$group])) continue;
            $list = $catalog[$group];

            // Normalize to ['key','name'] pairs for your view
            $arr = [];
            foreach ($list as $f) {
                $key  = (string)($f['key']  ?? '');
                $name = (string)($f['name'] ?? $key);
                if ($key === '') continue;
                $arr[] = ['key' => $key, 'name' => $name];
            }

            if ($arr) {
                usort($arr, function($a,$b){ return strcmp($a['name'], $b['name']); });
                $out[$group] = $arr;
            }
        }

        ksort($out);
        return $out;
    }
}
