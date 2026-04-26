<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/AI_Provider_Interface.php';

class Gemini_Provider implements AI_Provider_Interface
{
    protected string $apiKey;
    protected string $model;
    protected string $systemPrompt;
    protected int    $maxTokens;
    protected float  $temperature;

    protected const ENDPOINT_TPL =
        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    public function __construct(array $config)
    {
        $this->apiKey       = (string)($config['api_key']       ?? '');
        $this->model        = (string)($config['model']         ?? 'gemini-1.5-flash');
        $this->systemPrompt = (string)($config['system_prompt'] ?? '');
        $this->maxTokens    = (int)   ($config['max_tokens']    ?? 1024);
        $this->temperature  = (float) ($config['temperature']   ?? 0.3);
    }

    public function complete(array $messages, array $options = []): array
    {
        $maxTokens   = (int)  ($options['max_tokens']  ?? $this->maxTokens);
        $temperature = (float)($options['temperature'] ?? $this->temperature);

        // Gemini uses a different message format: role is 'user' or 'model'
        $contents = [];
        foreach ($messages as $m) {
            $contents[] = [
                'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ];
        }

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $this->systemPrompt]],
            ],
            'contents'           => $contents,
            'generationConfig'   => [
                'maxOutputTokens' => $maxTokens,
                'temperature'     => $temperature,
            ],
        ];

        $url = sprintf(self::ENDPOINT_TPL, urlencode($this->model), urlencode($this->apiKey));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr)         return $this->_error('cURL: ' . $curlErr);
        if ($httpCode >= 400) {
            $d = json_decode($body, true);
            return $this->_error($d['error']['message'] ?? 'HTTP ' . $httpCode);
        }

        $d       = json_decode($body, true);
        $content = $d['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($content === null) return $this->_error('Empty response from Gemini.');

        $tokensUsed = ($d['usageMetadata']['promptTokenCount']    ?? 0)
                    + ($d['usageMetadata']['candidatesTokenCount'] ?? 0);

        return [
            'success'     => true,
            'content'     => trim($content),
            'error'       => '',
            'tokens_used' => (int)$tokensUsed,
            'model'       => $this->model,
        ];
    }

    public function getProviderName(): string { return 'gemini'; }

    public function getAvailableModels(): array
    {
        return [
            'gemini-1.5-flash'   => 'Gemini 1.5 Flash (Fast, free tier)',
            'gemini-1.5-pro'     => 'Gemini 1.5 Pro (Most capable)',
            'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)',
        ];
    }

    protected function _error(string $msg): array
    {
        return ['success' => false, 'content' => '', 'error' => $msg,
                'tokens_used' => 0, 'model' => $this->model];
    }
}