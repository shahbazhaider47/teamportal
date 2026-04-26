<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Libraries & helpers
        $this->load->library(['form_validation', 'security', 'email']);
        $this->load->helper(['form', 'url', 'security']);

        // Models
        $this->load->model([
            'User_model',
            'Authentication_model',
            'Activity_log_model',
            'System_settings_model', // for email settings
        ]);

        // Security headers
        $this->output->set_header('X-Content-Type-Options: nosniff');
        $this->output->set_header('X-Frame-Options: DENY');
        $this->output->set_header('X-XSS-Protection: 1; mode=block');
    }

    /**
     * Login (GET renders form, POST authenticates)
     */
    public function login()
    {
        if ($this->session->userdata('is_logged_in')) {
            redirect('dashboard'); return;
        }

        // Load reCAPTCHA settings (group: recaptcha)
        $recaptchaSettings = $this->System_settings_model->get_all('recaptcha');
    
        $recaptcha_enabled        = ($recaptchaSettings['recaptcha_enabled']        ?? 'no') === 'yes';
        $recaptcha_on_login       = ($recaptchaSettings['recaptcha_on_login']       ?? 'no') === 'yes';
        $recaptcha_site_key       = $recaptchaSettings['recaptcha_site_key']        ?? '';
        $recaptcha_secret_key     = $recaptchaSettings['recaptcha_secret_key']      ?? '';
        $recaptcha_version        = $recaptchaSettings['recaptcha_version']         ?? 'v2_checkbox';
        $recaptcha_score_threshold= (float)($recaptchaSettings['recaptcha_score_threshold'] ?? 0.5);
    
        // Only consider enabled if all required pieces are present
        $recaptchaActiveForLogin = $recaptcha_enabled
            && $recaptcha_on_login
            && !empty($recaptcha_site_key)
            && !empty($recaptcha_secret_key);
    
        $data = [
            'title'                   => 'Login',
            'csrf_token_name'         => $this->security->get_csrf_token_name(),
            'csrf_token_value'        => $this->security->get_csrf_hash(),
    
            // Pass reCAPTCHA config to the view
            'recaptcha_enabled'       => $recaptchaActiveForLogin,
            'recaptcha_version'       => $recaptcha_version,
            'recaptcha_site_key'      => $recaptcha_site_key,
            'recaptcha_score_threshold'=> $recaptcha_score_threshold,
        ];

        if ($this->input->method() !== 'post') {
            $this->load->view('authentication/login', $data);
            return;
        }

        // Throttle obvious brute force
        $postedEmail = $this->input->post('email', TRUE);
        if ($this->Authentication_model->is_brute_force($postedEmail)) {
            $this->log_activity('Blocked brute force attempt for email: ' . $postedEmail);
            set_alert('warning', 'Too many login attempts. Please try again later.');
            redirect('authentication/login'); return;
        }

        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->log_activity('Login validation failed for email: ' . $postedEmail);
            $this->load->view('authentication/login', $data);
            return;
        }
        
        // If reCAPTCHA is active for login, verify it now
        if (!empty($recaptchaActiveForLogin)) {
            $token = (string)$this->input->post('g-recaptcha-response', TRUE);
        
            list($rcOk, $rcError) = $this->verify_recaptcha_token(
                $token,
                $recaptcha_secret_key,
                $recaptcha_version,
                $recaptcha_score_threshold,
                'login'
            );
        
            if (!$rcOk) {
                $this->log_activity('Login blocked by reCAPTCHA for email: ' . $postedEmail);
                $data['login_error']     = $rcError ?: 'reCAPTCHA check failed. Please try again.';
                $data['recaptcha_error'] = $rcError ?: 'reCAPTCHA check failed. Please try again.';
                $this->load->view('authentication/login', $data);
                return;
            }
        }
        
        $email    = strtolower(trim($postedEmail));
        $password = (string)$this->input->post('password', TRUE);
        $user     = $this->Authentication_model->authenticate($email, $password);

        if (! $user) {
            $this->Authentication_model->log_failed_attempt($email);
            $this->log_activity('Failed login attempt for email: ' . $email);
            $data['login_error'] = 'Invalid email or password.';
            $this->load->view('authentication/login', $data);
            return;
        }

        // Account active?
        if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            $this->log_activity('Login blocked for inactive user: ' . $email);
            $data['login_error'] = 'Your account is disabled. Please contact support.';
            $this->load->view('authentication/login', $data);
            return;
        }

        // Success path
        $this->session->sess_regenerate();
        $this->session->set_userdata([
            'user_id'      => $user['id'],
            'firstname'    => $user['firstname'],
            'lastname'     => $user['lastname'],
            'user_role'    => $user['user_role'],
            'emp_id'       => $user['emp_id'],
            'is_logged_in' => TRUE,
            'last_activity'=> time(),
        ]);

        $this->User_model->update_last_login($user['id']);
        $this->log_activity('User logged in: ' . $email);
        
        log_message('debug', 'SESSION ID: ' . session_id());
        log_message('debug', print_r($this->session->userdata(), true));

        $redirect_url = $this->session->userdata('redirect_url') ?: 'dashboard';
        $this->session->unset_userdata('redirect_url');
        redirect($redirect_url);
    }

    /**
     * Social login callback
     */
    public function social_login($provider)
    {
        $this->load->library('OAuth');

        try {
            $user_profile = $this->oauth->authenticate($provider);
            $user = $this->Authentication_model->handle_social_user($provider, $user_profile);

            if (! $user) {
                throw new Exception('Failed to authenticate with ' . $provider);
            }

            $this->session->set_userdata([
                'user_id'      => $user['id'],
                'email'        => $user['email'],
                'firstname'    => $user['firstname'],
                'lastname'     => $user['lastname'],
                'user_role'    => $user['user_role'],
                'is_logged_in' => TRUE,
                'last_activity'=> time(),
            ]);

            $this->log_activity('User logged in via ' . $provider . ': ' . $user['email']);
            redirect('dashboard');

        } catch (Exception $e) {
            $this->log_activity('Social login failed: ' . $e->getMessage());
            set_alert('warning', 'Failed to login with ' . $provider);
            redirect('authentication/login');
        }
    }

    /**
     * Password policy callback
     */
    public function validate_password_strength($password)
    {
        $password = (string) $password;
    
        $hasLen     = (strlen($password) >= 8);
        $hasUpper   = preg_match('/[A-Z]/', $password);
        $hasDigit   = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[\W_]/', $password); // symbols or underscore
    
        if ($hasLen && $hasUpper && $hasDigit && $hasSpecial) {
            return TRUE;
        }
    
        $this->form_validation->set_message(
            'validate_password_strength',
            'The {field} must be at least 8 characters and include an uppercase letter, a number, and a symbol.'
        );
        return FALSE;
    }

    /**
     * Forgot password (GET form / POST issue token + email)
     */
    public function forgot_password()
    {
        // --- reCAPTCHA config (group: recaptcha) ---
        $rc = $this->System_settings_model->get_all('recaptcha'); // ['recaptcha_enabled'=>..., ...]
    
        $enabledGlobal   = ($rc['recaptcha_enabled']        ?? 'no') === 'yes';
        $onForgot        = ($rc['recaptcha_on_forgot']      ?? 'no') === 'yes';
        $version         =  $rc['recaptcha_version']        ?? 'v2_checkbox';
        $siteKey         =  $rc['recaptcha_site_key']       ?? '';
        $secretKey       =  $rc['recaptcha_secret_key']     ?? '';
        $scoreThreshold  = (float)($rc['recaptcha_score_threshold'] ?? '0.5');
    
        $recaptchaActive = $enabledGlobal && $onForgot && $siteKey && $secretKey;
    
        $data = [
            'title'              => 'Forgot Password',
            'recaptcha_enabled'  => $recaptchaActive ? 1 : 0,
            'recaptcha_version'  => $version,
            'recaptcha_site_key' => $siteKey,
            'recaptcha_error'    => null,
        ];
    
        // GET: show form
        if ($this->input->method() !== 'post') {
            $this->load->view('authentication/forgot_password', $data);
            return;
        }
    
        // Validate basic fields first
        $this->form_validation->set_rules('emp_id', 'Employee ID', 'trim|required');
        $this->form_validation->set_rules('email',  'Email',       'trim|required|valid_email');
    
        if ($this->form_validation->run() === FALSE) {
            $this->load->view('authentication/forgot_password', $data);
            return;
        }
    
        // --- reCAPTCHA server-side verification (if active) ---
        if ($recaptchaActive) {
            $token = (string)$this->input->post('g-recaptcha-response', TRUE);
    
            if ($token === '') {
                $data['recaptcha_error'] = 'Please complete the security check.';
                $this->load->view('authentication/forgot_password', $data);
                return;
            }
    
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $query     = http_build_query([
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $this->input->ip_address(),
            ]);
    
            $response = @file_get_contents($verifyUrl . '?' . $query);
            $decoded  = $response ? json_decode($response, true) : null;
    
            $ok = false;
    
            if (is_array($decoded) && !empty($decoded['success'])) {
                if ($version === 'v3') {
                    $score = isset($decoded['score']) ? (float)$decoded['score'] : 0.0;
                    $ok    = $score >= $scoreThreshold;
                } else {
                    // v2 checkbox / v2 invisible – success flag is enough
                    $ok = true;
                }
            }
    
            if (!$ok) {
                $data['recaptcha_error'] = 'Security verification failed. Please try again.';
                $this->load->view('authentication/forgot_password', $data);
                return;
            }
        }
    
        // --- Existing logic (unchanged) ---
        $emp_id = trim($this->input->post('emp_id', TRUE));
        $email  = strtolower(trim($this->input->post('email', TRUE)));
    
        $user = $this->User_model->get_user_by_emp_id_and_email($emp_id, $email);
    
        if (! $user) {
            // Log and show specific error
            $this->log_activity("Password reset attempt failed - Emp ID '{$emp_id}' does not match Email '{$email}'");
            set_alert('warning', 'The provided Employee ID does not match the provided Email.');
            redirect('authentication/forgot_password');
            return;
        }
    
        // If user exists, proceed with reset token
        $token      = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
        $this->User_model->update_user_pass($user['id'], [
            'password_token'   => $token,
            'token_expires_at' => $expires_at,
        ]);
    
        $reset_link = site_url("authentication/reset_password/{$token}");
    
        try {
            $sent = $this->send_reset_email($email, $reset_link);
            if (! $sent) {
                log_message('error', "Password reset email FAILED for {$email}");
            } else {
                log_message('info',  "Password reset email sent to {$email}");
            }
        } catch (Throwable $e) {
            log_message('error', 'Password reset email exception for: ' . $email . ' | ' . $e->getMessage());
        }
    
        $this->log_activity('Password reset requested for user: ' . $email);
        set_alert('success', 'A password reset link has been sent to your email.');
        redirect('authentication/forgot_password');
    }

    /**
     * Reset password (GET form / POST perform reset)
     */
    public function reset_password($token = null)
    {
        if ($this->session->userdata('is_logged_in')) {
            redirect('dashboard'); return;
        }
    
        if (empty($token)) {
            set_alert('warning', 'Invalid password reset token.');
        }
    
        // Lookup user by token
        $user = $this->User_model->get_user_by_token($token);
        if (! $user || strtotime($user['token_expires_at']) < time()) {
            set_alert('warning', 'This link is expired now.');
        }
    
        // --- reCAPTCHA config (group: recaptcha) ---
        $rc = $this->System_settings_model->get_all('recaptcha'); // ['recaptcha_enabled'=>..., ...]
    
        $enabledGlobal   = ($rc['recaptcha_enabled']          ?? 'no') === 'yes';
        $onSetPassword   = ($rc['recaptcha_on_set_password']  ?? 'no') === 'yes';
        $version         =  $rc['recaptcha_version']          ?? 'v2_checkbox';
        $siteKey         =  $rc['recaptcha_site_key']         ?? '';
        $secretKey       =  $rc['recaptcha_secret_key']       ?? '';
        $scoreThreshold  = (float)($rc['recaptcha_score_threshold'] ?? '0.5');
    
        $recaptchaActive = $enabledGlobal && $onSetPassword && $siteKey && $secretKey;
    
        $data = [
            'title'              => 'Reset Password',
            'token'              => $token,
            'csrf_token_name'    => $this->security->get_csrf_token_name(),
            'csrf_token_value'   => $this->security->get_csrf_hash(),
    
            'recaptcha_enabled'  => $recaptchaActive ? 1 : 0,
            'recaptcha_version'  => $version,
            'recaptcha_site_key' => $siteKey,
            'recaptcha_error'    => null,
        ];
    
        // GET: show form
        if ($this->input->method() !== 'post') {
            $this->load->view('authentication/reset_password', $data);
            return;
        }
    
        // If token is invalid/expired, do not allow reset even on POST
        if (! $user || strtotime($user['token_expires_at']) < time()) {
            set_alert('warning', 'This password reset link is no longer valid.');
            $this->load->view('authentication/reset_password', $data);
            return;
        }
    
        // Validation rules for new password
        $this->form_validation->set_rules(
            'password',
            'Password',
            'trim|required|min_length[8]|regex_match[/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]'
        );
        $this->form_validation->set_message(
            'regex_match',
            'The {field} must be at least 8 characters and include an uppercase letter, a number, and a symbol.'
        );
    
        $this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required|matches[password]');
    
        if ($this->form_validation->run() === FALSE) {
            $this->load->view('authentication/reset_password', $data);
            return;
        }
    
        // --- reCAPTCHA server-side verification (if active) ---
        if ($recaptchaActive) {
            $tokenResponse = (string)$this->input->post('g-recaptcha-response', TRUE);
    
            if ($tokenResponse === '') {
                $data['recaptcha_error'] = 'Please complete the security check.';
                $this->load->view('authentication/reset_password', $data);
                return;
            }
    
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $query     = http_build_query([
                'secret'   => $secretKey,
                'response' => $tokenResponse,
                'remoteip' => $this->input->ip_address(),
            ]);
    
            $response = @file_get_contents($verifyUrl . '?' . $query);
            $decoded  = $response ? json_decode($response, true) : null;
    
            $ok = false;
    
            if (is_array($decoded) && !empty($decoded['success'])) {
                if ($version === 'v3') {
                    $score = isset($decoded['score']) ? (float)$decoded['score'] : 0.0;
                    $ok    = $score >= $scoreThreshold;
                } else {
                    // v2 checkbox / v2 invisible – success flag is enough
                    $ok = true;
                }
            }
    
            if (! $ok) {
                $data['recaptcha_error'] = 'Security verification failed. Please try again.';
                $this->load->view('authentication/reset_password', $data);
                return;
            }
        }
    
        // --- Perform password reset ---
        $this->User_model->update_user_pass($user['id'], [
            'password'         => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
            'password_token'   => null,
            'token_expires_at' => null,
        ]);
    
        $this->log_activity('Password reset successfully for user: ' . $user['email']);
        set_alert('success', 'Your password has been reset successfully. Please login with your new password.');
        redirect('authentication/login');
    }

    /**
     * Compose and send the password reset email using system email settings
     */
    protected function send_reset_email($email, $reset_link)
    {
        // Build email config from DB settings (group=email)
        $s      = $this->System_settings_model->get_all('email');
        $proto  = strtolower($s['email_protocol'] ?? 'smtp');

        $cfg = [
            'protocol' => $proto,
            'mailtype' => 'html',
            'charset'  => 'utf-8',
            'wordwrap' => TRUE,
            'newline'  => "\r\n",
            'crlf'     => "\r\n",
        ];

        if ($proto === 'smtp') {
            $cfg['smtp_host']   = $s['smtp_host'] ?? '';
            $cfg['smtp_port']   = (int)($s['smtp_port'] ?? 587);
            $cfg['smtp_user']   = $s['smtp_user'] ?? '';
            $cfg['smtp_pass']   = $s['smtp_pass'] ?? '';
            if (!empty($s['smtp_crypto'])) {
                $cfg['smtp_crypto'] = $s['smtp_crypto']; // '', tls, ssl
            }
        } elseif ($proto === 'sendmail') {
            $cfg['mailpath'] = is_executable('/usr/sbin/sendmail') ? '/usr/sbin/sendmail' :
                               (is_executable('/usr/lib/sendmail') ? '/usr/lib/sendmail' : '/usr/sbin/sendmail');
        }

        $this->email->initialize($cfg);
        $this->email->set_mailtype('html');
        $this->email->set_newline("\r\n");
        $this->email->set_crlf("\r\n");

        // From / branding
        $from_email   = $s['from_email'] ?? '';
        $from_name    = $s['from_name']  ?? (get_system_setting('company_name') ?: 'RCM Centric');
        if (empty($from_email)) {
            $host = parse_url(base_url(), PHP_URL_HOST);
            $from_email = 'no-reply@' . $host;
        }

        $company_name = get_system_setting('company_name') ?: 'RCM Centric';
        $logo_file    = get_system_setting('light_logo') ?: '';
        $logo_url     = $logo_file ? base_url('uploads/company/' . $logo_file) : '';

        // Ensure absolute link
        if (strpos($reset_link, 'http') !== 0) {
            $reset_link = site_url($reset_link);
        }

        $this->email->from($from_email, $from_name);
        $this->email->reply_to($from_email, $from_name);
        $this->email->to($email);
        $this->email->subject('Password Reset Request');

        $html = $this->load->view('emails/password_reset', [
            'reset_link'   => $reset_link,
            'company_name' => $company_name,
            'logo_url'     => $logo_url,
        ], TRUE);

        $text = "You requested a password reset for {$company_name}.\n\n"
              . "Open this link to reset your password:\n{$reset_link}\n\n"
              . "If you did not request this, ignore this email.\n"
              . "This link expires in 1 hour.\n";

        $this->email->message($html);
        $this->email->set_alt_message($text);
        $this->email->set_header('Return-Path', $from_email);
        $this->email->set_priority(3);

        if (!$this->email->send(FALSE)) {
            $dbg = method_exists($this->email, 'print_debugger') ? $this->email->print_debugger(['headers']) : 'No debug';
            log_message('error', 'Password reset email failed for ' . $email . ' | ' . $dbg);
            return false;
        }

        return true;
    }


