<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/AI_Provider_Interface.php';
// Groq is OpenAI-compatible — extend it, just override endpoint + models
require_once __DIR__ . '/OpenAI_Provider.php';

class Groq_Provider extends OpenAI_Provider
{
    protected const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(array $config)
    {
        $config['model'] = $config['model'] ?? 'llama-3.1-70b-versatile';
        parent::__construct($config);
    }

    public function getProviderName(): string { return 'groq'; }

    public function getAvailableModels(): array
    {
        return [
            'llama-3.1-70b-versatile' => 'Llama 3.1 70B (Free tier, fast)',
            'llama-3.1-8b-instant'    => 'Llama 3.1 8B (Fastest)',
            'mixtral-8x7b-32768'      => 'Mixtral 8x7B',
            'gemma2-9b-it'            => 'Gemma 2 9B',
        ];
    }

    // Override complete() to use Groq's endpoint
    public function complete(array $messages, array $options = []): array
    {
        $maxTokens   = (int)  ($options['max_tokens']  ?? $this->maxTokens);
        $temperature = (float)($options['temperature'] ?? $this->temperature);

        $fullMessages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt]],
            $messages
        );

        $payload = [
            'model'       => $this->model,
            'messages'    => $fullMessages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ];

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr)        return $this->_error('cURL: ' . $curlErr);
        if ($httpCode >= 400) {
            $d = json_decode($body, true);
            return $this->_error($d['error']['message'] ?? 'HTTP ' . $httpCode);
        }

        $d       = json_decode($body, true);
        $content = $d['choices'][0]['message']['content'] ?? null;
        if ($content === null) return $this->_error('Empty response from Groq.');

        return [
            'success'     => true,
            'content'     => trim($content),
            'error'       => '',
            'tokens_used' => (int)($d['usage']['total_tokens'] ?? 0),
            'model'       => $d['model'] ?? $this->model,
        ];
    }
}