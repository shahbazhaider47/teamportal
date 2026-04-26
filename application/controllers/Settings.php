<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Settings_model');
        $this->load->model('System_settings_model');
        $this->load->model('User_permissions_model', 'userperms');
        $this->load->model('User_model');
        $this->load->model('Activity_log_model');
        
    }

    protected function log_activity(string $action)
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id') ?: 0,
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function loadDefaults(): array
    {
        $this->config->load('default_user_permissions', TRUE, TRUE);
        $cfg = $this->config->item('default_user_permissions');
    
        if (is_array($cfg)) {
            return [
                'default_user_grants' => isset($cfg['default_user_grants']) && is_array($cfg['default_user_grants']) ? $cfg['default_user_grants'] : [],
                'default_user_denies' => isset($cfg['default_user_denies']) && is_array($cfg['default_user_denies']) ? $cfg['default_user_denies'] : [],
            ];
        }
    
        $path = APPPATH.'config/default_user_permissions.php';
        if (is_file($path)) {
            $config = include $path;
            if (is_array($config)) {
                return [
                    'default_user_grants' => $config['default_user_grants'] ?? [],
                    'default_user_denies' => $config['default_user_denies'] ?? [],
                ];
            }
        }
    
        return ['default_user_grants'=>[], 'default_user_denies'=>[]];
    }


    private function normalizeLinesToList(?string $text): array
    {
        $lines = preg_split('/\R/u', (string)$text);
        $out = [];
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l !== '') $out[] = $l;
        }
        return array_values(array_unique($out, SORT_STRING));
    }

    private function filterPerms(array $list): array
    {
        $out = [];
        foreach ($list as $perm) {
            $p = strtolower(preg_replace('/\s*:\s*/', ':', trim((string)$perm)));
            if ($p === '') continue;
            // Allow "module:action" or legacy "can:..." — loosen if you prefer strict
            if (preg_match('/^[a-z0-9_\-\.]+:[a-z0-9_\-\.]+$/', $p) || strpos($p, 'can:') === 0) {
                $out[] = $p;
            } else {
                $out[] = $p; // keep non-empty lines to avoid surprising admins
            }
        }
        return array_values(array_unique($out, SORT_STRING));
    }