/**
 * Verify Google reCAPTCHA token against Google's API.
 *
 * @param string $token
 * @param string $secret
 * @param string $version           v2_checkbox|v2_invisible|v3
 * @param float  $scoreThreshold    Used only for v3
 * @param string $expectedAction    Optional, used for v3 action checks
 * @return array [bool $ok, string|null $error]
 */
protected function verify_recaptcha_token(
    string $token,
    string $secret,
    string $version = 'v2_checkbox',
    float $scoreThreshold = 0.5,
    string $expectedAction = 'login'
): array {
    if (empty($token) || empty($secret)) {
        return [false, 'Security check failed. Please try again.'];
    }

    $endpoint = 'https://www.google.com/recaptcha/api/siteverify';

    $postData = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $this->input->ip_address(),
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 5,
        ],
    ]);

    $raw = @file_get_contents($endpoint, false, $context);

    if ($raw === false) {
        return [false, 'Unable to verify reCAPTCHA at this time. Please try again.'];
    }

    $resp = json_decode($raw, true) ?: [];

    if (empty($resp['success'])) {
        return [false, 'reCAPTCHA verification failed. Please try again.'];
    }

    // Additional checks for v3 (score and optional action)
    if ($version === 'v3') {
        $score  = isset($resp['score']) ? (float)$resp['score'] : 0.0;
        $action = $resp['action'] ?? '';

        if ($score < $scoreThreshold) {
            return [false, 'Suspicious activity detected. Please try again.'];
        }

        // If you want strict action match, uncomment:
        // if ($action && $action !== $expectedAction) {
        //     return [false, 'Invalid reCAPTCHA action. Please try again.'];
        // }
    }

    return [true, null];
}

    /**
     * Logout
     */
    public function logout()
    {
        // Capture data BEFORE destroying session
        $email   = $this->session->userdata('email');
        $user_id = $this->session->userdata('user_id');
    
        // Regenerate session ID (security)
        $this->session->sess_regenerate(TRUE);
    
        // Destroy CI session
        $this->session->sess_destroy();
    
        // Destroy native PHP session completely
        session_unset();
        session_destroy();
    
        // Prevent cached authenticated pages
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Cache-Control: post-check=0, pre-check=0', false)
            ->set_header('Pragma: no-cache');
    
        // Log safely (user_id already captured)
        log_message('debug', 'Session destroyed. ID was: ' . session_id());
        if ($user_id) {
            $this->Activity_log_model->add([
                'user_id'    => $user_id,
                'action'     => 'User logged out: ' . $email,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => substr($this->input->user_agent(), 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    
        redirect('authentication/login');
    }

    /**
     * Activity log helper
     */
    protected function log_activity(string $action)
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id') ?: 0,
            'action'     => $action,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr($this->input->user_agent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
