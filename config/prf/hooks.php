<?php

return [
    'google_sheets' => [
        'service_account_key_path' => env('GOOGLE_SERVICE_ACCOUNT_KEY_PATH'),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SOCIAL_MEDIA_SPREADSHEET_ID'),
        'sheet_name' => env('GOOGLE_SHEETS_SOCIAL_MEDIA_SHEET_NAME'),
    ],

    'google_drive' => [
        'folder_id' => env('GOOGLE_DRIVE_MISSIONS_FOLDER_ID'),
        'shared_drive_id' => env('GOOGLE_DRIVE_SHARED_DRIVE_ID'),
    ],
];
