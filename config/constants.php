<?php

return [
    'ES' => [
        'max_bucket_size' => 1000,
        'max_allowed_bucket_size' => 10000,
        'scroll' => '10m'
    ],
    'Export' => [
        'expire_time' => 60,
    ],
    'S3' => [
        'lead_folder' => 'lead'
    ]
];
