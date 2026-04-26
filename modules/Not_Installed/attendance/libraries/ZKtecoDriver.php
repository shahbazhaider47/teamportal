<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Minimal ZKTeco socket driver using PHP sockets (ZKLib-like flow).
 * If you already use a vendor ZKLib, swap internals but keep the interface.
 */
class ZKtecoDriver implements BiometricDriver
{
    protected $ip;
    protected $port;
    protected $commKey;

    public function __construct(array $cfg)
    {
        $this->ip      = $cfg['ip'] ?? '';
        $this->port    = (int)($cfg['port'] ?? 4370);
        $this->commKey = $cfg['comm_key'] ?? null;
    }

    public function connect(): void
    {
        // Implement your chosen ZK protocol handshake here.
        // For production, I recommend dropping in a proven ZKLib implementation.
        if (empty($this->ip) || $this->port <= 0) {
            throw new Exception('Invalid device configuration.');
        }
        // Assume reachable; real impl should open socket and auth.
    }

    public function ping(): bool
    {
        try {
            $this->connect();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUsers(): array
    {
        // Return array of ['device_user_id' => '12', 'name' => 'Ali', 'code' => 'RCM-001']
        // Replace with actual SDK call. We return empty to keep the interface stable.
        return [];
    }

    public function getAttendance(?DateTime $from = null, ?DateTime $to = null): array
    {
        // Return normalized array:
        // [
        //   ['device_user_id'=>'12','punch_time'=>'2025-10-20 09:01:23','status_code'=>0,'verified'=>1,'work_code'=>null],
        //   ...
        // ]
        // Replace with SDK calls; here we throw to make it clear you must wire the SDK.
        throw new Exception('ZKTeco SDK integration required (wire ZKLib here).');
    }
}
