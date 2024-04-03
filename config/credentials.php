<?php

return [
    'credentials' => [
        'json' => __DIR__ . '/../storage/app/' . env('GOOGLE_SERVICE_ACCOUNT_JSON_PATH')
    ],
    'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
];
