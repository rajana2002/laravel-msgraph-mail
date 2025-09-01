<?php

return [
    'tenant_id' => env('MSGRAPH_TENANT_ID'),
    'client_id' => env('MSGRAPH_CLIENT_ID'),
    'secret_id' => env('MSGRAPH_SECRET_ID'),
    'from_address' => env('MSGRAPH_FROM_ADDRESS'),
    'msgraph_oauth_url' => env('MSGRAPH_OAUTH_URL', 'http://domain.com/msgraph/oauth'), // to be deleted?
    'msgraph_landing_url' => env('MSGRAPH_LANDING_URL', 'http://domain.com/msgraph') // to be deleted?
];
