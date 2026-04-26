<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CompanySettingsLoader
 *
 * This Hook runs before any controller is called. It:
 * 1) Loads the single row from company_info
 * 2) Applies date_default_timezone_set()
 * 3) Pushes date_format and time_format into CI’s config
 */
class CompanySettingsLoader
{
    /**
     * This method is invoked at the "pre_controller" hook point.
     * It:
     *   - Retrieves date_format, time_format, default_timezone
     *   - Calls date_default_timezone_set()
     *   - Sets CI config items so helpers/views can reference them
     */
    public function initialize()
    {
        // Get CodeIgniter instance
        $CI =& get_instance();

        // Load the Company_info_model (pulls from company_info table)
        $CI->load->model('Company_info_model');

        // Fetch the single row (since your table has exactly one record of company_info)
        $company = $CI->Company_info_model->get_all_values();

        // 1) Apply the stored default timezone (if any)
        if (! empty($company['default_timezone'])) {
            @date_default_timezone_set($company['default_timezone']);
        }
        // 2) Push the stored date_format into CI config (or fallback to 'Y-m-d')
        $fmt_date = ! empty($company['date_format']) ? $company['date_format'] : 'Y-m-d';
        $CI->config->set_item('date_format', $fmt_date);

        // 3) Push the stored time_format into CI config (or fallback to ’24’)
        //     We store '24' or '12' to indicate the display style.
        $fmt_time = ! empty($company['time_format']) ? $company['time_format'] : '24';
        $CI->config->set_item('time_format', $fmt_time);
    }
}
