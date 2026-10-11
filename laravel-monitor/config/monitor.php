<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Enable/Disable Switch
    |--------------------------------------------------------------------------
    |
    | This setting determines whether the monitoring package is enabled or not.
    | When disabled, no telemetry data will be sent to the monitoring endpoint.
    |
    */

    'enabled' => env('APP_MONITORING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Monitoring Endpoint URL
    |--------------------------------------------------------------------------
    |
    | The URL of the central monitoring API endpoint that will receive
    | the heartbeat payloads from this application.
    |
    */

    'endpoint' => env('APP_MONITORING_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | API Token for Authentication
    |--------------------------------------------------------------------------
    |
    | The secret token used to authenticate with the monitoring server.
    | This should be kept secure and never exposed in logs or responses.
    |
    */

    'api_token' => env('APP_MONITORING_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait for a response from the monitoring API
    | before considering the request failed.
    |
    */

    'timeout' => env('APP_MONITORING_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Database Metadata Collection
    |--------------------------------------------------------------------------
    |
    | Determines whether database connection information should be included
    | in the telemetry payload. This does NOT include sensitive credentials.
    |
    */

    'send_database_metadata' => env('APP_MONITORING_SEND_DATABASE_METADATA', true),

    /*
    |--------------------------------------------------------------------------
    | Environment Information Collection
    |--------------------------------------------------------------------------
    |
    | Determines whether application environment information should be included
    | in the telemetry payload.
    |
    */

    'send_environment' => env('APP_MONITORING_SEND_ENVIRONMENT', true),

    /*
    |--------------------------------------------------------------------------
    | Version Information Collection
    |--------------------------------------------------------------------------
    |
    | Determines whether Laravel/PHP version information should be included
    | in the telemetry payload.
    |
    */

    'send_version_information' => env('APP_MONITORING_SEND_VERSION_INFORMATION', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection for Heartbeat Jobs
    |--------------------------------------------------------------------------
    |
    | The queue connection that should be used to send heartbeat requests.
    | Using queues ensures the Laravel application is not blocked waiting
    | for the monitoring API response.
    |
    */

    'queue_connection' => env('APP_MONITORING_QUEUE_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Interval (minutes)
    |--------------------------------------------------------------------------
    |
    | How often heartbeat requests should be sent to the monitoring server.
    | Set to 0 to disable periodic heartbeats (only boot heartbeat will be sent).
    |
    */

    'heartbeat_interval' => env('APP_MONITORING_HEARTBEAT_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Retry Attempts for Failed Requests
    |--------------------------------------------------------------------------
    |
    | Number of times to retry a failed heartbeat request before giving up.
    |
    */

    'retry_attempts' => env('APP_MONITORING_RETRY_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Delay (seconds)
    |--------------------------------------------------------------------------
    |
    | Number of seconds to wait between retry attempts.
    |
    */

    'retry_delay' => env('APP_MONITORING_RETRY_DELAY', 10),

];