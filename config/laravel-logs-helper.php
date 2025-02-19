<?php

return [
    'rotation_size' => [
        'one_file_size_limit_bytes' => env('LOGS_ROTATION_SIZE_ONE_FILE', 0),
        'channel_size_limit_bytes' => env('LOGS_ROTATION_SIZE_CHANNEL', 0),
        'total_size_limit_bytes' => env('LOGS_ROTATION_SIZE_TOTAL', 0),
    ],
];