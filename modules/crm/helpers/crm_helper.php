<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_menu_by_slug')) {
    /**
     * Find a menu item by slug in a menus array
     */
    function get_menu_by_slug(array $menus, string $slug): ?array
    {
        foreach ($menus as $m) {
            if (($m['slug'] ?? '') === $slug) {
                return $m;
            }
        }
        return null;
    }
}

if (!function_exists('crm_generate_client_code')) {
    function crm_generate_client_code(): string
    {
        $CI = &get_instance();
        $CI->load->database();
        $prefix = 'PRC-';
        $CI->db->select('client_code')
               ->from('crm_clients')
               ->like('client_code', $prefix, 'after')
               ->order_by('id', 'DESC')
               ->limit(1);

        $row = $CI->db->get()->row_array();
        $nextNumber = 1;

        if (!empty($row['client_code'])) {
            $number = (int) preg_replace('/[^0-9]/', '', $row['client_code']);
            $nextNumber = $number + 1;
        }
        
        return $prefix . str_pad((string)$nextNumber, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('practice_types')) {
    function practice_types(): array
    {
        return [
            'Solo Practice',
            'Group Practice',
            'Clinic',
            'Hospital',
            'Urgent Care',
            'Specialty Practice',
            'Diagnostic Center',
            'Ambulatory Surgery Center',
            'Telehealth Practice',
            'Billing Company',
            'Management Services Organization (MSO)',
        ];
    }
}

if (!function_exists('practice_types_dropdown')) {
    function practice_types_dropdown(
        bool $include_blank = true,
        string $blank_label = '-- Select Practice Type --'
    ): array {
        $types = practice_types();
        $dropdown = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($types as $type) {
            $dropdown[$type] = $type;
        }

        return $dropdown;
    }
}


if (!function_exists('contact_titles')) {
    function contact_titles(): array
    {
        return [
            'Physician / Doctor',
            'Medical Director',
            'Chief Medical Officer (CMO)',
            'Surgeon',
            'Specialist',
            'Nurse Practitioner (NP)',
            'Physician Assistant (PA)',
            'Registered Nurse (RN)',
            'Practice Administrator',
            'Practice Manager',
            'Office Manager',
            'Business Office Manager',
            'Front Office Manager',
            'Back Office Manager',
            'Clinical Director',
            'Billing Manager',
            'Revenue Cycle Manager',
            'Accounts Receivable Manager',
            'Billing Coordinator',
            'Medical Biller',
            'Insurance Specialist',
            'CEO / President',
            'COO (Chief Operating Officer)',
            'CFO (Chief Financial Officer)',
            'Vice President (VP)',
            'Director',
            'Owner',
            'Partner',
            'Operations Manager',
            'Account Manager',
            'Client Services Manager',
            'Medical Records Manager',
            'Compliance Officer',
            'Credentialing Specialist',
            'Medical Staff Coordinator',
            'Scheduling Coordinator',
            'Patient Coordinator',
            'Financial Counselor',
            'Receptionist',
            'Front Desk Staff',
            'Medical Secretary',
            'Group Practice',
            'Clinic',
            'Hospital',
            'Urgent Care',
            'Specialty Practice',
            'Diagnostic Center',
            'Ambulatory Surgery Center',
            'Telehealth Practice',
            'Billing Company',
            'Management Services Organization (MSO)',
            'Manager',
            'Supervisor',
            'Coordinator',
            'Specialist',
            'Representative',
            'Other'
        ];
    }
}

if (!function_exists('contact_titles_dropdown')) {
    function contact_titles_dropdown(
        bool $include_blank = true,
        string $blank_label = '-- Select Title --'
    ): array {
        $types = contact_titles();
        $dropdown = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($types as $type) {
            $dropdown[$type] = $type;
        }

        return $dropdown;
    }
}


if (!function_exists('lead_source_types')) {
    function lead_source_types(): array
    {
        return [
            'Website Inquiry',
            'Referral - Existing Client',
            'Referral - Partner',
            'LinkedIn',
            'Cold Email Campaign',
            'Cold Calling',
            'Google Ads',
            'Facebook / Meta Ads',
            'Conference / Event',
            'Webinar',
            'Industry Directory',
            'Upwork / Freelancer',
            'Inbound Call',
            'Email Inquiry',
            'Marketing Campaign',
            'Vendor Partner',
            'Reseller / Affiliate',
            'Other',
        ];
    }
}

if (!function_exists('lead_source_dropdown')) {
    function lead_source_dropdown(
        bool $include_blank = true,
        string $blank_label = '-- Select Lead Source --'
    ): array {
        $types = lead_source_types();
        $dropdown = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($types as $type) {
            $dropdown[$type] = $type;
        }

        return $dropdown;
    }
}


if (!function_exists('lead_status_types')) {
    function lead_status_types(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'proposal_sent' => 'Proposal Sent',
            'negotiation' => 'Negotiation',
            'demo_scheduled' => 'Demo Scheduled',
            'demo_completed' => 'Demo Completed',
            'contract_sent' => 'Contract Sent',
            'contract_signed' => 'Contract Signed',
            'lost' => 'Lost',
            'disqualified' => 'Disqualified',
        ];
    }
}

if (!function_exists('lead_status_dropdown')) {
    function lead_status_dropdown(
        bool $include_blank = true,
        string $blank_label = '-- Select Lead Status --'
    ): array {
        $types = lead_status_types();
        $dropdown = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($types as $type) {
            $dropdown[$type] = $type;
        }

        return $dropdown;
    }
}


if (!function_exists('lead_source_meta')) {
    function lead_source_meta(): array
    {
        return [
            'Website Inquiry' => [
                'label' => 'Website Inquiry',
                'icon'  => 'ti ti-world-www',
            ],
            'Referral - Existing Client' => [
                'label' => 'Referral - Existing Client',
                'icon'  => 'ti ti-users',
            ],
            'Referral - Partner' => [
                'label' => 'Referral - Partner',
                'icon'  => 'ti ti-handshake',
            ],
            'LinkedIn' => [
                'label' => 'LinkedIn',
                'icon'  => 'ti ti-brand-linkedin',
            ],
            'Cold Email Campaign' => [
                'label' => 'Cold Email Campaign',
                'icon'  => 'ti ti-mail',
            ],
            'Cold Calling' => [
                'label' => 'Cold Calling',
                'icon'  => 'ti ti-phone-call',
            ],
            'Google Ads' => [
                'label' => 'Google Ads',
                'icon'  => 'ti ti-brand-google',
            ],
            'Facebook / Meta Ads' => [
                'label' => 'Facebook / Meta Ads',
                'icon'  => 'ti ti-brand-facebook',
            ],
            'Conference / Event' => [
                'label' => 'Conference / Event',
                'icon'  => 'ti ti-calendar-event',
            ],
            'Webinar' => [
                'label' => 'Webinar',
                'icon'  => 'ti ti-device-desktop',
            ],
            'Industry Directory' => [
                'label' => 'Industry Directory',
                'icon'  => 'ti ti-list-search',
            ],
            'Upwork / Freelancer' => [
                'label' => 'Upwork / Freelancer',
                'icon'  => 'ti ti-briefcase',
            ],
            'Inbound Call' => [
                'label' => 'Inbound Call',
                'icon'  => 'ti ti-phone-incoming',
            ],
            'Email Inquiry' => [
                'label' => 'Email Inquiry',
                'icon'  => 'ti ti-at',
            ],
            'Marketing Campaign' => [
                'label' => 'Marketing Campaign',
                'icon'  => 'ti ti-speakerphone',
            ],
            'Vendor Partner' => [
                'label' => 'Vendor Partner',
                'icon'  => 'ti ti-building-store',
            ],
            'Reseller / Affiliate' => [
                'label' => 'Reseller / Affiliate',
                'icon'  => 'ti ti-network',
            ],
            'Other' => [
                'label' => 'Other',
                'icon'  => 'ti ti-dots',
            ],
        ];
    }
}

if (!function_exists('lead_source_types')) {
    function lead_source_types(): array
    {
        return array_keys(lead_source_meta());
    }
}

if (!function_exists('lead_source_dropdown')) {
    function lead_source_dropdown(
        bool $include_blank = true,
        string $blank_label = '-- Select Lead Source --'
    ): array {
        $sources  = lead_source_meta();
        $dropdown = [];

        if ($include_blank) {
            $dropdown[''] = $blank_label;
        }

        foreach ($sources as $key => $meta) {
            $dropdown[$key] = $meta['label'];
        }

        return $dropdown;
    }
}

if (!function_exists('get_lead_source_icon')) {
    function get_lead_source_icon(?string $source): string
    {
        $sources = lead_source_meta();

        if (!$source || !isset($sources[$source])) {
            return 'ti ti-circle-dashed';
        }

        return $sources[$source]['icon'] ?? 'ti ti-circle-dashed';
    }
}

if (!function_exists('get_lead_source_label')) {
    function get_lead_source_label(?string $source): string
    {
        $sources = lead_source_meta();

        if (!$source || !isset($sources[$source])) {
            return '—';
        }

        return $sources[$source]['label'] ?? '—';
    }
}

if (!function_exists('render_lead_source')) {
    function render_lead_source(?string $source): string
    {
        $icon  = get_lead_source_icon($source);
        $label = get_lead_source_label($source);

        return '<span class="lead-source-display d-inline-flex align-items-center gap-1">'
            . '<i class="' . html_escape($icon) . ' text-info" style="font-size:14px"></i>'
            . '<span>' . html_escape($label) . '</span>'
            . '</span>';
    }
}



if (!function_exists('crm_lead_status_map')) {
    function crm_lead_status_map(): array
    {
        return [
            'new' => [
                'label' => 'New',
                'bg'    => '#e8f4fd',
                'color' => '#1a73e8',
            ],
            'contacted' => [
                'label' => 'Contacted',
                'bg'    => '#fff3e0',
                'color' => '#f57c00',
            ],
            'qualified' => [
                'label' => 'Qualified',
                'bg'    => '#e8f5e9',
                'color' => '#2e7d32',
            ],
            'demo_scheduled' => [
                'label' => 'Demo Scheduled',
                'bg'    => '#e0f2fe',
                'color' => '#0284c7',
            ],
            'demo_completed' => [
                'label' => 'Demo Completed',
                'bg'    => '#ede9fe',
                'color' => '#7c3aed',
            ],
            'proposal_sent' => [
                'label' => 'Proposal Sent',
                'bg'    => '#f3e5f5',
                'color' => '#6a1b9a',
            ],
            'negotiation' => [
                'label' => 'Negotiation',
                'bg'    => '#fce7f3',
                'color' => '#be185d',
            ],
            'contract_sent' => [
                'label' => 'Contract Sent',
                'bg'    => '#fff7ed',
                'color' => '#c2410c',
            ],
            'contract_signed' => [
                'label' => 'Contract Signed',
                'bg'    => '#dcfce7',
                'color' => '#15803d',
            ],
            'lost' => [
                'label' => 'Lost',
                'bg'    => '#f8fafc',
                'color' => '#64748b',
            ],
            'disqualified' => [
                'label' => 'Disqualified',
                'bg'    => '#fef2f2',
                'color' => '#b91c1c',
            ],
        ];
    }
}

if (!function_exists('crm_lead_status_meta')) {
    function crm_lead_status_meta(?string $status): array
    {
        $status = strtolower(trim((string)$status));
        $map    = crm_lead_status_map();

        if (isset($map[$status])) {
            return $map[$status] + ['key' => $status];
        }

        return [
            'key'   => $status,
            'label' => ucwords(str_replace('_', ' ', $status !== '' ? $status : 'Unknown')),
            'bg'    => '#f1f5f9',
            'color' => '#475569',
        ];
    }
}

if (!function_exists('crm_lead_status_label')) {
    function crm_lead_status_label(?string $status): string
    {
        $meta = crm_lead_status_meta($status);
        return (string)$meta['label'];
    }
}

if (!function_exists('crm_lead_status_badge_style')) {
    function crm_lead_status_badge_style(?string $status): string
    {
        $meta = crm_lead_status_meta($status);
        return 'background:' . $meta['bg'] . ';color:' . $meta['color'] . ';';
    }
}


if (!function_exists('crm_activity_icon_class')) {
    function crm_activity_icon_class($action)
    {
        switch ($action) {
            case 'created':
                return ['icon' => 'ti ti-plus', 'wrap' => 'crm-note'];
            case 'updated':
                return ['icon' => 'ti ti-edit', 'wrap' => 'crm-note'];
            case 'deleted':
                return ['icon' => 'ti ti-trash', 'wrap' => 'crm-status'];
            case 'restored':
                return ['icon' => 'ti ti-restore', 'wrap' => 'crm-status'];
            case 'assigned':
                return ['icon' => 'ti ti-user-check', 'wrap' => 'crm-task'];
            case 'status_changed':
                return ['icon' => 'ti ti-refresh', 'wrap' => 'crm-status'];
            case 'verified':
                return ['icon' => 'ti ti-shield-check', 'wrap' => 'crm-call'];
            case 'unverified':
                return ['icon' => 'ti ti-shield-x', 'wrap' => 'crm-status'];
            case 'forecast_updated':
                return ['icon' => 'ti ti-chart-line', 'wrap' => 'crm-meeting'];
            case 'imported':
                return ['icon' => 'ti ti-file-import', 'wrap' => 'crm-email'];
            case 'called':
                return ['icon' => 'ti ti-phone', 'wrap' => 'crm-call'];
            case 'emailed':
                return ['icon' => 'ti ti-mail', 'wrap' => 'crm-email'];
            case 'meeting_logged':
                return ['icon' => 'ti ti-calendar-event', 'wrap' => 'crm-meeting'];
            case 'note_added':
                return ['icon' => 'ti ti-note', 'wrap' => 'crm-note'];
            case 'needs_updated':
                return ['icon' => 'ti ti-checklist', 'wrap' => 'crm-note'];
            default:
                return ['icon' => 'ti ti-activity', 'wrap' => 'crm-note'];
        }
    }
}

if (!function_exists('crm_activity_title')) {
    function crm_activity_title($action)
    {
        switch ($action) {
            case 'created':
                return 'Lead Created';
            case 'updated':
                return 'Lead Updated';
            case 'deleted':
                return 'Lead Deleted';
            case 'restored':
                return 'Lead Restored';
            case 'assigned':
                return 'Lead Assignment Updated';
            case 'status_changed':
                return 'Lead Status Updated';
            case 'verified':
                return 'Lead Verified';
            case 'unverified':
                return 'Lead Unverified';
            case 'forecast_updated':
                return 'Forecast Updated';
            case 'imported':
                return 'Lead Import Executed';
            case 'called':
                return 'Call Logged';
            case 'emailed':
                return 'Email Logged';
            case 'meeting_logged':
                return 'Meeting Logged';
            case 'note_added':
                return 'Note Added';
            case 'needs_updated':
                return 'Needs & Criteria Updated';                
            default:
                return ucwords(str_replace('_', ' ', (string)$action));
        }
    }
}

if (!function_exists('crm_activity_badges')) {
    function crm_activity_badges(array $activity)
    {
        $badges = [];
        $action = (string)($activity['action'] ?? '');
        $meta   = [];

        if (!empty($activity['metadata'])) {
            $decoded = json_decode((string)$activity['metadata'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        switch ($action) {
            case 'status_changed':
                if (!empty($meta['old_status']) || !empty($meta['new_status'])) {
                    $old = !empty($meta['old_status']) ? ucwords(str_replace('_', ' ', $meta['old_status'])) : 'N/A';
                    $new = !empty($meta['new_status']) ? ucwords(str_replace('_', ' ', $meta['new_status'])) : 'N/A';
                    $badges[] = [
                        'class' => 'crm-badge-info',
                        'text'  => $old . ' → ' . $new,
                        'icon'  => 'ti ti-refresh',
                    ];
                }
                break;

            case 'assigned':
                if (array_key_exists('new_assigned_to', $meta)) {
                    $badges[] = [
                        'class' => 'crm-badge-warning',
                        'text'  => !empty($meta['new_assigned_to']) ? 'Assigned' : 'Unassigned',
                        'icon'  => 'ti ti-user-check',
                    ];
                }
                break;

            case 'verified':
                $badges[] = [
                    'class' => 'crm-badge-success',
                    'text'  => 'Verified',
                    'icon'  => 'ti ti-circle-check',
                ];
                break;

            case 'unverified':
                $badges[] = [
                    'class' => 'crm-badge-gray',
                    'text'  => 'Unverified',
                    'icon'  => 'ti ti-shield-x',
                ];
                break;

            case 'forecast_updated':
                if (!empty($meta['new_forecast_category'])) {
                    $badges[] = [
                        'class' => 'crm-badge-purple',
                        'text'  => ucwords(str_replace('_', ' ', $meta['new_forecast_category'])),
                        'icon'  => 'ti ti-chart-line',
                    ];
                }
                if (isset($meta['new_forecast_probability']) && $meta['new_forecast_probability'] !== null && $meta['new_forecast_probability'] !== '') {
                    $badges[] = [
                        'class' => 'crm-badge-info',
                        'text'  => (int)$meta['new_forecast_probability'] . '% Probability',
                        'icon'  => 'ti ti-percentage',
                    ];
                }
                break;

            case 'imported':
                if (isset($meta['inserted'])) {
                    $badges[] = [
                        'class' => 'crm-badge-success',
                        'text'  => 'Inserted: ' . (int)$meta['inserted'],
                        'icon'  => 'ti ti-plus',
                    ];
                }
                if (isset($meta['updated'])) {
                    $badges[] = [
                        'class' => 'crm-badge-info',
                        'text'  => 'Updated: ' . (int)$meta['updated'],
                        'icon'  => 'ti ti-edit',
                    ];
                }
                if (isset($meta['skipped'])) {
                    $badges[] = [
                        'class' => 'crm-badge-gray',
                        'text'  => 'Skipped: ' . (int)$meta['skipped'],
                        'icon'  => 'ti ti-player-skip-forward',
                    ];
                }
                break;
        }

        return $badges;
    }
}

if (!function_exists('crm_activity_user_text')) {
    function crm_activity_user_text(array $activity)
    {
        $action = (string)($activity['action'] ?? '');

        switch ($action) {
            case 'assigned':
                return 'Updated by';
            case 'status_changed':
                return 'Changed by';
            case 'verified':
                return 'Verified by';
            case 'unverified':
                return 'Updated by';
            case 'forecast_updated':
                return 'Updated by';
            case 'imported':
                return 'Imported by';
            default:
                return 'Logged by';
        }
    }
}

if (!function_exists('proposal_statuses')) {
    function proposal_statuses()
    {
        return [
            'draft' => [
                'label'      => 'Draft',
                'icon'       => 'ti-pencil',
                'css'        => 'prp-status-draft',
                'icon_bg'    => '#f1f5f9',
                'icon_color' => '#475569',
            ],
            'pending_review' => [
                'label'      => 'Pending Review',
                'icon'       => 'ti-clock',
                'css'        => 'prp-status-pending_review',
                'icon_bg'    => '#fef3c7',
                'icon_color' => '#d97706',
            ],
            'sent' => [
                'label'      => 'Sent',
                'icon'       => 'ti-send',
                'css'        => 'prp-status-sent',
                'icon_bg'    => '#dbeafe',
                'icon_color' => '#1d4ed8',
            ],
            'viewed' => [
                'label'      => 'Viewed',
                'icon'       => 'ti-eye',
                'css'        => 'prp-status-viewed',
                'icon_bg'    => '#ede9fe',
                'icon_color' => '#7c3aed',
            ],
            'approved' => [
                'label'      => 'Approved',
                'icon'       => 'ti-circle-check',
                'css'        => 'prp-status-approved',
                'icon_bg'    => '#d1fae5',
                'icon_color' => '#059669',
            ],
            'declined' => [
                'label'      => 'Declined',
                'icon'       => 'ti-x',
                'css'        => 'prp-status-declined',
                'icon_bg'    => '#fee2e2',
                'icon_color' => '#dc2626',
            ],
            'expired' => [
                'label'      => 'Expired',
                'icon'       => 'ti-clock-cancel',
                'css'        => 'prp-status-expired',
                'icon_bg'    => '#fff7ed',
                'icon_color' => '#c2410c',
            ],
            'cancelled' => [
                'label'      => 'Cancelled',
                'icon'       => 'ti-ban',
                'css'        => 'prp-status-cancelled',
                'icon_bg'    => '#f1f5f9',
                'icon_color' => '#64748b',
            ],
        ];
    }
}

if (!function_exists('proposal_status')) {
    function proposal_status($status)
    {
        $statuses = proposal_statuses();
        return $statuses[$status] ?? null;
    }
}

if (!function_exists('proposal_status_badge')) {
    function proposal_status_badge($status)
    {
        $s = proposal_status($status);

        if (!$s) return '';

        return '<span class="prp-badge '.$s['css'].'">
                    <i class="ti '.$s['icon'].'"></i> '.$s['label'].'
                </span>';
    }
}

if (!function_exists('forecast_categories')) {
    function forecast_categories()
    {
        return [
            'commit' => [
                'label' => 'Commit',
                'css'   => 'prp-forecast-commit'
            ],
            'best_case' => [
                'label' => 'Best Case',
                'css'   => 'prp-forecast-best_case'
            ],
            'pipeline' => [
                'label' => 'Pipeline',
                'css'   => 'prp-forecast-pipeline'
            ],
            'omitted' => [
                'label' => 'Omitted',
                'css'   => 'prp-forecast-omitted'
            ],
        ];
    }
}


if (!function_exists('forecast_category')) {
    function forecast_category($category)
    {
        $cats = forecast_categories();
        return $cats[$category] ?? null;
    }
}

if (!function_exists('forecast_badge')) {
    function forecast_badge($category)
    {
        $c = forecast_category($category);

        if (!$c) return '';

        return '<span class="prp-badge '.$c['css'].'">
                    '.$c['label'].'
                </span>';
    }
}

function crm_date(?string $d, bool $time = false): string {
    if (empty($d) || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') return '—';
    return $time ? date('M j, Y · g:i A', strtotime($d)) : date('M j, Y', strtotime($d));
}


if (!function_exists('proposal_billing_cycles')) {
    function proposal_billing_cycles()
    {
        return [
            ''          => '— Select —',
            'weekly'    => 'Weekly',
            'bi-weekly' => 'Bi-Weekly',
            'monthly'   => 'Monthly',
            'quarterly' => 'Quarterly',
            'annual'    => 'Annual',
            'custom'    => 'Custom',
        ];
    }
}


if (!function_exists('proposal_discount_types')) {
    function proposal_discount_types()
    {
        return [
            'none'    => 'None',
            'percent' => 'Percent (%)',
            'fixed'   => 'Fixed ($)',
        ];
    }
}

if (!function_exists('proposal_item_types')) {
    function proposal_item_types()
    {
        return [
            'service'   => 'Service',
            'setup_fee' => 'Setup Fee',
            'addon'     => 'Addon',
            'other'     => 'Other',
        ];
    }
}




/**
 * crm_contracts_helper.php
 * Add this to application/helpers/ or load it inside the CRM module helper.
 */

if (!function_exists('contract_statuses')) {
    function contract_statuses(): array
    {
        return [
            'draft'             => ['label' => 'Draft',             'icon' => 'ti-pencil',       'css' => 'contract-status-draft'],
            'pending_signature' => ['label' => 'Pending Signature', 'icon' => 'ti-clock',        'css' => 'contract-status-pending'],
            'active'            => ['label' => 'Active',            'icon' => 'ti-circle-check', 'css' => 'contract-status-active'],
            'expired'           => ['label' => 'Expired',           'icon' => 'ti-clock-cancel', 'css' => 'contract-status-expired'],
            'terminated'        => ['label' => 'Terminated',        'icon' => 'ti-ban',          'css' => 'contract-status-terminated'],
            'cancelled'         => ['label' => 'Cancelled',         'icon' => 'ti-x',            'css' => 'contract-status-cancelled'],
        ];
    }
}

if (!function_exists('contract_status')) {
    function contract_status(string $status): ?array
    {
        $statuses = contract_statuses();
        return $statuses[$status] ?? null;
    }
}

if (!function_exists('contract_status_badge')) {
    function contract_status_badge(string $status): string
    {
        $s = contract_status($status);
        if (!$s) {
            return '<span class="badge bg-secondary">' . html_escape($status) . '</span>';
        }
        return '<span class="prp-badge ' . $s['css'] . '">'
             . '<i class="ti ' . $s['icon'] . '"></i> '
             . $s['label']
             . '</span>';
    }
}

if (!function_exists('crm_lead_status_score')) {
    function crm_lead_status_score(?string $status): int
    {
        $scores = [
            'new'             => 10,
            'contacted'       => 20,
            'qualified'       => 40,
            'demo_scheduled'  => 50,
            'demo_completed'  => 60,
            'proposal_sent'   => 70,
            'negotiation'     => 80,
            'contract_sent'   => 90,
            'contract_signed' => 100,
            'lost'            => 0,
            'disqualified'    => 0,
        ];

        return $scores[strtolower((string)$status)] ?? 0;
    }
}

if (!function_exists('crm_forecast_category_score')) {
    function crm_forecast_category_score(?string $category): int
    {
        $scores = [
            'commit'    => 40,
            'best_case' => 30,
            'pipeline'  => 20,
            'omitted'   => 0,
        ];

        return $scores[strtolower((string)$category)] ?? 0;
    }
}

if (!function_exists('crm_forecast_probability_score')) {
    function crm_forecast_probability_score($probability): int
    {
        $p = (int)$probability;

        if ($p >= 90) return 40;
        if ($p >= 75) return 35;
        if ($p >= 50) return 25;
        if ($p >= 25) return 15;
        if ($p > 0)   return 5;

        return 0;
    }
}

if (!function_exists('crm_calculate_lead_score')) {
    function crm_calculate_lead_score(array $lead): int
    {
        $status_score   = crm_lead_status_score($lead['status'] ?? null);
        $forecast_score = crm_forecast_category_score($lead['forecast_category'] ?? null);
        $prob_score     = crm_forecast_probability_score($lead['forecast_probability'] ?? 0);

        $score = $status_score + $forecast_score + $prob_score;

        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }
}

function get_crm_setting($key, $default = null)
{
    $CI =& get_instance();
    $CI->load->model('Crmsettings_model');

    static $cache = null;

    if ($cache === null) {
        $cache = $CI->Crmsettings_model->get_all();
    }

    return $cache[$key] ?? $default;
}


if (!function_exists('crm_settings')) {
    /**
     * Get all CRM settings (cached per request)
     */
    function crm_settings()
    {
        $CI =& get_instance();

        static $cache = null;

        if ($cache === null) {
            $CI->load->model('Crmsettings_model');
            $cache = $CI->Crmsettings_model->get_all();
        }

        return $cache;
    }
}

if (!function_exists('crm_setting')) {
    /**
     * Get single CRM setting
     */
    function crm_setting($key, $default = null)
    {
        $settings = crm_settings();
        return $settings[$key] ?? $default;
    }
}

function crm_form_value($field, $record = [], $setting_key = null, $default = null)
{
    // 1. Form submitted value
    $val = set_value($field);
    if (!empty($val)) {
        return $val;
    }

    // 2. Existing record
    if (!empty($record[$field])) {
        return $record[$field];
    }

    // 3. CRM setting
    if ($setting_key) {
        return crm_setting($setting_key, $default);
    }

    return $default;
}

if (!function_exists('crm_is_lead_stale')) {
    function crm_is_lead_stale($last_contact_date)
    {
        if (empty($last_contact_date)) {
            return false; // no contact = don't mark stale (or change later if needed)
        }

        $days_limit = (int) crm_setting('crm_flag_stale_lead_days', 14);

        if ($days_limit < 1) {
            $days_limit = 14;
        }

        $last = strtotime($last_contact_date);
        $now  = time();

        $diff_days = floor(($now - $last) / (60 * 60 * 24));

        return $diff_days >= $days_limit;
    }
}

if (!function_exists('crm_lead_stale_days')) {
    function crm_lead_stale_days($last_contact_date)
    {
        if (empty($last_contact_date)) return 0;

        return floor((time() - strtotime($last_contact_date)) / 86400);
    }
}


if (!function_exists('crm_currencies')) {
    function crm_currencies(): array
    {
        return [
            'USD' => [
                'name'     => 'US Dollar',
                'symbol'   => '$',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'PKR' => [
                'name'     => 'Pakistani Rupee',
                'symbol'   => 'Rs',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'EUR' => [
                'name'     => 'Euro',
                'symbol'   => '€',
                'decimals' => 2,
                'thousand' => '.',
                'decimal'  => ',',
            ],
            'GBP' => [
                'name'     => 'British Pound',
                'symbol'   => '£',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'JPY' => [
                'name'     => 'Japanese Yen',
                'symbol'   => '¥',
                'decimals' => 0,
                'thousand' => ',',
                'decimal'  => '',
            ],
            'CAD' => [
                'name'     => 'Canadian Dollar',
                'symbol'   => 'C$',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'AUD' => [
                'name'     => 'Australian Dollar',
                'symbol'   => 'A$',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'INR' => [
                'name'     => 'Indian Rupee',
                'symbol'   => '₹',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
            'CNY' => [
                'name'     => 'Chinese Yuan',
                'symbol'   => '¥',
                'decimals' => 2,
                'thousand' => ',',
                'decimal'  => '.',
            ],
        ];
    }
}

if (!function_exists('crm_currency_dropdown')) {
    function crm_currency_dropdown(string $selected = 'USD'): array
    {
        $list = [];

        foreach (crm_currencies() as $code => $c) {
            $list[$code] = sprintf(
                '%s (%s)',
                $c['name'],
                $c['symbol']
            );
        }

        return $list;
    }
}

// Status badge helper
function group_status_badge(string $status): string {
    return match($status) {
        'active'    => '<span class="badge badge-active"><span class="badge-dot-green"></span> Active</span>',
        'inactive'  => '<span class="badge badge-inactive">Inactive</span>',
        'suspended' => '<span class="badge badge-hold">Suspended</span>',
        'churned'   => '<span class="badge badge-danger">Churned</span>',
        default     => '<span class="badge badge-inactive">' . ucfirst($status) . '</span>',
    };
}