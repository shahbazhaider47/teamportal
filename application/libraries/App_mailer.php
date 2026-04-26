<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * App_mailer
 * - Reads SMTP settings from system_settings (group 'email')
 * - Normalizes TLS/SSL vs. port
 * - Renders HTML views (application or module HMVC) with optional alt text/view
 * - Auto-builds plain-text fallback from HTML when missing
 * - CC/BCC, reply-to, file + string attachments
 * - Logs failures with recipient & subject context
 */
class App_mailer
{
    /** @var CI_Controller */
    protected $CI;

    /** Cached email settings (system_settings[group=email]) */
    protected $settings = [];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('email');  // load once here
        $this->CI->load->model('System_settings_model', 'sysset');
        $this->settings = $this->CI->sysset->get_all('email') ?: [];
    }

    /** Force refresh settings from DB (optional) */
    public function refresh_settings(): void
    {
        $this->settings = $this->CI->sysset->get_all('email') ?: [];
    }

    /**
     * Configure CI Email from DB settings (idempotent; safe to call per send).
     * You can pass $overrides to tweak config per message (rare).
     */
    public function configure(array $overrides = []): void
    {
        $S = $this->settings;
    
        $protocol    = strtolower(trim($S['email_protocol'] ?? 'smtp'));
        $smtp_host   = trim($S['smtp_host']   ?? '');
        $smtp_port   = (int)($S['smtp_port']  ?? 587);
        $smtp_user   = trim($S['smtp_user']   ?? '');
        $smtp_pass   = (string)($S['smtp_pass'] ?? '');
        $smtp_crypto = strtolower(trim($S['smtp_crypto'] ?? 'tls'));
    
        // Port-based normalization (only when NOT overridden)
        if ($protocol === 'smtp' && !isset($overrides['smtp_crypto'])) {
            if ($smtp_port === 465) $smtp_crypto = 'ssl';
            if ($smtp_port === 587) $smtp_crypto = 'tls';
        }
    
        // Build base config from DB settings first
        $base = [
            'protocol' => $protocol,
            'mailtype' => 'html',
            'charset'  => 'utf-8',
            'wordwrap' => true,
            'newline'  => "\r\n",
            'crlf'     => "\r\n",
        ];
    
        if ($protocol === 'smtp') {
            $base['smtp_host']   = $smtp_host;
            $base['smtp_port']   = $smtp_port;
            $base['smtp_user']   = $smtp_user;
            $base['smtp_pass']   = $smtp_pass;
            if ($smtp_crypto === 'tls' || $smtp_crypto === 'ssl') {
                $base['smtp_crypto'] = $smtp_crypto;
            }
        }
    
        // Overrides win over base — correct merge order
        $cfg = array_merge($base, $overrides);
    
        $this->CI->email->initialize($cfg);
    }
    /** Resolve default From using settings; fall back to company/domain */
    public function resolve_default_from(): array
    {
        // Prefer system_settings values if present
        $S = $this->settings ?: [];
        $from_email = trim($S['from_email'] ?? '');
        $from_name  = trim($S['from_name']  ?? '');
    
        // FALLBACKS come from your misc_helper:
        // - from_name  → get_company_name()
        // - from_email → get_business_email() then host-based no-reply
        if ($from_name === '' && function_exists('get_company_name')) {
            $from_name = get_company_name();                // << uses company_info table
        }
    
        if ($from_email === '' && function_exists('get_business_email')) {
            $from_email = trim(get_business_email());       // << uses company_info table
        }
    
        if ($from_email === '') {
            $host = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
            $from_email = 'no-reply@' . $host;
        }
        if ($from_name === '') {
            $from_name = 'System';
        }
        return [$from_email, $from_name];
    }


    public function brand_name(): string
    {
        // Prefer system_settings.from_name; fallback to helper; final fallback to 'System'
        $S = $this->settings ?: [];
        $name = trim($S['from_name'] ?? '');
        if ($name === '' && function_exists('get_company_name')) {
            $name = get_company_name();
        }
        return $name !== '' ? $name : 'System';
    }

    /** Render a view (supports application and module HMVC view paths) */
    protected function render_view(string $view, array $data = []): string
    {
        return $this->CI->load->view($view, $data, true);
    }

    /** Build a simple plaintext fallback from HTML */
    protected function html_to_text(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\R{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Send email.
     * Options:
     *  - to (string|array, required)
     *  - subject (string, required)
     *  - body (string, optional if 'view' provided) – HTML by default
     *  - mailtype ('html'|'text', optional; default 'html')
     *  - view (string, optional) + view_data (array)    → renders HTML body
     *  - alt_view (string, optional) + alt_data (array) → renders plain-text alternative
     *  - alt_text (string, optional)                    → explicit plain-text alternative
     *  - from_email / from_name (optional; defaults resolved from settings)
     *  - cc / bcc (string|array, optional)
     *  - reply_to (array ['email'=>'','name'=>''], optional)
     *  - attachments (array of file paths or arrays: ['path'=>...,'disposition'=>...,'filename'=>...,'mime'=>...])
     *  - string_attachments (array of arrays: ['data'=>..., 'filename'=>..., 'mime'=>...])
     *
     * @return bool true on success
     */
    public function send(array $opts): bool
    {
        $to         = $opts['to']        ?? null;
        $subject    = (string)($opts['subject'] ?? '');
        $body       = $opts['body']      ?? null;
        $mailtype   = strtolower($opts['mailtype'] ?? 'html');

        // Accept template inputs
        $view       = $opts['view']       ?? null;
        $view_data  = (array)($opts['view_data'] ?? []);
        $alt_view   = $opts['alt_view']   ?? null;
        $alt_data   = (array)($opts['alt_data'] ?? $view_data);
        $alt_text   = $opts['alt_text']   ?? null;

        $cc         = $opts['cc']         ?? null;
        $bcc        = $opts['bcc']        ?? null;
        $reply_to   = $opts['reply_to']   ?? null;
        $attachments= (array)($opts['attachments'] ?? []);
        $str_attach = (array)($opts['string_attachments'] ?? []);

        if (empty($to) || $subject === '') {
            log_message('error', 'App_mailer: missing "to" or "subject"');
            return false;
        }

        // Render HTML body from view if provided
        if ($body === null && $view) {
            $body = $this->render_view((string)$view, $view_data);
        }
        if ($body === null) {
            $body = ''; // allow empty body, but discouraged
        }

        // From (defaults resolved from settings)
        $from_email = $opts['from_email'] ?? null;
        $from_name  = $opts['from_name']  ?? null;
        if (!$from_email || !$from_name) {
            [$def_email, $def_name] = $this->resolve_default_from();
            $from_email = $from_email ?: $def_email;
            $from_name  = $from_name  ?: $def_name;
        }

        $E = $this->CI->email;
        $E->clear(true);        // clear previous state first
        $this->configure();
        $E->from($from_email, $from_name);

        // Recipients (string or array)
        is_array($to) ? $E->to($to) : $E->to((string)$to);
        if (!empty($cc))  { is_array($cc)  ? $E->cc($cc)  : $E->cc((string)$cc); }
        if (!empty($bcc)) { is_array($bcc) ? $E->bcc($bcc) : $E->bcc((string)$bcc); }

        // Reply-To
        if (is_array($reply_to) && !empty($reply_to['email'])) {
            $E->reply_to($reply_to['email'], $reply_to['name'] ?? '');
        }

        // Subject
        // If not passed explicitly, allow a template to set $subject (view_data['subject'])
        if ($subject === '' && !empty($view_data['subject'])) {
            $subject = (string)$view_data['subject'];
        }
        $E->subject($subject ?: '(no subject)');

        // Body + alt
        if ($mailtype === 'text') {
            $E->set_mailtype('text');
            $E->message($body);
        } else {
            $E->set_mailtype('html');
            $E->message($body);

            // Determine alt text
            if ($alt_view) {
                $alt = $this->render_view((string)$alt_view, $alt_data);
                $E->set_alt_message($alt);
            } elseif ($alt_text !== null) {
                $E->set_alt_message($alt_text);
            } else {
                $E->set_alt_message($this->html_to_text($body));
            }
        }

        // Attachments from files
        foreach ($attachments as $a) {
            if (is_string($a)) {
                if (is_file($a)) { $E->attach($a); }
            } elseif (is_array($a) && !empty($a['path']) && is_file($a['path'])) {
                $E->attach(
                    $a['path'],
                    $a['disposition'] ?? 'attachment',
                    $a['filename']    ?? null,
                    $a['mime']        ?? null
                );
            }
        }

        // In-memory string attachments
        foreach ($str_attach as $sa) {
            if (!is_array($sa)) continue;
            $data = $sa['data']     ?? null;
            $name = $sa['filename'] ?? null;
            $mime = $sa['mime']     ?? 'application/octet-stream';
            if ($data === null || !$name) continue;
        
            // Write to temp file, attach, schedule cleanup
            $tmp = tempnam(sys_get_temp_dir(), 'ci_mail_');
            file_put_contents($tmp, $data);
            $E->attach($tmp, 'attachment', $name, $mime);
            // Clean up after send — register a shutdown function
            register_shutdown_function(function() use ($tmp) {
                if (is_file($tmp)) @unlink($tmp);
            });
        }

        $ok = $E->send(false);
        if (!$ok && method_exists($E, 'print_debugger')) {
            // Include minimal context in logs
            $toStr = is_array($to) ? implode(',', $to) : (string)$to;
            log_message('error', 'App_mailer send() failed to [' . $toStr . '] subj="' . $subject . '": ' . $E->print_debugger(['headers']));
        }
        return $ok;
    }

    /** Convenience: quick plain-text */
    public function send_plain(string $to, string $subject, string $text): bool
    {
        return $this->send([
            'to'       => $to,
            'subject'  => $subject,
            'body'     => $text,
            'mailtype' => 'text',
        ]);
    }
}
