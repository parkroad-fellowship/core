<?php

return [
    'google_sheets' => [
        'service_account_key_path' => env('GOOGLE_SERVICE_ACCOUNT_KEY_PATH'),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SOCIAL_MEDIA_SPREADSHEET_ID'),
        'sheet_name' => env('GOOGLE_SHEETS_SOCIAL_MEDIA_SHEET_NAME'),
    ],
];
