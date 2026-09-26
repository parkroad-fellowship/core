<?php

/*
 | Google Workspace directory credentials, used to create member mailboxes for tenants in
 | organisation-domain email mode. Owned by each tenant; there is no .env fallback.
 */
return [
    'service_account_json' => null,
    'admin_subject' => null,
    'org_unit_path' => '/',
];
