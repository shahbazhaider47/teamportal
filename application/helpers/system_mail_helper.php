<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('app_mailer')) {
    function app_mailer(): App_mailer
    {
        $CI =& get_instance();
        $CI->load->library('App_mailer');
        return $CI->app_mailer;
    }
}

if (!function_exists('system_email_config')) {
    function system_email_config(): array
    {
        $CI =& get_instance();
        $CI->load->model('System_settings_model', 'sysset');
        $S = $CI->sysset->get_all('email') ?: [];

        $protocol    = strtolower(trim($S['email_protocol'] ?? 'smtp'));
        $smtp_host   = trim($S['smtp_host']   ?? '');
        $smtp_port   = (int)($S['smtp_port']  ?? 587);
        $smtp_user   = trim($S['smtp_user']   ?? '');
        $smtp_pass   = (string)($S['smtp_pass'] ?? '');
        $smtp_crypto = strtolower(trim($S['smtp_crypto'] ?? 'tls'));

        if ($protocol === 'smtp') {
            if ($smtp_port === 465) $smtp_crypto = 'ssl';
            if ($smtp_port === 587) $smtp_crypto = 'tls';
        }

        $cfg = [
            'protocol' => $protocol,
            'mailtype' => 'text',
            'charset'  => 'utf-8',
            'wordwrap' => true,
            'newline'  => "\r\n",
            'crlf'     => "\r\n",
        ];

        if ($protocol === 'smtp') {
            $cfg['smtp_host']   = $smtp_host;
            $cfg['smtp_port']   = $smtp_port;
            $cfg['smtp_user']   = $smtp_user;
            $cfg['smtp_pass']   = $smtp_pass;
            if ($smtp_crypto === 'tls' || $smtp_crypto === 'ssl') {
                $cfg['smtp_crypto'] = $smtp_crypto;
            }
        }
        return $cfg;
    }
}

if (!function_exists('system_send_plain_email')) {
    function system_send_plain_email(string $to, string $subject, string $body): bool
    {
        return app_mailer()->send([
            'to'       => $to,
            'subject'  => $subject,
            'body'     => $body,
            'mailtype' => 'text',
        ]);
    }
}

if (!function_exists('get_email_from_defaults')) {
    function get_email_from_defaults(): array {
        $from_email = get_system_setting('from_email', '', 'email');
        $from_name  = get_system_setting('from_name',  'RCM Centric', 'email');

        if (empty($from_email)) {
            $host = parse_url(base_url(), PHP_URL_HOST);
            $from_email = 'no-reply@' . $host;
        }

        if (empty($from_name)) {
            $from_name = 'RCM Centric';
        }

        return [$from_email, $from_name];
    }
}
