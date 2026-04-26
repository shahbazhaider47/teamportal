<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Policies extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Enforce login
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    }

    /**
     * Display Company Policy
     */
    public function company_policy()
    {
        // Fetch all company policies
        $this->db->from('hrm_documents');
        $this->db->where('doc_scope', 'company');
        $this->db->where('doc_type', 'Company Policy');
        $this->db->order_by('created_at', 'DESC');
        $policies = $this->db->get()->result_array();

        // Determine active policy
        $active_policy_id = (int) $this->input->get('id');
        if (!$active_policy_id && !empty($policies)) {
            $active_policy_id = (int) $policies[0]['id'];
        }

        $active_policy = null;
        foreach ($policies as $policy) {
            if ((int)$policy['id'] === $active_policy_id) {
                $active_policy = $policy;
                break;
            }
        }

        // Layout payload
        $layout_data = [
            'page_title' => 'Company Policy',
            'subview'    => 'policies/company_policy',
            'view_data'  => [
                'policies'        => $policies,
                'active_policy'   => $active_policy,
                'active_policy_id'=> $active_policy_id,
            ],
        ];

        $this->load->view('layouts/master', $layout_data);
    }
}
