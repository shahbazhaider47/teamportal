<?php defined('BASEPATH') or exit('No direct script access allowed');

class Ai_chat
{
    protected $CI;
    protected AI_Provider_Interface $provider;
    protected bool $enabled = false;

    // Encryption key — set this in config/config.php as $config['ai_encryption_key']
    // Must be exactly 32 bytes for AES-256
    protected string $encKey = '';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();

        $this->encKey = $this->CI->config->item('ai_encryption_key') ?: '';

        $settings = $this->_loadSettings();

        $this->enabled = ((int)($settings['ai_enabled'] ?? 0) === 1);

        if ($this->enabled) {
            $this->provider = $this->_makeProvider($settings);
        }
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function complete(array $messages, array $options = []): array
    {
        if (!$this->enabled) {
            return [
                'success'     => false,
                'content'     => '',
                'error'       => 'AI assistant is not enabled.',
                'tokens_used' => 0,
                'model'       => '',
            ];
        }

        return $this->provider->complete($messages, $options);
    }

    public function getProviderName(): string
    {
        return $this->enabled ? $this->provider->getProviderName() : '';
    }

    // Used by settings page to populate model dropdown dynamically
    public function getModelsForProvider(string $provider): array
    {
        $p = $this->_makeProviderByName($provider, [
            'api_key'       => '',
            'model'         => '',
            'system_prompt' => '',
        ]);
        return $p ? $p->getAvailableModels() : [];
    }

    // ── Settings helpers (used by settings controller) ────────────────────────

    public function encryptKey(string $plaintext): string
    {
        if ($plaintext === '' || $this->encKey === '') return $plaintext;

        $iv         = random_bytes(16);
        $encrypted  = openssl_encrypt($plaintext, 'AES-256-CBC', $this->encKey, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public function decryptKey(string $ciphertext): string
    {
        if ($ciphertext === '' || $this->encKey === '') return $ciphertext;

        $decoded = base64_decode($ciphertext);
        if ($decoded === false || strpos($decoded, '::') === false) return $ciphertext;

        [$iv, $encrypted] = explode('::', $decoded, 2);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->encKey, 0, $iv);
        return $decrypted !== false ? $decrypted : $ciphertext;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    protected function _loadSettings(): array
    {
        $rows = $this->CI->db
            ->where_in('key', [
                'ai_enabled',
                'ai_provider',
                'ai_model',
                'ai_api_key',
                'ai_max_tokens',
                'ai_temperature',
                'ai_system_prompt',
            ])
            ->get('system_settings')
            ->result_array();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['key']] = $r['value'] ?? '';
        }
        return $map;
    }

    protected function _makeProvider(array $settings): AI_Provider_Interface
    {
        $provider = strtolower(trim((string)($settings['ai_provider'] ?? '')));

        $config = [
            'api_key'       => $this->decryptKey((string)($settings['ai_api_key'] ?? '')),
            'model'         => (string)($settings['ai_model'] ?? ''),
            'system_prompt' => (string)($settings['ai_system_prompt'] ?? ''),
            'max_tokens'    => (int)($settings['ai_max_tokens'] ?? 1024),
            'temperature'   => (float)($settings['ai_temperature'] ?? 0.3),
        ];

        $instance = $this->_makeProviderByName($provider, $config);

        if ($instance === null) {
            throw new RuntimeException('Unknown AI provider: ' . $provider);
        }

        return $instance;
    }

    protected function _makeProviderByName(string $provider, array $config): ?AI_Provider_Interface
    {
        $base = APPPATH . 'libraries/AI/';

        switch ($provider) {
            case 'claude':
                require_once $base . 'Claude_Provider.php';
                return new Claude_Provider($config);
            case 'openai':
                require_once $base . 'OpenAI_Provider.php';
                return new OpenAI_Provider($config);
            case 'groq':
                require_once $base . 'Groq_Provider.php';
                return new Groq_Provider($config);
            case 'gemini':
                require_once $base . 'Gemini_Provider.php';
                return new Gemini_Provider($config);
            default:
                return null;
        }
    }
}