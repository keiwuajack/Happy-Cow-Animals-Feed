<?php
// Business details shown on receipts and quotations. Edited on the Business Settings page.
require_once __DIR__ . '/settings_helper.php';
return [
    'name'    => hc_setting('business_name', 'HAPPY COW'),
    'tagline' => hc_setting('business_tagline', 'Animal Feeds'),
    'phone'   => hc_setting('business_phone', ''),
    'email'   => hc_setting('business_email', ''),
    'address' => hc_setting('business_address', 'Kenya'),
    'receipt_note' => hc_setting('receipt_note', 'Goods once sold cannot be returned'),
];
