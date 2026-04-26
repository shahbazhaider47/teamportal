<?php defined('BASEPATH') or exit('No direct script access allowed');

interface MergeFieldProvider
{
    /** Group key, e.g., 'company', 'user', 'ticket' */
    public function name(): string;

    /**
     * Return list of fields.
     * Each: ['name' => 'Company Name', 'key' => '{company.name}', 'available' => ['company','other']]
     */
    public function fields(): array;

    /**
     * Build key=>value map given $ctx (e.g., ['user_id'=>5,'ticket_id'=>123,'company'=>true])
     * Return: ['{company.name}' => 'RCM Centric', ...]
     */
    public function format(array $ctx): array;
}