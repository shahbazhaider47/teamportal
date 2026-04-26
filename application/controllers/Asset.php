<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Asset extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Asset_model');
        $this->load->model('User_model');
        $this->load->model('Department_model'); // departments
        $this->load->library(['form_validation', 'upload']);
    }

    /**
     * Assigned Assets Page
     * Shows all assets currently "in-use"
     */
    public function index()
    {

        if (!staff_can('view', 'assets')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $view_data = [
            'assets' => $this->Asset_model->get_assigned_assets(), // only assigned assets
            'users'       => $this->User_model->get_all_users(false),    // only active users
            'asset_types' => $this->Asset_model->get_asset_types(),
            'available'   => $this->Asset_model->get_available_inventory(),
            'departments' => $this->Department_model->get_all_departments(),
        ];

        $layout_data = [
            'page_title' => 'Assigned Assets',
            'subview'    => 'asset/manage',
            'view_data'  => $view_data
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Inventory Page
     * Shows unassigned/available assets
     */
    public function inventory()
    {

        if (!staff_can('view', 'assets')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
        
        $view_data = [
            'assets'      => $this->Asset_model->get_unassigned_assets(), // all except in-use
            'users'       => $this->User_model->get_all_users(false),
            'asset_types' => $this->Asset_model->get_asset_types(),
        ];
    
        $layout_data = [
            'page_title' => 'Inventory List',
            'subview'    => 'asset/inventory',
            'view_data'  => $view_data
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }
    
    
    
    public function add_to_inventory($purchase_id)
    {
        $purchase = $this->Asset_model->get_purchase((int)$purchase_id);
    
        if ($purchase && $purchase['purchase_status'] === 'Purchased') {
            $data = [
                'serial_no'      => $this->Asset_model->generate_serial_no(),
                'name'           => $purchase['purchase_title'],
                'type_id'        => $purchase['asset_type_id'],
                'price'          => $purchase['cost_per_item'],
                'status'         => 'available',
                'purchase_date'  => $purchase['date_required'],
                'description'    => $purchase['description'],
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s')
            ];
    
            $this->Asset_model->insert_asset($data);
    
            echo json_encode([
                'success' => true,
                'data'    => $purchase
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }


    /**
     * New Purchases Page
     * Shows purchase requests and history
     */
    public function new_purchases()
    {

        if (!staff_can('view', 'assets')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
        
        $view_data = [
            'purchases'   => $this->Asset_model->get_all_purchases(),
            'users'       => $this->User_model->get_all_users(false),
            'asset_types' => $this->Asset_model->get_asset_types(),
        ];

        $layout_data = [
            'page_title' => 'New Purchases',
            'subview'    => 'asset/new_purchases',
            'view_data'  => $view_data
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Add or update an asset
     * Handles file upload, serial number generation
     */
    public function save()
    {
        $id = $this->input->post('id');

        $data = [
            'serial_no'      => $this->input->post('serial_no', true),
            'name'           => $this->input->post('name', true),
            'status'         => $this->input->post('status', true),
            'type_id'        => $this->input->post('type_id', true),
            'price'          => $this->input->post('price', true),
            'purchase_date'  => $this->input->post('purchase_date', true),
            'guarantee_date' => $this->input->post('guarantee_date', true),
            'description'    => $this->input->post('description', true),
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        // Image upload
        if (!empty($_FILES['image']['name'])) {
            $upload_path = './uploads/asset/images/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            $config['upload_path']   = $upload_path;
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size']      = 2048;
            $this->upload->initialize($config);

            if ($this->upload->do_upload('image')) {
                $data['image'] = $this->upload->data('file_name');
            }
        }

        if ($id) {
            // Update existing
            $this->Asset_model->update_asset($id, $data);
            set_alert('success', 'Inventory updated successfully.');
        } else {
            // Auto generate serial_no if empty
            if (empty($data['serial_no'])) {
                $data['serial_no'] = $this->Asset_model->generate_serial_no();
            }
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->Asset_model->insert_asset($data);
            set_alert('success', 'New inventory added successfully.');
        }

        redirect('asset/inventory');
    }

    /**
     * Assign an asset to user or department
     */
    public function assign()
    {
        $asset_id    = $this->input->post('asset_id');
        $assign_type = $this->input->post('assign_type');
        $assign_id   = $this->input->post('assign_id');

        if ($asset_id && $assign_type && $assign_id) {
            $this->Asset_model->assign_asset($asset_id, $assign_type, $assign_id);
            set_alert('success', 'Asset assigned successfully.');
        } else {
            set_alert('danger', 'Failed to assign asset. Missing fields.');
        }

        redirect('asset');
    }

/**
 * Reassign an asset to user/department
 */
public function reassign()
{
    $asset_id    = $this->input->post('asset_id');
    $assign_type = $this->input->post('assign_type');
    $assign_id   = $this->input->post('assign_id');

    if ($asset_id && $assign_type && $assign_id) {
        $this->Asset_model->assign_asset($asset_id, $assign_type, $assign_id);
        set_alert('success', 'Asset re-assigned successfully.');
    } else {
        set_alert('danger', 'Failed to re-assign asset. Missing fields.');
    }

    redirect('asset');
}

/**
 * Quick inline status update via dropdown
 * Also unassigns the asset when status is changed
 */
public function update_status($id)
{
    $status = $this->input->post('status');
    if ($id && $status) {
        // Always clear assignment when status changes
        $update = [
            'status'        => $status,
            'employee_id'   => null,
            'department_id' => null,
            'updated_at'    => date('Y-m-d H:i:s')
        ];

        $this->Asset_model->update_asset($id, $update);

        $asset = $this->Asset_model->get_asset($id);
        echo json_encode([
            'success'        => true,
            'id'             => $id,
            'serial_no'      => $asset['serial_no'],
            'new_status'     => $asset['status'],
            'assigned_to'    => null, // because cleared
            'department_name'=> null
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}

    /**
     * Delete asset record
     */
    public function delete($id)
    {
        $this->Asset_model->delete_asset($id);
        set_alert('success', 'Asset deleted.');
        redirect('asset/inventory');
    }

    /**
     * Get a single asset (AJAX)
     */
    public function get($id)
    {
        $asset = $this->Asset_model->get_asset($id);
        echo json_encode($asset);
    }

    /**
     * Generate next serial number (AJAX)
     */
    public function get_next_serial()
    {
        $serial = $this->Asset_model->generate_serial_no();
        echo json_encode(['serial_no' => $serial]);
    }

    /**
     * Add new asset type (AJAX)
     */
    public function add_type()
    {
        if ($this->input->post('name')) {
            $type_id = $this->Asset_model->add_asset_type($this->input->post('name', true));
            echo json_encode(['success' => true, 'id' => $type_id]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    /**
     * AJAX: Get active users
     */
    public function get_users()
    {
        $users = $this->User_model->get_all_users(false); // only active
        echo json_encode($users);
    }

    /**
     * AJAX: Get all departments
     */
    public function get_departments()
    {
        $departments = $this->Department_model->get_all_departments();
        echo json_encode($departments);
    }

    // ───────────────────────── Purchases ───────────────────────── //

    /**
     * Save (Add or Update) a purchase request
     */
    public function save_purchase()
    {
        $id   = $this->input->post('id');
        $user = (int)$this->session->userdata('user_id');

        $data = [
            'purchase_title'    => $this->input->post('purchase_title', true),
            'description'       => $this->input->post('description', true),
            'asset_type_id'     => $this->input->post('asset_type_id', true),
            'required_quantity' => (int)$this->input->post('required_quantity', true),
            'date_required'     => $this->input->post('date_required', true),
            'cost_per_item'     => $this->input->post('cost_per_item', true),
            'total_amount'      => $this->input->post('cost_per_item', true) * (int)$this->input->post('required_quantity', true),
            'purchase_status'   => $this->input->post('purchase_status', true),
        ];

        if ($data['purchase_status'] === 'Purchased') {
            $data['purchase_source'] = $this->input->post('purchase_source', true);
            $data['purchased_by']    = $this->input->post('purchased_by', true) ?: null;
            $data['payment_user']    = $this->input->post('payment_user', true) ?: null;
            $data['payment_method']  = $this->input->post('payment_method', true);
        } else {
            $data['purchase_source'] = null;
            $data['purchased_by']    = null;
            $data['payment_user']    = null;
            $data['payment_method']  = null;
        }

        if ($id) {
            $this->Asset_model->update_purchase($id, $data);
            set_alert('success', 'Purchase request updated successfully.');
        } else {
            $data['created_by'] = $user;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->Asset_model->insert_purchase($data);
            set_alert('success', 'New purchase request added successfully.');
        }

        redirect('asset/new_purchases');
    }

    /**
     * Delete a purchase record
     */
    public function delete_purchase($id)
    {
        if ($this->Asset_model->delete_purchase($id)) {
            set_alert('success', 'Purchase request deleted successfully.');
        } else {
            set_alert('danger', 'Failed to delete purchase request.');
        }
        redirect('asset/new_purchases');
    }

    /**
     * Quick update of purchase status
     */
    public function update_purchase_status($id, $status)
    {
        $data = ['purchase_status' => $status];

        if ($status !== 'Purchased') {
            $data['purchase_source'] = null;
            $data['purchased_by']    = null;
            $data['payment_user']    = null;
            $data['payment_method']  = null;
        }

        $this->Asset_model->update_purchase($id, $data);
        set_alert('success', 'Purchase status updated to: ' . ucfirst($status));
        redirect('asset/new_purchases');
    }
}
