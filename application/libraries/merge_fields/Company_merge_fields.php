<?php defined('BASEPATH') or exit('No direct script access allowed');

class Company_merge_fields implements MergeFieldProvider
{
    public function name(): string
    {
        return 'company';
    }

    public function fields(): array
    {
        return [
            ['name' => 'Company Name',     'key' => '{company.name}',      'available' => ['company', 'other']],
            ['name' => 'Business Phone',   'key' => '{company.phone}',     'available' => ['company', 'other']],
            ['name' => 'Business Email',   'key' => '{company.email}',     'available' => ['company', 'other']],
            ['name' => 'Address (Line)',   'key' => '{company.address}',   'available' => ['company', 'other']],
            ['name' => 'State/Province',   'key' => '{company.state}',     'available' => ['company', 'other']],
            ['name' => 'City',             'key' => '{company.city}',      'available' => ['company', 'other']],
            ['name' => 'ZIP/Postal Code',  'key' => '{company.zip}',       'available' => ['company', 'other']],
            ['name' => 'Full Address',     'key' => '{company.address_full}', 'available' => ['company', 'other']],
            ['name' => 'Light Logo URL',   'key' => '{company.logo_light}','available' => ['company', 'other']],
            ['name' => 'Dark Logo URL',    'key' => '{company.logo_dark}', 'available' => ['company', 'other']],
            ['name' => 'Favicon URL',      'key' => '{company.favicon}',   'available' => ['company', 'other']],
            ['name' => 'Created At',       'key' => '{company.created_at}','available' => ['company', 'other']],
            ['name' => 'Updated At',       'key' => '{company.updated_at}','available' => ['company', 'other']],
        ];
    }

    public function format(array $ctx = []): array
    {
        $CI = &get_instance();

        // Fetch the single company row
        $row = $CI->db->limit(1)->get('company_info')->row_array() ?: [];

        // Helper to convert stored relative paths to absolute URLs
        $toUrl = function ($path) use ($CI) {
            $p = trim((string)$path);
            if ($p === '') return '';
            // If already absolute (http/https/data), return as-is
            if (preg_match('#^(?:https?:)?//#i', $p) || str_starts_with($p, 'data:')) {
                return $p;
            }
            // Otherwise assume it's a relative path stored in DB
            if (function_exists('base_url')) {
                return rtrim(base_url($p), '/');
            }
            return $p;
        };

        $name   = (string)($row['company_name']   ?? '');
        $phone  = (string)($row['business_phone'] ?? '');
        $email  = (string)($row['business_email'] ?? '');

        $addr   = (string)($row['address']   ?? '');
        $state  = (string)($row['state']     ?? '');
        $city   = (string)($row['city']      ?? '');
        $zip    = (string)($row['zip_code']  ?? '');

        $logoL  = $toUrl($row['light_logo'] ?? '');
        $logoD  = $toUrl($row['dark_logo']  ?? '');
        $fav    = $toUrl($row['favicon']    ?? '');

        $created= (string)($row['created_at'] ?? '');
        $updated= (string)($row['updated_at'] ?? '');

        // Compose a sensible single-line address
        $parts = array_filter([$addr, $city, $state, $zip], fn($v) => trim((string)$v) !== '');
        $addrFull = implode(', ', $parts);

        return [
            '{company.name}'         => $name,
            '{company.phone}'        => $phone,
            '{company.email}'        => $email,

            '{company.address}'      => $addr,
            '{company.state}'        => $state,
            '{company.city}'         => $city,
            '{company.zip}'          => $zip,
            '{company.address_full}' => $addrFull,

            '{company.logo_light}'   => $logoL,
            '{company.logo_dark}'    => $logoD,
            '{company.favicon}'      => $fav,

            '{company.created_at}'   => $created,
            '{company.updated_at}'   => $updated,
        ];
    }
}