<?php defined('BASEPATH') or exit('No direct script access allowed');

class Biometric extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attendance/Biometric_model', 'bio');
        $this->load->model('User_model','users'); // for mapping dropdowns
        $this->load->helper(['url','form']);
    }

    /* ---------------------------
     * Dashboard / Devices
     * --------------------------- */
    public function index()
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $devices = $this->bio->list_devices();
        if (!is_array($devices)) { $devices = []; }
        
        $layout_data = [
            'page_title' => 'Biometric Devices',
            'subview'    => 'attendance/biometric/manage',
            'view_data'  => ['devices' => $devices],
        ];
        $this->load->view('layouts/master', $layout_data);

    }

    public function device_form($id = null)
    {
        
        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('edit'); // or specific permission key
        if ($this->input->method() === 'post') {
            $data = $this->input->post(null, true);
            $id   = $this->bio->upsert_device($id ? (int)$id : null, $data);
            set_alert('success','Device saved.');
            redirect('attendance/biometric');
            return;
        }
        $device = $id ? $this->bio->get_device((int)$id) : null;
        $layout_data = [
            'page_title' => $id ? 'Edit Device' : 'Add Device',
            'subview'    => 'attendance/biometric/device_form',
            'view_data'  => compact('device'),
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function delete_device($id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('delete');
        $this->bio->delete_device((int)$id);
        set_alert('success','Device removed.');
        redirect('attendance/biometric');
    }

    /* ---------------------------
     * Mapping
     * --------------------------- */
    public function map_users($device_id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('edit');
        $device_id = (int)$device_id;
        $mappings  = $this->bio->list_mappings($device_id);
        $users     = $this->users->get_all(); // implement to return [id, fullname, emp_id,...]
        $layout_data = [
            'page_title' => 'Map Users to Device',
            'subview'    => 'attendance/biometric/map_users',
            'view_data'  => compact('device_id','mappings','users'),
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function upsert_mapping($device_id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('edit');
        $device_id = (int)$device_id;
        $device_user_id = (string)$this->input->post('device_user_id', true);
        $user_id        = (int)$this->input->post('user_id', true);
        $user_code      = (string)$this->input->post('user_code', true);
        $this->bio->upsert_mapping($device_id, $device_user_id, $user_id, $user_code ?: null);
        set_alert('success','Mapping saved.');
        redirect("attendance/biometric/map_users/{$device_id}");
    }

    public function delete_mapping($id, $device_id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('edit');
        $this->bio->delete_mapping((int)$id);
        set_alert('success','Mapping removed.');
        redirect("attendance/biometric/map_users/".(int)$device_id);
    }

    /* ---------------------------
     * Manual Fetch
     * --------------------------- */
    public function logs($device_id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard_view();
        $device = $this->bio->get_device((int)$device_id);
        $layout_data = [
            'page_title' => 'Biometric Logs',
            'subview'    => 'attendance/biometric/logs',
            'view_data'  => compact('device'),
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function fetch_now($device_id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('create');
        $device = $this->bio->get_device((int)$device_id);
        if (!$device) { set_alert('danger','Device not found.'); redirect('attendance/biometric'); return; }

        // Window
        $from = $this->input->post('from') ?: null;
        $to   = $this->input->post('to')   ?: null;

        // Create driver
        $driver = $this->make_driver($device);
        $fromDT = $from ? new DateTime($from.' 00:00:00') : null;
        $toDT   = $to   ? new DateTime($to.' 23:59:59')   : null;

        $job_id = $this->bio->start_job($device['id'], (int)$this->session->userdata('user_id'), $from, $to);

        try {
            $driver->connect();
            $records = $driver->getAttendance($fromDT, $toDT); // wire SDK
            $dupWin  = (int)get_setting('biometric_duplicate_window_seconds', 60);
            $stats   = $this->bio->stage_logs($device['id'], $records, $dupWin);
            $this->bio->finish_job($job_id, 'success', $stats['total'], $stats['inserted'], $stats['skipped'], null);
            set_alert('success', "Fetched {$stats['inserted']} new logs ({$stats['skipped']} skipped).");
        } catch (Exception $e) {
            $this->bio->finish_job($job_id, 'failed', 0, 0, 0, $e->getMessage());
            set_alert('danger', 'Fetch failed: ' . $e->getMessage());
        }

        redirect("attendance/biometric/logs/{$device['id']}");
    }

    public function import_to_attendance($device_id)
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('create');
        $device_id = (int)$device_id;
        $rangeFrom = $this->input->post('range_from', true) ?: date('Y-m-01');
        $rangeTo   = $this->input->post('range_to',   true) ?: date('Y-m-t');

        $res = $this->bio->transform_to_attendance($device_id, $rangeFrom, $rangeTo);
        set_alert('success', "Transformed {$res['days']} day(s): {$res['created']} created, {$res['updated']} updated, {$res['flags']} flagged.");
        redirect("attendance/biometric/logs/{$device_id}");
    }

    /* ---------------------------
     * Scheduled (Cron/CLI)
     * --------------------------- */
    public function run_scheduled()
    {
        
        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        // CLI or tokenized URL: /attendance/biometric/run_scheduled?token=XYZ
        $token = $this->input->get('token');
        $cli   = is_cli();
        $cfg   = get_setting('biometric_cron_token', null);

        if (!$cli && (!$cfg || $token !== $cfg)) {
            show_error('Unauthorized', 401);
            return;
        }

        $devices = array_filter($this->bio->list_devices(), fn($d) => (int)$d['is_active'] === 1);
        foreach ($devices as $device) {
            try {
                $driver = $this->make_driver($device);
                $driver->connect();

                // Fetch “yesterday to today” by default
                $y = new DateTime('yesterday');
                $n = new DateTime('today');
                $records = $driver->getAttendance($y, $n);
                $dupWin  = (int)get_setting('biometric_duplicate_window_seconds', 60);
                $stats   = $this->bio->stage_logs((int)$device['id'], $records, $dupWin);

                // Optional: auto-transform “yesterday”
                $day = $y->format('Y-m-d');
                $this->bio->transform_to_attendance((int)$device['id'], $day, $day);

                log_message('info', "[biometric cron] Device {$device['name']}: +{$stats['inserted']} (skipped {$stats['skipped']})");
            } catch (Exception $e) {
                log_message('error', "[biometric cron] Device {$device['name']} failed: ".$e->getMessage());
            }
        }

        if (!$cli) echo "OK";
    }

    /* ---------------------------
     * Settings page (optional)
     * --------------------------- */
    public function settings()
    {

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->guard('edit');
        if ($this->input->method() === 'post') {
            $keys = [
                'biometric_enabled','biometric_default_device_id',
                'biometric_duplicate_window_seconds','biometric_grace_minutes',
                'biometric_late_after_minutes','biometric_early_leave_before_minutes',
                'biometric_cron_token','biometric_default_shift_start',
                'biometric_default_shift_end','biometric_timezone'
            ];
            foreach ($keys as $k) {
                set_setting($k, $this->input->post($k, true)); // your helper
            }
            set_alert('success','Biometric settings updated.');
            redirect('attendance/biometric/settings');
            return;
        }
        $layout_data = [
            'page_title' => 'Biometric Settings',
            'subview'    => 'attendance/biometric/settings',
            'view_data'  => [],
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /* ---------------------------
     * Helpers
     * --------------------------- */
    private function make_driver(array $device)
    {
        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        // If you add more drivers, switch by type in $device later.
        $cfg = ['ip'=>$device['ip_address'], 'port'=>$device['port'], 'comm_key'=>$device['comm_key']];
        $this->load->library('attendance/ZKtecoDriver', $cfg);
        return new ZKtecoDriver($cfg);
    }

    private function guard($perm) { /* plug into your staff_can('edit','attendance') etc */ }
    private function guard_view() { /* view permission check */ }




public function ping($device_id)
{

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
    // Always return JSON, no view rendering
    $this->output->set_content_type('application/json');

    // Auth guard (return JSON instead of redirecting to login)
    if (!$this->session->userdata('is_logged_in')) {
        $this->output->set_status_header(401)
            ->set_output(json_encode(['ok' => false, 'message' => 'Not authenticated.']));
        return;
    }

    $device_id = (int)$device_id;
    $device = $this->bio->get_device($device_id);
    if (!$device) {
        $this->output->set_status_header(404)
            ->set_output(json_encode(['ok' => false, 'message' => 'Device not found.']));
        return;
    }

    // QUICK REACHABILITY TEST (no SDK): try TCP connect to IP:port with short timeout
    $ip   = $device['ip_address'];
    $port = (int)$device['port'];
    $ok   = false; $err = '';

    // Suppress warnings, capture failure reason
    $errno = 0; $errstr = '';
    $start = microtime(true);
    $conn = @fsockopen($ip, $port, $errno, $errstr, 2.0); // 2s timeout
    if ($conn) {
        fclose($conn);
        $ok = true;
        // Stamp last_seen_at
        $this->db->where('id', $device_id)->update('biometric_devices', [
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);
    } else {
        $err = $errstr ?: 'Unable to open socket';
    }
    $ms = (int) ((microtime(true) - $start) * 1000);

    if ($ok) {
        $this->output->set_output(json_encode([
            'ok' => true,
            'message' => "Device reachable in {$ms}ms",
        ]));
    } else {
        $this->output->set_status_header(502)
            ->set_output(json_encode([
                'ok' => false,
                'message' => "Device unreachable: {$err} (ip {$ip}:{$port})",
            ]));
    }
    return;
}



public function ingest()
{

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
    $this->output->set_content_type('application/json');
    if (strtoupper($this->input->server('REQUEST_METHOD')) !== 'POST') {
        $this->output->set_status_header(405)
            ->set_output(json_encode(['ok'=>false,'message'=>'Method not allowed']));
        return;
    }

    $token = $this->input->server('HTTP_X_AGENT_TOKEN') ?: '';
    $payload = json_decode($this->input->raw_input_stream, true);

    if (!$payload || !is_array($payload)) {
        $this->output->set_status_header(400)
            ->set_output(json_encode(['ok'=>false,'message'=>'Invalid JSON']));
        return;
    }

    $deviceId = (int)($payload['device_id'] ?? 0);
    $records  = $payload['records'] ?? null;

    if ($deviceId <= 0 || !is_array($records)) {
        $this->output->set_status_header(422)
            ->set_output(json_encode(['ok'=>false,'message'=>'device_id and records required']));
        return;
    }

    $device = $this->bio->get_device($deviceId);
    if (!$device) {
        $this->output->set_status_header(404)
            ->set_output(json_encode(['ok'=>false,'message'=>'Device not found']));
        return;
    }
    if (empty($device['agent_token']) || !hash_equals($device['agent_token'], $token)) {
        $this->output->set_status_header(401)
            ->set_output(json_encode(['ok'=>false,'message'=>'Unauthorized']));
        return;
    }

    try {
        $dupWin = (int)get_setting('biometric_duplicate_window_seconds', 60);
        $stats  = $this->bio->stage_logs($deviceId, $records, $dupWin);

        // optional: auto-transform "yesterday"
        $tz = get_setting('biometric_timezone','Asia/Karachi');
        $y  = new DateTime('yesterday', new DateTimeZone($tz));
        $day= $y->format('Y-m-d');
        $transform = $this->bio->transform_to_attendance($deviceId, $day, $day);

        $this->db->where('id',$deviceId)->update('biometric_devices', [
            'last_seen_at'  => date('Y-m-d H:i:s'),
            'last_fetch_at' => date('Y-m-d H:i:s'),
        ]);

        $this->output->set_output(json_encode(['ok'=>true,'staged'=>$stats,'transform'=>$transform]));
    } catch (Exception $e) {
        $this->output->set_status_header(500)
            ->set_output(json_encode(['ok'=>false,'message'=>$e->getMessage()]));
    }
}


// --------------- ZKTeco ADMS/iClock endpoints ---------------
public function iclock($action = '')
{
    switch (strtolower($action)) {
        case 'cdata':       return $this->iclock_cdata();
        case 'getrequest':  return $this->iclock_getrequest();
        case 'devicecmd':   return $this->iclock_devicecmd();
        default: $this->output->set_status_header(404)->set_output(''); return;
    }
}

private function iclock_cdata()
{

        if (! staff_can('view_global','attendance')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
    $this->output->set_content_type('text/plain; charset=utf-8');
    $sn = $this->input->get('SN', true);
    if (!$sn) { $this->output->set_status_header(400)->set_output("ERROR: SN missing"); return; }

    $device = $this->db->get_where('biometric_devices', ['device_sn' => $sn, 'is_active' => 1])->row_array();
    if (!$device) { $this->output->set_status_header(401)->set_output("ERROR: unauthorized device"); return; }
    $device_id = (int)$device['id'];

    $raw = $this->input->raw_input_stream;
    if (!$raw) { $raw = $this->input->post('data', true) ?: ''; }

    if ($raw === '' || $raw === null) { $this->touch_device_seen($device_id); $this->output->set_output("OK"); return; }

    $lines = preg_split('/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $records = [];
    foreach ($lines as $line) {
        $rec = $this->parse_iclock_line($line);
        if ($rec) {
            $rec['device_user_id'] = (string)$rec['device_user_id'];
            $rec['punch_time']     = date('Y-m-d H:i:s', strtotime($rec['punch_time']));
            $records[] = $rec;
        }
    }
    if (empty($records)) { $this->touch_device_seen($device_id); $this->output->set_output("OK"); return; }

    $this->load->model('attendance/Biometric_model', 'bio');
    $dupWin = (int)get_setting('biometric_duplicate_window_seconds', 60);
    try {
        $stats = $this->bio->stage_logs($device_id, $records, $dupWin);
        $tz = get_setting('biometric_timezone','Asia/Karachi');
        $y  = new DateTime('yesterday', new DateTimeZone($tz));
        $day= $y->format('Y-m-d');
        $this->bio->transform_to_attendance($device_id, $day, $day);
        $this->touch_device_seen($device_id, true);
        $this->output->set_output("OK\nSTAGED={$stats['inserted']};SKIPPED={$stats['skipped']}");
    } catch (Exception $e) {
        $this->output->set_status_header(500)->set_output("ERROR: ".$e->getMessage());
    }
}

private function iclock_getrequest()
{
    $this->output->set_content_type('text/plain; charset=utf-8')->set_output("OK");
}

private function iclock_devicecmd()
{
    $this->output->set_content_type('text/plain; charset=utf-8')->set_output("OK");
}

private function touch_device_seen(int $device_id, bool $fetched = false): void
{
    $data = ['last_seen_at' => date('Y-m-d H:i:s')];
    if ($fetched) $data['last_fetch_at'] = $data['last_seen_at'];
    $this->db->where('id', $device_id)->update('biometric_devices', $data);
}

private function parse_iclock_line(string $line): ?array
{
    $line = trim($line);
    if ($line === '') return null;

    if (strpos($line, '=') !== false) { // key=value pairs
        $parts = preg_split('/[\t ]+/', $line);
        $map = [];
        foreach ($parts as $p) { if (strpos($p,'=')===false) continue; [$k,$v]=explode('=',$p,2); $map[strtolower(trim($k))]=trim($v); }
        $pin = $map['pin'] ?? $map['userid'] ?? $map['id'] ?? null;
        $dt  = $map['datetime'] ?? $map['time'] ?? $map['timestamp'] ?? null;
        if (!$pin || !$dt) return null;
        return [
            'device_user_id' => $pin,
            'punch_time'     => $dt,
            'status_code'    => isset($map['status'])   ? (int)$map['status']   : null,
            'verified'       => isset($map['verified']) ? (int)$map['verified'] : null,
            'work_code'      => $map['workcode'] ?? null,
            'punch_type'     => null
        ];
    }

    $tokens = preg_split('/\s+/', $line);
    if (strtoupper($tokens[0]) === 'ATTLOG' && count($tokens) >= 3) {
        return [
            'device_user_id' => $tokens[1],
            'punch_time'     => $tokens[2],
            'status_code'    => isset($tokens[3]) ? (int)$tokens[3] : null,
            'verified'       => isset($tokens[4]) ? (int)$tokens[4] : null,
            'work_code'      => isset($tokens[5]) ? $tokens[5] : null,
            'punch_type'     => null
        ];
    }
    return null;
}


    
}
