<?php

return [
    'base_url' => env('HODOWLA_API_BASE_URL', ''),
    'timeout' => (int) env('HODOWLA_API_TIMEOUT', 10),
    'token' => env('HODOWLA_API_TOKEN'),
];
