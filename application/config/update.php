<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Update configuration
 *
 * How it works:
 * - The app fetches a JSON manifest from `manifest_url`.
 * - It only accepts responses/downloads if the host matches `allowed_host`.
 * - The manifest provides version, release notes, and URLs to the ZIP (and optional signature).
 *
 * Changeover plan:
 * - During local/staging, you can leave `manifest_url`/`allowed_host` blank and our controller
 *   defaults to /settings/updates/manifest (local endpoint).
 * - For production, set both to your update server (HTTPS strongly recommended).
 */

$config['update'] = [
    // Release channel to read from the manifest ("stable" or "beta" — must exist in manifest)
    'channel'          => 'stable',

    // ===== PRODUCTION SETTINGS (set these when your update server is live) =====
    // This should point to your published manifest.json
    'manifest_url'     => 'https://demo.linqer.me/manifest.json',
    // Safety check: only accept from this host (no scheme, no path)
    'allowed_host'     => 'demo.linqer.me',

    // ===== LOCAL/STAGING FALLBACK =====
    // If you want to use the local manifest endpoint instead of the remote server
    // just comment the two lines above and leave them empty like this:
    // 'manifest_url'     => '',
    // 'allowed_host'     => '',
    //
    // With both blank, the controller auto-defaults to:
    //   manifest_url = site_url('settings/updates/manifest')
    //   allowed_host = current request host

    // Working directories (must be writable by PHP user)
    'maintenance_flag' => APPPATH.'cache/maintenance.lock',
    'staging_dir'      => APPPATH.'cache/update_staging',
    'download_dir'     => APPPATH.'cache/update_downloads',

    // Optional: signature verification
    // Put your PUBLIC key here when you start signing releases (recommended).
    // If present AND the manifest provides a .sig, the updater verifies the ZIP with this key.
    'public_key_path'  => APPPATH.'config/update_pubkey.pem',
];
