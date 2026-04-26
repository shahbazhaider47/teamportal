<?php defined('BASEPATH') or exit('No direct script access allowed');

class Ai_chat_controller extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Ai_chat');
    }

    // ── POST /ai_chat/send ────────────────────────────────────────────────────
    public function send()
    {
        if (!$this->session->userdata('is_logged_in')) {
            return $this->_json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }
        if ($this->input->method() !== 'post') {
            return $this->_json(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        if (!$this->ai_chat->isEnabled()) {
            return $this->_json(['success' => false, 'error' => 'AI assistant is not enabled. Contact your administrator.']);
        }

        $message = trim((string)($this->input->post('message') ?? ''));
        if ($message === '' || mb_strlen($message) > 2000) {
            return $this->_json(['success' => false, 'error' => 'Invalid message length.']);
        }

        // History from browser — strip anything but role+content
        $historyRaw = $this->input->post('history') ?? '[]';
        $history    = json_decode($historyRaw, true);
        if (!is_array($history)) $history = [];

        // Safe for PHP 7.2+
        $history = array_values(array_filter(
            array_slice($history, -20),
            function ($m) {
                return isset($m['role'], $m['content'])
                    && in_array($m['role'], ['user', 'assistant'], true)
                    && is_string($m['content']);
            }
        ));

        // Append current message
        $history[] = ['role' => 'user', 'content' => $message];

        $result = $this->ai_chat->complete($history);

        if (!$result['success']) {
            return $this->_json(['success' => false, 'error' => $result['error']]);
        }

        return $this->_json([
            'success'     => true,
            'reply'       => $result['content'],
            'tokens_used' => $result['tokens_used'],
            'model'       => $result['model'],
        ]);
    }

    // ── POST /ai_chat/test — called from settings page ────────────────────────
    public function test()
    {
        if (!$this->session->userdata('is_logged_in')) {
            return $this->_json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }
        if ($this->input->method() !== 'post') {
            return $this->_json(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        // Allow testing with values not yet saved
        $provider  = trim((string)($this->input->post('provider')  ?? ''));
        $apiKey    = trim((string)($this->input->post('api_key')   ?? ''));
        $model     = trim((string)($this->input->post('model')     ?? ''));

        if (!$provider || !$apiKey || !$model) {
            return $this->_json(['success' => false, 'error' => 'Provider, API key and model are required.']);
        }

        // Build a temporary provider directly — don't touch DB settings
        $base = APPPATH . 'libraries/AI/';
        $config = [
            'api_key'       => $apiKey,
            'model'         => $model,
            'system_prompt' => 'You are a helpful assistant.',
            'max_tokens'    => 64,
            'temperature'   => 0.3,
        ];

        try {
            switch ($provider) {
                case 'claude':
                    require_once $base . 'Claude_Provider.php';
                    $p = new Claude_Provider($config);
                    break;
                case 'openai':
                    require_once $base . 'OpenAI_Provider.php';
                    $p = new OpenAI_Provider($config);
                    break;
                case 'groq':
                    require_once $base . 'Groq_Provider.php';
                    $p = new Groq_Provider($config);
                    break;
                case 'gemini':
                    require_once $base . 'Gemini_Provider.php';
                    $p = new Gemini_Provider($config);
                    break;
                default:
                    return $this->_json(['success' => false, 'error' => 'Unknown provider.']);
            }

            $result = $p->complete([['role' => 'user', 'content' => 'Reply with exactly: OK']]);

            if ($result['success']) {
                return $this->_json([
                    'success' => true,
                    'message' => 'Connection successful.',
                    'model'   => $result['model'],
                ]);
            }

            return $this->_json(['success' => false, 'error' => $result['error']]);

        } catch (Throwable $e) {
            return $this->_json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── GET /ai_chat/models?provider=claude ───────────────────────────────────
    public function models()
    {
        if (!$this->session->userdata('is_logged_in')) {
            return $this->_json(['success' => false], 401);
        }

        $provider = trim((string)($this->input->get('provider') ?? ''));
        $models   = $this->ai_chat->getModelsForProvider($provider);

        return $this->_json(['success' => true, 'models' => $models]);
    }

    protected function _json(array $data, int $status = 200): void
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}