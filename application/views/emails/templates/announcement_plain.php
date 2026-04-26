<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Plain-text fallback for Announcement emails
 */
?>

<?= strtoupper($title ?? 'ANNOUNCEMENT') ?>


Hello <?= $recipient_name ?? 'there' ?>,

<?= strip_tags($message ?? '') ?>


View announcement:
<?= $cta_url ?? base_url('announcements') ?>


--
<?= $brand ?? 'Company' ?>
<?= $brand_url ?? base_url() ?>
