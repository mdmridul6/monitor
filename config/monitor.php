<?php

return [

    'enabled' => env('APP_MONITORING_ENABLED', false),

    'endpoint' => env('APP_MONITORING_ENDPOINT'),

    'api_token' => env('APP_MONITORING_TOKEN'),

    'timeout' => 5,

    'send_database_metadata' => true,

    'send_environment' => true,

    'send_version_information' => true,

    'queue_driver' => env('APP_MONITORING_QUEUE', 'default'),

    'max_retries' => 3,

    'retry_delay' => 60,

    'heartbeat_interval' => 3600,

    'include_git_info' => false,

    'include_os_info' => false,

    'include_hostname' => false,

    'include_app_version' => false,

    'warn_http_endpoint' => true,

    'storage_path' => storage_path('laravel-monitor'),

];
