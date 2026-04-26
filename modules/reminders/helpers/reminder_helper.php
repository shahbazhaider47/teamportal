<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('generate_recurring_dates')) {
    function generate_recurring_dates($start_date, $frequency, $count)
    {
        $dates = [];
        $base = new DateTime($start_date);
        for ($i = 1; $i < $count; $i++) {
            $next = clone $base;
            switch ($frequency) {
                case 'daily': $next->modify("+{$i} days"); break;
                case 'weekly': $next->modify("+{$i} weeks"); break;
                case 'monthly': $next->modify("+{$i} months"); break;
                case 'yearly': $next->modify("+{$i} years"); break;
            }
            $dates[] = $next->format('Y-m-d H:i:s');
        }
        return $dates;
    }
}

