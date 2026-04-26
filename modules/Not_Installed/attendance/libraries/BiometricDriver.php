<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Generic biometric driver interface.
 * Implementations: ZKtecoDriver, (future) CloudPushDriver, etc.
 */
interface BiometricDriver
{
    /**
     * @param array $cfg ['ip' => 'x.x.x.x', 'port' => 4370, 'comm_key' => '...']
     */
    public function __construct(array $cfg);

    /** @throws Exception on failure */
    public function connect(): void;

    /** Pull attendance logs within an optional window; return array of records */
    public function getAttendance(?DateTime $from = null, ?DateTime $to = null): array;

    /** Get registered users from device for mapping */
    public function getUsers(): array;

    /** Optional health check / ping */
    public function ping(): bool;
}
