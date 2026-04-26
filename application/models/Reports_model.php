<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reports_model extends CI_Model
{
    /**
     * Resolve a report from core or modules
     */
    public function resolve_report(string $group, string $report)
    {
        // 1️⃣ Let modules handle their own reports
        if (function_exists('hooks')) {
            $result = hooks()->apply_filters(
                'resolve_report',
                null,
                $group,
                $report
            );

            if (is_array($result)) {
                return $result;
            }
        }

        // 2️⃣ Core reports fallback
        return $this->resolve_core_report($group, $report);
    }

    /**
     * Core reports resolver
     */
    private function resolve_core_report(string $group, string $report)
    {
        if ($group === 'users' && $report === 'all') {
            return [
                'title'       => 'All Users Report',
                'columns'     => ['ID', 'EMP ID', 'Full Name', 'Email Address', 'Status', 'Phone'],
                'rows'        => $this->get_all_users(),
                'exportable'  => true,
                'printable'   => true,
            ];
        }

        return null;
    }

    private function get_all_users()
    {
        return $this->db
            ->select('id, emp_id, fullname, email, is_active, emp_phone')
            ->from('users')
            ->get()
            ->result_array();
    }
}
