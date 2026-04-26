<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('time_ago')) {
    function time_ago($date)
    {
        $CI = &get_instance();

        $localization = [];

        foreach (['time_ago_just_now', 'time_ago_minute', 'time_ago_minutes', 'time_ago_hour', 'time_ago_hours', 'time_ago_yesterday', 'time_ago_days', 'time_ago_week', 'time_ago_weeks', 'time_ago_month', 'time_ago_months', 'time_ago_year', 'time_ago_years'] as $langKey) {
            if (isset($CI->lang->language[$langKey])) {
                $localization[$langKey] = $CI->lang->language[$langKey];
            }
        }

        return \app\services\utilities\Date::timeAgoString($date, $localization);
    }
}

if ( ! function_exists('get_date_format'))
{
    function get_date_format()
    {
        return get_company_field('date_format');
    }
}


if (!function_exists('format_date')) {
    function format_date($value): string
    {
        $fmt = get_setting('date_format', 'Y-m-d');

        if (is_numeric($value)) {
            return date($fmt, (int)$value);
        }

        $ts = strtotime($value);
        return $ts ? date($fmt, $ts) : '-';
    }
}

if (!function_exists('format_time')) {
    function format_time($value): string
    {
        $fmt = get_setting('time_format', 'H:i');

        if (is_numeric($value)) {
            return date($fmt, (int)$value);
        }

        $ts = strtotime($value);
        return $ts ? date($fmt, $ts) : '';
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($value): string
    {
        return trim(format_date($value) . ' ' . format_time($value));
    }
}

if ( ! function_exists('get_time_format'))
{
    function get_time_format()
    {
        return get_company_field('time_format');
    }
}

if ( ! function_exists('get_default_timezone'))
{
    function get_default_timezone()
    {
        return get_company_field('default_timezone');
    }
}

if (!function_exists('_dt_sep')) {
    function _dt_sep($datetime, string $sep = ' - '): string
    {
        if (empty($datetime)) return '';
        $date = _d($datetime);
        $time = date((get_time_format() === '24' ? 'H:i' : 'h:i A'), strtotime($datetime));
        return $date . $sep . $time;
    }
}

if (!function_exists('get_public_holidays')) {
    function get_public_holidays($countryCode, $year)
    {
        $api_url = "https://date.nager.at/api/v3/PublicHolidays/$year/$countryCode";
        $response = @file_get_contents($api_url);
        $holidays = [];
        if ($response !== false) {
            $data = json_decode($response, true);
            if (is_array($data) && count($data)) {
                foreach ($data as $h) {
                    $holidays[$h['date']] = $h['localName'];
                }
                return $holidays;
            }
        }
        if (strtoupper($countryCode) == 'PK') {
            $holidays["$year-03-23"] = "Pakistan Day";
            $holidays["$year-08-14"] = "Independence Day";
            $holidays["$year-12-25"] = "Quaid-e-Azam Day";
        }
        return $holidays;
    }

}

if (!function_exists('render_holidays_scope_list')) {
    function render_holidays_scope_list($ids, array $source, string $nameKey, int $limit = 3)
    {
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $ids = $decoded;
            }
        }

        if (empty($ids) || !is_array($ids)) {
            return 'All';
        }

        $map = [];
        foreach ($source as $row) {
            if (isset($row['id'], $row[$nameKey])) {
                $map[(int)$row['id']] = $row[$nameKey];
            }
        }

        $names = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if (isset($map[$id])) {
                $names[] = $map[$id];
            }
        }

        if (empty($names)) {
            return 'All';
        }

        $shown = array_slice($names, 0, $limit);
        $extra = count($names) - count($shown);

        $output = implode(', ', $shown);

        if ($extra > 0) {
            $output .= ' <span class="text-muted">(+'.$extra.')</span>';
        }

        return $output;
    }
}

if (!function_exists('calculate_leave_days')) {
    function calculate_leave_days($start_date, $end_date)
    {
        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);
        return $start->diff($end)->days + 1;
    }
}

function get_user_public_holidays($user_id, $start, $end)
{
    $CI =& get_instance();
    $CI->load->database();
    $user = $CI->db
        ->select('id, office_id, emp_department, emp_title')
        ->from('users')
        ->where('id', $user_id)
        ->where('is_active', 1)
        ->get()
        ->row_array();

    if (!$user) {
        return [];
    }

    $CI->db->from('public_holidays');
    $CI->db->where('deleted_at IS NULL', null, false);
    $CI->db->group_start()
        ->where('from_date <=', $end)
        ->where('to_date >=', $start)
        ->group_end();

    $holidays = $CI->db->get()->result_array();

    if (empty($holidays)) {
        return [];
    }

    $events = [];

    foreach ($holidays as $h) {

        if (!holiday_applies_to_user($h, $user)) {
            continue;
        }

        $events[] = [
            'id'    => 'ph_' . $h['id'],
            'title' => $h['name'],
            'start' => $h['from_date'],
            'end'   => date('Y-m-d', strtotime($h['to_date'] . ' +1 day')),
            'allDay' => true,
            'classNames' => ['event-public-holiday'],
            'extendedProps' => [
                'type' => 'holiday',
                'editable' => false,
                'is_public_holiday' => true,
                'category' => $h['category']
            ]
        ];
    }

    return $events;
}

function holiday_applies_to_user(array $holiday, array $user): bool
{
    $match = function ($field, $value) {
        if (empty($field)) return true;

        $ids = is_array($field)
            ? $field
            : preg_split('/\s*,\s*/', trim($field));

        return in_array($value, $ids);
    };

    return
        $match($holiday['locations'],   $user['office_id']) &&
        $match($holiday['departments'], $user['emp_department']) &&
        $match($holiday['positions'],   $user['emp_title']) &&
        $match($holiday['employees'],   $user['id']);
}