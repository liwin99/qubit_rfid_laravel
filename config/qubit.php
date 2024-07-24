<?php

return [
    'reader_minutes_offline' => env('READER_MINUTES_OFFLINE', 5),
    'rssi_threshold' => env('RSSI_THRESHOLD', -50),
    'debounce_minutes' => env('DEBOUNCE_MINUTES', 10),
    'double_reader_interval_seconds' => env('DOUBLE_READER_INTERVAL_SECONDS', 10)
];
