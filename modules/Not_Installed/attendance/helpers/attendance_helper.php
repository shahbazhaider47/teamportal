<?php
defined('BASEPATH') or exit('No direct script access allowed');



if (!function_exists('calculate_leave_days')) {
    function calculate_leave_days($start_date, $end_date)
    {
        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);
        return $start->diff($end)->days + 1; // +1 to include the start day
    }
}

if (!function_exists('get_leave_status_badge')) {
    function get_leave_status_badge($status)
    {
        switch (strtolower($status)) {
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'pending':  return 'warning';
            default:         return 'secondary';
        }
    }
}


function get_leave_status_badge($status)
{
    switch (strtolower($status)) {
        case 'approved': return 'success';
        case 'pending': return 'warning';
        case 'rejected': return 'danger';
        case 'hold': return 'secondary';
        default: return 'light';
    }
}