private function writeDefaultsFile(array $grants, array $denies): void
{
    $target = APPPATH.'config/default_user_permissions.php';
    $backup = $target.'.bak-'.date('YmdHis');

    $export = function(array $arr): string {
        $items = array_map(function($v){ return '"'.addslashes($v).'"'; }, $arr);
        return '['.implode(',', $items).']';
    };

    $php = <<<PHP
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return [
    'default_user_grants' => {$export($grants)},
    'default_user_denies' => {$export($denies)},
];

PHP;

    // Ensure path is writable (either file or directory)
    if (!is_dir(APPPATH.'config') || (!is_writable($target) && !is_writable(APPPATH.'config'))) {
        throw new RuntimeException('Config path not writable: '.APPPATH.'config');
    }

    if (is_file($target)) { @copy($target, $backup); }

    $tmp = $target.'.tmp';
    $bytes = @file_put_contents($tmp, $php, LOCK_EX);
    if ($bytes === false) {
        throw new RuntimeException('Unable to write temp file: '.$tmp);
    }
    if (!@rename($tmp, $target)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to move temp file into place.');
    }
    @chmod($target, 0644);
}

    public function index()
    {
        if (! $this->session->userdata('is_logged_in')) {
            show_error('Forbidden', 403);
            return;
        }
    
        if (! staff_can('viewsystem','general')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        // 1) Define sections (unchanged)
        $sections = [
            'general' => [
                'title'    => 'General Settings',
                'children' => [
                    ['id'=>'system','name'=>'System Settings','icon'=>'fa-solid fa-sliders-h','view'=>'settings/includes/system'],
                    ['id'=>'default_permissions','name'=>'Default Permissions','icon'=>'fa-solid fa-key','view'=>'settings/includes/default_permissions'],                    
                    ['id'=>'permissions_single_user','name'=>'Permissions - Single User','icon'=>'fa-solid fa-user-lock','view'=>'settings/includes/permissions_single_user'],
                    ['id'=>'user_permissions','name'=>'Permissions - Multiple Users','icon'=>'fa-solid fa-users-gear','view'=>'settings/includes/permissions_multiple_users'],
                    ['id'=>'calendar','name'=>'Calendar','icon'=>'fa-solid fa-calendar','view'=>'settings/includes/calendar'],
                    ['id'=>'support','name'=>'Support','icon'=>'fa-solid fa-headphones fa-fw','view'=>'settings/includes/support'],
                ],
            ],
            'advanced' => [
                'title'    => 'Advance Settings',
                'children' => [
                    ['id'=>'email','name'=>'Email Settings','icon'=>'fa-solid fa-envelope','view'=>'settings/includes/email'],
                    ['id'=>'cron','name'=>'Cron Job','icon'=>'fa-solid fa-sync','view'=>'settings/includes/cron'],
                    ['id'=>'pusher','name'=>'Pusher','icon'=>'fa-solid fa-bell','view'=>'settings/includes/pusher'],
                    ['id'=>'recaptcha','name'=>'Google reCAPTCHA','icon'=>'fa-solid fa-brands fa-google fa-fw','view'=>'settings/includes/google_recaptcha'],
                    ['id'=>'ai','name'=>'AI Assistant','icon'=>'fa-solid fa-robot','view'=>'settings/includes/ai'],
                ],
            ],
        ];
    
        // 2) Load module settings
        $modulesPath    = FCPATH . 'modules/';
        $moduleSettings = [];
        
        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $moduleFolder) {
                if ($moduleFolder === '.' || $moduleFolder === '..') { continue; }
        
                $moduleDir = $modulesPath . $moduleFolder . '/';
                if (!is_dir($moduleDir)) { continue; }
        
                // Only show modules that expose a settings view
                $settingsView = $moduleDir . 'views/settings.php';
                if (!file_exists($settingsView)) { continue; }
        
                // Reset defaults per module
                $moduleName = ucfirst($moduleFolder);
                $icon       = 'fa-solid fa-box';
        
                // 1) Prefer explicit config/module_config.php
                $configPath = $moduleDir . 'config/module_config.php';
                if (file_exists($configPath)) {
                    $config = [];
                    include $configPath;
                    if (!empty($config['settings']['icon'])) { $icon = $config['settings']['icon']; }
                    if (!empty($config['settings']['name'])) { $moduleName = $config['settings']['name']; }
                    unset($config);
                }
        
                // 2) Fallback: header metadata in modules/<module>/<module>.php (or Ucfirst.php)
                if ($icon === 'fa-solid fa-box' || $moduleName === ucfirst($moduleFolder)) {
                    $init = $moduleDir . $moduleFolder . '.php';
                    if (!file_exists($init)) {
                        $init = $moduleDir . ucfirst($moduleFolder) . '.php';
                    }
        
                    if (file_exists($init) && function_exists('parse_module_metadata')) {
                        $meta = parse_module_metadata($init);
                        if ($icon === 'fa-solid fa-box' && !empty($meta['Settings Icon'])) {
                            $icon = $meta['Settings Icon'];
                        }
                        if ($moduleName === ucfirst($moduleFolder) && !empty($meta['Settings Name'])) {
                            $moduleName = $meta['Settings Name'];
                        }
                    }
                }
        
                $moduleSettings[] = [
                    'id'   => $moduleFolder,
                    'name' => $moduleName,
                    'icon' => $icon,
                    'view' => "../../modules/{$moduleFolder}/views/settings",
                ];
            }
        }

        if (!empty($moduleSettings)) {
            $sections['modules'] = ['title'=>'Modules & Plugins','children'=>$moduleSettings];
        }
    
        // 3) Resolve group
        $groupId = $this->input->get('group', TRUE) ?: 'system';
    
        // 4) Find group metadata
        $foundGroup = null;
        foreach ($sections as $section) {
            foreach ($section['children'] as $child) {
                if ($child['id'] === $groupId) { $foundGroup = $child; break 2; }
            }
        }
        if (empty($foundGroup)) { show_404("Settings group '{$groupId}' not found"); return; }
    
        $group = ['id'=>$foundGroup['id'], 'name'=>$foundGroup['name'], 'view'=>$foundGroup['view']];
    
        // 5) POST handlers (normalized and split by group)
        if ($this->input->method() === 'post') {
            
            // Normalize posted settings (may be empty when all boxes are unchecked)
            $posted = $this->input->post('settings', TRUE) ?: [];
    
            // B) Calendar normalization (kept compatible)
            if ($groupId === 'calendar') {
                if (isset($posted['draggable_events']) && is_array($posted['draggable_events'])) {
                    $posted['draggable_events'] = json_encode(array_values($posted['draggable_events']));
                } else {
                    $posted['draggable_events'] = json_encode([]);
                }
            }
    
                        // D) User-based permissions (SINGLE + MULTI)
            // ================================
            // SINGLE USER PERMISSIONS
            // ================================
            if ($groupId === 'permissions_single_user') {
            
                $user_id = (int)$this->input->post('user_id', true);
            
                if ($user_id <= 0) {
                    set_alert('danger', 'Please select a user.');
                    redirect('settings?group=permissions_single_user');
                    return;
                }
            
                $grants = $this->input->post('settings[grants]', true) ?? [];
                $denies = $this->input->post('settings[denies]', true) ?? [];
            
                $grants = array_values(array_unique(array_filter(array_map('trim', (array)$grants))));
                $denies = array_values(array_unique(array_filter(array_map('trim', (array)$denies))));
            
                $this->userperms->save($user_id, $grants, $denies);
            
                // refresh self
                if ((int)$this->session->userdata('user_id') === $user_id
                    && function_exists('refresh_current_user_permissions_cache')) {
                    refresh_current_user_permissions_cache();
                }
            
                set_alert('success', 'Permissions saved for selected user.');
                $this->log_activity("Updated permissions (single) user_id={$user_id}");
            
                redirect("settings?group=permissions_single_user&uid={$user_id}");
                return;
            }
            
            // ================================
            // MULTIPLE USER PERMISSIONS
            // ================================
            if ($groupId === 'user_permissions') {
            
                $user_ids = $this->input->post('user_ids', true) ?? [];
                $user_ids = array_values(array_unique(array_map('intval', (array)$user_ids)));
            
                if (empty($user_ids)) {
                    set_alert('danger', 'Please select at least one user.');
                    redirect('settings?group=user_permissions');
                    return;
                }
            
                $grants = $this->input->post('settings[grants]', true) ?? [];
                $denies = $this->input->post('settings[denies]', true) ?? [];
            
                $grants = array_values(array_unique(array_filter(array_map('trim', (array)$grants))));
                $denies = array_values(array_unique(array_filter(array_map('trim', (array)$denies))));
            
                foreach ($user_ids as $uid) {
                    if ($uid > 0) {
                        $this->userperms->save($uid, $grants, $denies);
                    }
                }
            
                set_alert('success', 'Permissions applied to selected users.');
                $this->log_activity('Updated permissions (multiple users)');
            
                redirect('settings?group=user_permissions');
                return;
            }
            
            // C) Default user permissions (writes application/config/default_user_permissions.php)
            if ($groupId === 'default_permissions') {
                // Read textareas (one permission per line)
                $grants_raw = $this->input->post('default_user_grants', true);
                $denies_raw = $this->input->post('default_user_denies', true);
            
                $grants = $this->normalizeLinesToList($grants_raw);
                $denies = $this->normalizeLinesToList($denies_raw);
            
                // Light normalization/validation
                $grants = $this->filterPerms($grants);
                $denies = $this->filterPerms($denies);
            
                try {
                    $this->writeDefaultsFile($grants, $denies);
            
                    // Reload config so subsequent requests see new values immediately
                    $this->config->load('default_user_permissions', TRUE, TRUE);
            
                    // If you cache per-user permissions, clear current user cache
                    if (function_exists('refresh_current_user_permissions_cache')) {
                        refresh_current_user_permissions_cache();
                    }
            
                    set_alert('success', 'Default permissions saved.');
                    $this->log_activity('Updated default permissions config');
                } catch (\Throwable $e) {
                    log_message('error', 'save_default_permissions failed: '.$e->getMessage());
                    set_alert('danger', 'Failed to save defaults: '.$e->getMessage());
                }
            
                redirect('settings?group=default_permissions'); return;
            }
    
            // E) Email settings
            if ($groupId === 'email') {
                $existingEmail = $this->System_settings_model->get_all('email');
                $payload = [
                    'email_protocol' => $posted['email_protocol'] ?? 'smtp',
                    'smtp_host'      => $posted['smtp_host']      ?? '',
                    'smtp_port'      => isset($posted['smtp_port']) ? (int)$posted['smtp_port'] : 587,
                    'smtp_crypto'    => $posted['smtp_crypto']    ?? '',
                    'smtp_user'      => $posted['smtp_user']      ?? '',
                    'from_email'     => $posted['from_email']     ?? '',
                    'from_name'      => $posted['from_name']      ?? '',
                ];
                if (!empty($posted['smtp_pass'])) {
                    $payload['smtp_pass'] = $posted['smtp_pass'];
                } elseif (isset($existingEmail['smtp_pass'])) {
                    $payload['smtp_pass'] = $existingEmail['smtp_pass'];
                }
    
                if ($this->System_settings_model->upsert($payload, 'email')) {
                    set_alert('success', 'Email settings saved successfully.');
                    $this->log_activity("Updated email settings");
                } else {
                    set_alert('danger', 'Failed to save email settings.');
                }
                redirect("settings?group=email"); return;
            }

            // Cron settings (handled in this controller; no nested form)
            if ($groupId === 'cron') {
                // auth already checked above
            
                // Load existing
                $S = $this->System_settings_model->get_all('cron');
            
                // Was "Regenerate" clicked?
                $regen = (bool)$this->input->post('regenerate_token');
                $token = $S['cron_auth_token'] ?? '';
                if ($regen || empty($token)) {
                    $token = bin2hex(random_bytes(24));
                }
            
                // Collect fields
                $enableHttp = (int)($this->input->post('cron_enable_http') ?? 0);
                $lockTtl    = (int)($this->input->post('cron_lock_ttl') ?? 600);
                $retention  = (int)($this->input->post('cron_retention_days') ?? 90);
            
                // Persist
                $ok = $this->System_settings_model->upsert([
                    'cron_auth_token'     => $token,
                    'cron_enable_http'    => $enableHttp,
                    'cron_lock_ttl'       => $lockTtl,
                    'cron_retention_days' => $retention,
                ], 'cron');
            
                if ($ok) {
                    set_alert('success', 'Cron settings saved.');
                    $this->log_activity('Updated cron settings');
                } else {
                    set_alert('danger', 'Failed to save cron settings.');
                }
            
                redirect('settings?group=cron'); return;
            }

            // ── AI Settings ──────────────────────────────────────────────────────────────
            if ($groupId === 'ai') {
                $this->load->library('Ai_chat');
            
                // Load existing to preserve the encrypted key if not re-submitted
                $existingAi = $this->System_settings_model->get_all('ai');
            
                $fields = [
                    'ai_enabled'       => isset($posted['ai_enabled']) ? '1' : '0',
                    'ai_provider'      => trim((string)($posted['ai_provider']      ?? '')),
                    'ai_model'         => trim((string)($posted['ai_model']         ?? '')),
                    'ai_max_tokens'    => (string)max(256, min(4096, (int)($posted['ai_max_tokens']   ?? 1024))),
                    'ai_temperature'   => (string)max(0.0, min(1.0, (float)($posted['ai_temperature'] ?? 0.3))),
                    'ai_system_prompt' => trim((string)($posted['ai_system_prompt'] ?? '')),
                ];
            
                // Only update API key if a real new value was submitted
                $rawKey = trim((string)($posted['ai_api_key'] ?? ''));
                if ($rawKey !== '' && $rawKey !== '••••••••••••••••') {
                    $fields['ai_api_key'] = $this->ai_chat->encryptKey($rawKey);
                } else {
                    $fields['ai_api_key'] = $existingAi['ai_api_key'] ?? '';
                }
            
                if ($this->System_settings_model->upsert($fields, 'ai')) {
                    set_alert('success', 'AI settings saved.');
                    $this->log_activity('Updated AI settings');
                } else {
                    set_alert('danger', 'Failed to save AI settings.');
                }
            
                redirect('settings?group=ai');
                return;
            }

            // F) Fallback for other groups (system/other/modules)
            if ($this->Settings_model->save_group($groupId, $posted)) {
                set_alert('success', 'Settings saved successfully.');
                $this->log_activity("Updated settings for '{$groupId}'");
            } else {
                set_alert('danger', 'Failed to save settings.');
            }
            redirect("settings?group={$groupId}"); return;
        }
            
            if ($groupId === 'email') {
                $existing = $this->System_settings_model->get_all('email');
            
            } elseif ($groupId === 'cron') {
                $existing = $this->System_settings_model->get_all('cron');
            
            } elseif ($groupId === 'ai') {
                $raw = $this->System_settings_model->get_all('ai');
                if (!empty($raw['ai_api_key'])) {
                    $raw['ai_api_key'] = '••••••••••••••••';
                }
                $existing = $raw;
            
            } elseif ($groupId === 'permissions_single_user' || $groupId === 'user_permissions') {
                $existing = [];
            
            } elseif ($groupId === 'default_permissions') {
                $existing = $this->loadDefaults();
            
            } else {
                $existing = $this->Settings_model->get_group($groupId);
            }
            
            // 7) Bind base view data (DO NOT RESET $view_data)
            $view_data['sections']      = $sections;
            $view_data['group']         = $group;
            $view_data['existing_data'] = $existing;
            
            if ($groupId === 'default_permissions') {
                $view_data['default_user_grants'] = $existing['default_user_grants'];
                $view_data['default_user_denies'] = $existing['default_user_denies'];
            }
    
        // 8) Only prepare users & overrides for the user-permissions screen
        if (in_array($groupId, ['permissions_single_user','user_permissions'], true)) {
            if (method_exists($this->User_model, 'get_all_users') && is_callable([$this->User_model, 'get_all_users'])) {
                $users = $this->User_model->get_all_users(); // expect: id, fullname, email
            } else {
                $users = $this->db
                    ->select('id, TRIM(COALESCE(fullname, CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,"")))) AS fullname, emp_id', false)
                    ->from('users')
                    ->order_by('fullname', 'ASC')
                    ->get()
                    ->result_array();
            }
            $users = array_map(function($u){
                return [
                    'id'       => (int)($u['id'] ?? 0),
                    'fullname' => trim($u['fullname'] ?? ''),
                    'emp_id'   => $u['emp_id'] ?? '',
                ];
            }, $users);
    
            $selected_user_id = (int)($this->input->get('uid', true) ?? 0); // 0 means “none selected”
    
            $user_grants = [];
            $user_denies = [];
            if ($selected_user_id > 0) {
                $ud = $this->userperms->get_by_user_id($selected_user_id);
                $user_grants = $ud['grants'] ?? [];
                $user_denies = $ud['denies'] ?? [];
            }
    
            $view_data['users']             = $users;
            $view_data['selected_user_id']  = $selected_user_id;
            $view_data['user_grants']       = $user_grants;
            $view_data['user_denies']       = $user_denies;
        }
        
        // 9) Render
        $layout_data = [
            'page_title' => "Settings - {$group['name']}",
            'subview'    => 'settings/all',
            'view_data'  => $view_data,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

// application/controllers/Settings.php

    public function test_smtp()
    {
        if (!$this->session->userdata('is_logged_in')) {
            show_error('Forbidden', 403); return;
        }
        if (! staff_can('viewsystem','general')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $to = trim($this->input->post('test_email', true));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            set_alert('danger', 'Please provide a valid recipient email.');
            redirect('settings?group=email'); return;
        }
    
        // Pull saved settings
        $emailSettings = $this->System_settings_model->get_all('email');
    
        // Prepare initial attempt from saved settings
        $saved = [
            'protocol'   => $emailSettings['email_protocol'] ?? 'smtp',
            'smtp_host'  => $emailSettings['smtp_host'] ?? '',
            'smtp_port'  => (int)($emailSettings['smtp_port'] ?? 587),
            'smtp_user'  => $emailSettings['smtp_user'] ?? '',
            'smtp_pass'  => $emailSettings['smtp_pass'] ?? '',
            'smtp_crypto'=> $emailSettings['smtp_crypto'] ?? '', // '', 'tls', 'ssl'
        ];
    
        if (empty($saved['smtp_host']) && $saved['protocol'] === 'smtp') {
            log_message('error', 'SMTP test aborted: smtp_host is empty.');
            set_alert('danger', 'SMTP test failed: SMTP host is not configured.');
            redirect('settings?group=email'); return;
        }
    
        // Build attempt matrix (try saved first, then smart fallbacks)
        $attempts = [];
    
        // Always attempt the saved combo first
        $attempts[] = $saved;
    
        // If saved is TLS/587, add SSL/465 fallback; vice versa add TLS/587
        if ($saved['protocol'] === 'smtp') {
            if (strcasecmp($saved['smtp_crypto'], 'tls') === 0 && (int)$saved['smtp_port'] === 587) {
                $alt = $saved; $alt['smtp_crypto'] = 'ssl'; $alt['smtp_port'] = 465; $attempts[] = $alt;
            } elseif (strcasecmp($saved['smtp_crypto'], 'ssl') === 0 && (int)$saved['smtp_port'] === 465) {
                $alt = $saved; $alt['smtp_crypto'] = 'tls'; $alt['smtp_port'] = 587; $attempts[] = $alt;
            } else {
                // If crypto/port are odd, still try the two common standards
                $alt1 = $saved; $alt1['smtp_crypto'] = 'tls'; $alt1['smtp_port'] = 587; $attempts[] = $alt1;
                $alt2 = $saved; $alt2['smtp_crypto'] = 'ssl'; $alt2['smtp_port'] = 465; $attempts[] = $alt2;
            }
    
            // As a last resort, try no crypto on 25 (not recommended but helps diagnose)
            $alt3 = $saved; $alt3['smtp_crypto'] = ''; $alt3['smtp_port'] = 25; $attempts[] = $alt3;
        }
    
        $from_email = $emailSettings['from_email'] ?? '';
        $from_name  = $emailSettings['from_name']  ?? 'System';
        if (empty($from_email)) {
            $host = parse_url(base_url(), PHP_URL_HOST);
            $from_email = 'no-reply@' . $host;
        }
    
        $lastError = '';
        foreach ($attempts as $idx => $a) {
            // Compose CI Email config for this attempt
            $cfg = [
                'protocol'  => $a['protocol'],
                'mailtype'  => 'html',
                'charset'   => 'utf-8',
                'wordwrap'  => true,
                'newline'   => "\r\n",
                'crlf'      => "\r\n",
            ];
    
            if ($a['protocol'] === 'smtp') {
                $cfg['smtp_host']   = $a['smtp_host'];
                $cfg['smtp_port']   = (int)$a['smtp_port'];
                $cfg['smtp_user']   = $a['smtp_user'];
                $cfg['smtp_pass']   = $a['smtp_pass'];
                if (!empty($a['smtp_crypto'])) {
                    $cfg['smtp_crypto'] = $a['smtp_crypto']; // 'tls' or 'ssl'
                }
                // Optional: relax TLS for self-signed servers (uncomment if needed)
                // $cfg['smtp_conn_options'] = [
                //   'ssl' => [
                //     'verify_peer'       => false,
                //     'verify_peer_name'  => false,
                //     'allow_self_signed' => true
                //   ]
                // ];
            } elseif ($a['protocol'] === 'sendmail') {
                $cfg['mailpath'] = '/usr/sbin/sendmail';
            }
    
            $this->load->library('email');
            $this->email->initialize($cfg);
            $this->email->set_mailtype('html');
            $this->email->set_newline("\r\n");
            $this->email->set_crlf("\r\n");
    
            $this->email->from($from_email, $from_name);
            $this->email->to($to);
            $this->email->subject('SMTP Test - ' . ($from_name ?: 'System'));
            $body  = '<p>This message confirms that your SMTP configuration is functioning correctly. If you are reading this email, your <b>SMTP Settings</b> have been successfully verified.</p>';
            $body .= '<p>Time: ' . date('M j, Y H:i:s') . '</p>';
            $this->email->message($body);
            $this->email->set_alt_message("SMTP test.\nTime: " . date('M j, Y H:i:s'));
    
            $attemptLabel = sprintf(
                'attempt=%d protocol=%s host=%s port=%s crypto=%s user=%s',
                $idx+1, $cfg['protocol'],
                $cfg['protocol']==='smtp' ? ($cfg['smtp_host'] ?? '-') : '-',
                $cfg['protocol']==='smtp' ? ($cfg['smtp_port'] ?? '-') : '-',
                $cfg['protocol']==='smtp' ? ($cfg['smtp_crypto'] ?? '(none)') : '(n/a)',
                $cfg['protocol']==='smtp' ? ($cfg['smtp_user'] ?? '(none)') : '(n/a)'
            );
    
            log_message('info', 'SMTP test ' . $attemptLabel);
    
            if ($this->email->send(false)) {
                log_message('info', 'SMTP test SUCCESS: ' . $attemptLabel);
                set_alert('success', 'SMTP test email sent successfully.');
                redirect('settings?group=email'); return;
            }
    
            $dbg = method_exists($this->email, 'print_debugger')
                ? $this->email->print_debugger(['headers'])
                : 'No debug info';
            $thisError = 'SMTP test failed: ' . $attemptLabel . ' | Debug: ' . $dbg;
            log_message('error', $thisError);
            $lastError = $thisError;
        }
    
        // If we got here, all attempts failed
        // Trim log noise but keep enough context
        log_message('error', 'SMTP test final failure. Last error: ' . substr($lastError, 0, 1200));
    
        set_alert('danger',
            'SMTP test failed. Check logs for details. ' .
            'Validate host/port/crypto and credentials. ' .
            'Common combos: TLS:587 or SSL:465 using your full mailbox as username.'
        );
        redirect('settings?group=email');
    }

    // Cron Settings and Save Method - Used 2 difirent tables in DB 
    
    public function save_cron()
    {
        if (!$this->session->userdata('is_logged_in')) { show_error('Forbidden', 403); return; }
        // Use whatever guard you already enforce for Settings writes:
        if (!staff_can('editsystem','general')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        // Load existing cron settings
        $S = $this->System_settings_model->get_all('cron');
    
        // Regenerate token if requested or missing
        $regen = (bool)$this->input->post('regenerate_token');
        $token = $S['cron_auth_token'] ?? '';
        if ($regen || empty($token)) {
            $token = bin2hex(random_bytes(24));
        }
    
        // Collect posted values
        $enableHttp = (int)($this->input->post('cron_enable_http') ?? 0);
        $lockTtl    = (int)($this->input->post('cron_lock_ttl') ?? 600);
        $retention  = (int)($this->input->post('cron_retention_days') ?? 90);
    
        // Persist in group 'cron'
        $ok = $this->System_settings_model->upsert([
            'cron_auth_token'     => $token,
            'cron_enable_http'    => $enableHttp,
            'cron_lock_ttl'       => $lockTtl,
            'cron_retention_days' => $retention,
        ], 'cron');
    
        if ($ok) {
            set_alert('success', 'Cron settings saved.');
            $this->log_activity('Updated cron settings');
        } else {
            set_alert('danger', 'Failed to save cron settings.');
        }
    
        redirect('settings?group=cron');
    }

    public function cron_rotate_token()
    {
        if (!$this->session->userdata('is_logged_in')) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok'=>false,'error'=>'Forbidden']));
            return;
        }
        if (!staff_can('editsystem','general')) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok'=>false,'error'=>'Unauthorized']));
            return;
        }
    
        $this->load->model('System_settings_model', 'sysset');
    
        // Optional: accept current form values so we persist them together
        $enableHttp = (int)($this->input->post('enable_http', true) ?? 0);
        $lockTtl    = (int)($this->input->post('lock_ttl', true) ?? 600);
        $retention  = (int)($this->input->post('retention', true) ?? 90);
    
        // Generate new token
        try {
            $newToken = bin2hex(random_bytes(24));
        } catch (\Throwable $e) {
            $newToken = bin2hex(openssl_random_pseudo_bytes(24));
        }
    
        $ok = $this->sysset->upsert([
            'cron_auth_token'     => $newToken,
            'cron_enable_http'    => $enableHttp,
            'cron_lock_ttl'       => $lockTtl,
            'cron_retention_days' => $retention,
        ], 'cron');
    
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => (bool)$ok, 'token' => $newToken]));
    }


    public function system_info()
    {
        if (! $this->session->userdata('is_logged_in')) {
            show_error('Forbidden', 403);
            return;
        }
    
        // ✅ SAME permission used by Settings index
        if (! staff_can('viewsystem', 'general')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $layout_data = [
            'page_title' => 'System Info',
            'subview'    => 'settings/system_info',
            'view_data'  => [],
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }


    public function manage_permissions()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('auth/login');
            return;
        }

        if (! staff_can('manage_permissions', 'general')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $canEdit = staff_can('manage_permissions', 'general');

        $tab = $this->input->get('tab', true);
        $tab = in_array($tab, ['single', 'multi'], true) ? $tab : 'single';

        $coreModules       = include APPPATH . 'config/core_permissions.php';
        $modulePermissions = hooks()->apply_filters('user_permissions', []);
        $modules           = array_merge($coreModules, $modulePermissions);

        $excluded_modules = ['general', 'company', 'users', 'departments', 'assets', 'subscriptions', 'reports', 'utilities', 'crm', 'finance', 'payroll'];

        foreach ($modules as $moduleKey => $actions) {
            if (in_array($moduleKey, $excluded_modules, true)) {
                unset($modules[$moduleKey]);
            }
        }

        if (method_exists($this->User_model, 'get_all_users')) {
            $users = $this->User_model->get_all_users();
        } else {
            $users = $this->db
                ->select('id, TRIM(COALESCE(fullname, CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,"")))) AS fullname, emp_id, profile_image', false)
                ->from('users')
                ->order_by('fullname', 'ASC')
                ->get()
                ->result_array();
        }

        $users = array_map(function ($u) {
            return [
                'id'            => (int)($u['id']            ?? 0),
                'fullname'      => trim($u['fullname']        ?? ''),
                'emp_id'        => $u['emp_id']               ?? '',
                'profile_image' => $u['profile_image']        ?? '',
            ];
        }, $users);

        if ($this->input->method() === 'post') {

            if (! $canEdit) {
                $html = $this->load->view('errors/html/error_403', [], true);
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: text/html; charset=UTF-8');
                echo $html;
                exit;
            }

            $postTab = $this->input->post('active_tab', true);
            $postTab = in_array($postTab, ['single', 'multi'], true) ? $postTab : 'single';

            $grants = $this->input->post('settings[grants]', true) ?? [];
            $denies = $this->input->post('settings[denies]', true) ?? [];

            $grants = array_values(array_unique(array_filter(array_map('trim', (array)$grants))));
            $denies = array_values(array_unique(array_filter(array_map('trim', (array)$denies))));

            // Strip any permission belonging to an excluded module from submitted data
            $grants = array_values(array_filter($grants, function ($p) use ($excluded_modules) {
                $module = strstr($p, ':', true);
                return ! in_array($module, $excluded_modules, true);
            }));
            $denies = array_values(array_filter($denies, function ($p) use ($excluded_modules) {
                $module = strstr($p, ':', true);
                return ! in_array($module, $excluded_modules, true);
            }));

            if ($postTab === 'single') {

                $user_id = (int)$this->input->post('user_id', true);

                if ($user_id <= 0) {
                    set_alert('danger', 'Please select a user before saving.');
                    redirect('settings/manage_permissions?tab=single');
                    return;
                }

                $this->userperms->save($user_id, $grants, $denies);

                if ((int)$this->session->userdata('user_id') === $user_id
                    && function_exists('refresh_current_user_permissions_cache')) {
                    refresh_current_user_permissions_cache();
                }

                set_alert('success', 'Permissions saved for the selected user.');
                $this->log_activity("manage_permissions: updated single user uid={$user_id}");
                redirect("settings/manage_permissions?tab=single&uid={$user_id}");
                return;
            }

            if ($postTab === 'multi') {

                $raw_ids  = $this->input->post('user_ids', true) ?? '';
                $user_ids = array_values(array_unique(
                    array_filter(array_map('intval', explode(',', (string)$raw_ids)))
                ));

                if (empty($user_ids)) {
                    set_alert('danger', 'Please select at least one user before saving.');
                    redirect('settings/manage_permissions?tab=multi');
                    return;
                }

                $saved = 0;
                foreach ($user_ids as $uid) {
                    if ($uid > 0) {
                        $this->userperms->save($uid, $grants, $denies);
                        $saved++;
                    }
                }

                set_alert('success', "Permissions applied to {$saved} user(s).");
                $this->log_activity("manage_permissions: updated multi users count={$saved}");
                redirect('settings/manage_permissions?tab=multi');
                return;
            }
        }

        $selected_user_id = (int)($this->input->get('uid', true) ?? 0);
        $user_grants      = [];
        $user_denies      = [];

        if ($tab === 'single' && $selected_user_id > 0) {
            $ud = $this->userperms->get_by_user_id($selected_user_id);

            // Strip permissions belonging to excluded modules from display
            $user_grants = array_values(array_filter($ud['grants'] ?? [], function ($p) use ($excluded_modules) {
                $module = strstr($p, ':', true);
                return ! in_array($module, $excluded_modules, true);
            }));
            $user_denies = array_values(array_filter($ud['denies'] ?? [], function ($p) use ($excluded_modules) {
                $module = strstr($p, ':', true);
                return ! in_array($module, $excluded_modules, true);
            }));
        }

        $view_data = [
            'tab'              => $tab,
            'modules'          => $modules,
            'users'            => $users,
            'selected_user_id' => $selected_user_id,
            'user_grants'      => $user_grants,
            'user_denies'      => $user_denies,
            'can_edit'         => $canEdit,
        ];

        $layout_data = [
            'page_title' => 'Staff Permissions',
            'subview'    => 'settings/staff_permissions',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

}
