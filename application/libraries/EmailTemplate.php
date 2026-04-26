<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * EmailTemplate
 * - Render templates by slug with {placeholders} (supports dot-path and |default)
 * - Send immediately via CI Email OR enqueue to email_outbox (if email_queue_enabled)
 */
class EmailTemplate
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper(['url']);
        $this->CI->load->model('System_settings_model', 'sysset'); // uses your existing model
    }

    /**
     * Render a template by slug and optional lang.
     * Falls back to a safe generic template if not found or inactive.
     */
    public function render(string $slug, array $context = [], ?string $lang = null): array
    {
        $tpl = $this->findTemplate($slug, $lang);

        if (!$tpl) {
            // Safe fallback to avoid breaking modules
            $subject = $this->applyPlaceholders('Notification from {_app.name}', $this->baseContext($context));
            $html    = $this->applyPlaceholders(
                '<p>Hello {user.fullname|there},</p><p>You have a new message.</p>',
                $this->baseContext($context)
            );
            $text    = strip_tags($html);

            log_message('warning', 'EmailTemplate: slug not found or inactive: ' . $slug);
            return ['subject' => $subject, 'html' => $html, 'text' => $text];
        }

        $ctx     = $this->baseContext($context);
        $subject = $this->applyPlaceholders($tpl['subject'], $ctx);
        $html    = $this->applyPlaceholders($tpl['body_html'], $ctx);
        $text    = !empty($tpl['body_text'])
            ? $this->applyPlaceholders($tpl['body_text'], $ctx)
            : $this->htmlToText($html);

        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }

    /**
     * Send (or enqueue) an email using a template.
     * $options:
     *   - queue: bool (default: use get_setting('email_queue_enabled'))
     *   - scheduled_at: 'Y-m-d H:i:s' (optional)
     */
    public function send(string $slug, string $toEmail, string $toName = '', array $context = [], array $options = []): bool
    {
        $rendered = $this->render($slug, $context, $options['lang'] ?? null);

        // Router: queue or immediate
        $queueEnabled = (bool) get_setting('email_queue_enabled');
        $shouldQueue  = array_key_exists('queue', $options) ? (bool)$options['queue'] : $queueEnabled;

        if ($shouldQueue) {
            return $this->enqueue($slug, $toEmail, $toName, $rendered, $context, $options['scheduled_at'] ?? null);
        }

        return $this->sendNow($toEmail, $toName, $rendered);
    }

    /* ============================ Internals ============================ */

protected function findTemplate(string $slug, ?string $lang = null): ?array
{
    // 1) Try Perfex-style table used by the UI: emailtemplates
    $q = $this->CI->db->where(['slug' => $slug, 'active' => 1]);
    if ($lang) $q->where('language', $lang);
    $row = $q->get('emailtemplates')->row_array();
    if ($row) {
        return [
            'subject'   => $row['subject'] ?? '',
            'body_html' => $row['message'] ?? '',
            'body_text' => !empty($row['plaintext']) ? strip_tags($row['message'] ?? '') : null,
        ];
    }

    // 2) Fallback to your custom table (if you also keep it)
    if ($lang) {
        $row = $this->CI->db->where(['slug' => $slug, 'lang' => $lang, 'is_active' => 1])
                            ->get('email_templates')->row_array();
        if ($row) return $row;
    }
    $row = $this->CI->db->where(['slug' => $slug, 'is_active' => 1])
                        ->where('lang IS NULL', null, false)
                        ->get('email_templates')->row_array();
    return $row ?: null;
}


protected function baseContext(array $context): array
{
    $app = [
        'name'     => get_setting('companyname') ?: 'Your Application',
        'base_url' => base_url(),
        'year'     => date('Y'),
    ];
    $context['_app'] = $app; // always overwrite so it's fresh
    return $context;
}

    protected function applyPlaceholders(string $template, array $context): string
    {
        // Supports {a.b.c|Default text}
        return preg_replace_callback('/\{([a-zA-Z0-9_\.\-]+)(\|[^}]+)?\}/', function ($m) use ($context) {
            $path    = $m[1];
            $default = isset($m[2]) ? ltrim($m[2], '|') : '';
            $value   = $this->getByDotPath($context, $path);
            if ($value === null || $value === '') {
                return $default;
            }
            return (string) $value;
        }, $template);
    }

    protected function getByDotPath(array $arr, string $path)
    {
        $parts = explode('.', $path);
        $cur   = $arr;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
            } else {
                return null;
            }
        }
        return $cur;
    }

    protected function htmlToText(string $html): string
    {
        // very basic HTML→text fallback
        $text = preg_replace('#<br\s*/?>#i', "\n", $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim($text);
    }

    protected function enqueue(string $slug, string $toEmail, string $toName, array $rendered, array $context, ?string $scheduledAt): bool
    {
        $payload = [
            'to_email'      => $toEmail,
            'to_name'       => $toName ?: null,
            'subject'       => $rendered['subject'],
            'body_html'     => $rendered['html'],
            'body_text'     => $rendered['text'] ?? null,
            'template_slug' => $slug,
            'payload_json'  => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'        => 'queued',
            'attempts'      => 0,
            'scheduled_at'  => $scheduledAt,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        return (bool) $this->CI->db->insert('email_outbox', $payload);
    }

    protected function sendNow(string $toEmail, string $toName, array $rendered): bool
    {
        $this->CI->load->library('App_mailer', null, 'app_mailer');
    
        return $this->CI->app_mailer->send([
            'to'       => $toEmail,
            'subject'  => $rendered['subject'],
            'body'     => $rendered['html'],
            'alt_text' => $rendered['text'] ?? null,
            'mailtype' => 'html',
        ]);
    }
}
