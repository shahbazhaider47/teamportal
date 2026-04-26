<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $company = company_info();
    $companyName = isset($company['companyname']) ? html_escape($company['companyname']) : 'Your Company';
    $version = defined('APP_VERSION') ? APP_VERSION : 'v1.0.0';
    $year = date('Y');
?>

<footer class="border-top">
    <div class="">
        <div class="row">
            <div class="col-md-12">
                <p class="mb-0 text-muted small">
                    &copy; <?= $year ?> <?= html_escape($company['company_name'] ?? 'Company Name') ?> - All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

