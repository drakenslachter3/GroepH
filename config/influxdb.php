<?php

return [
    'url' => env('INFLUXDB_URL', 'http://localhost:8086'),
    'token' => env('INFLUXDB_TOKEN', ''),
    'org' => env('INFLUXDB_ORG', ''),
    'bucket' => env('INFLUXDB_BUCKET', ''),
    'debug' => env('INFLUXDB_DEBUG', false),
    'timeout' => env('INFLUXDB_TIMEOUT', 10),
    'verify_ssl' => env('INFLUXDB_VERIFY_SSL', true),
];