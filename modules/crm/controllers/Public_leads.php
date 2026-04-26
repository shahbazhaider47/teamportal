<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Public_leads extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->load->helper(['url', 'form', 'crm']);
        $this->load->library(['form_validation']);
        $this->load->model('crm/Crmleads_model', 'crmleads');
    }

    public function form()
    {
        $this->load->view('crm/public/lead_form');
    }

    public function submit()
    {
        if (!$this->input->post()) {
            show_404();
        }
    
        $leadId = $this->crmleads->insert_from_public_form(
            $this->input->post(NULL, true)
        );
    
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => $leadId > 0 ? 'success' : 'error',
                'message' => $leadId > 0
                    ? 'Thank you! Your request has been submitted.'
                    : 'Something went wrong. Please try again.'
            ]));
    }

    protected function _collect_lead_data_from_post(): array
    {
        $fields = $this->crmleads->allowed_columns();
        $data = [];

        foreach ($fields as $field) {
            $value = $this->input->post($field, true);

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$field] = ($value === '') ? null : $value;
        }

        return $data;
    }
}