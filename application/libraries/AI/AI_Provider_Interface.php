<?php defined('BASEPATH') or exit('No direct script access allowed');

interface AI_Provider_Interface
{
    /**
     * Send a conversation and get a reply.
     *
     * @param  array  $messages  [ ['role'=>'user'|'assistant', 'content'=>'...'], ... ]
     * @param  array  $options   Override max_tokens, temperature per-call if needed
     * @return array  [
     *   'success'       => bool,
     *   'content'       => string,   // the reply text
     *   'error'         => string,   // empty on success
     *   'tokens_used'   => int,      // 0 if provider doesn't return it
     *   'model'         => string,   // actual model used
     * ]
     */
    public function complete(array $messages, array $options = []): array;

    /**
     * Return the provider slug.
     */
    public function getProviderName(): string;

    /**
     * Return available models for this provider (for settings dropdown).
     */
    public function getAvailableModels(): array;
}