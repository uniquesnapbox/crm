<?php

return [
    'token' => env('INTEGRATION_API_TOKEN', ''),
    'default_per_page' => env('INTEGRATION_API_DEFAULT_PER_PAGE', 12),
    'max_per_page' => env('INTEGRATION_API_MAX_PER_PAGE', 50),
];