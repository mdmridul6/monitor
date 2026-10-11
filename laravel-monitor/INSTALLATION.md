# Laravel Monitor Package Installation Guide

## Overview

This guide will help you install and configure the Laravel Monitor package in your Laravel application. This package sends non-sensitive telemetry data to a centralized monitoring API whenever your Laravel application starts or periodically sends a heartbeat.

## Prerequisites

- PHP 8.1 or higher
- Laravel 10+ or 11+
- Composer

## Installation

### Step 1: Install the Package via Composer

Run the following command in your Laravel project directory:

```bash
composer require vendor/laravel-monitor
```

### Step 2: Publish the Configuration File

Publish the package configuration file:

```bash
php artisan vendor:publish --tag=monitoring-config
```

This will create a `config/monitor.php` file in your Laravel application.

### Step 3: Configure Environment Variables

Add the following environment variables to your `.env` file:

```env
APP_MONITORING_ENABLED=true
APP_MONITORING_ENDPOINT=https://monitor.example.com/api/v1/applications/heartbeat
APP_MONITORING_TOKEN=your-secret-token-here
```

### Step 4: Configure the Package

Edit the `config/monitor.php` file to customize the package settings:

```php
return [
    'enabled' => env('APP_MONITORING_ENABLED', false),
    
    'endpoint' => env('APP_MONITORING_ENDPOINT'),
    
    'api_token' => env('APP_MONITORING_TOKEN'),
    
    'timeout' => env('APP_MONITORING_TIMEOUT', 5),
    
    'send_database_metadata' => env('APP_MONITORING_SEND_DATABASE_METADATA', true),
    
    'send_environment' => env('APP_MONITORING_SEND_ENVIRONMENT', true),
    
    'send_version_information' => env('APP_MONITORING_SEND_VERSION_INFORMATION', true),
    
    'queue_connection' => env('APP_MONITORING_QUEUE_CONNECTION', null),
    
    'heartbeat_interval' => env('APP_MONITORING_HEARTBEAT_INTERVAL', 5),
    
    'retry_attempts' => env('APP_MONITORING_RETRY_ATTEMPTS', 3),
    
    'retry_delay' => env('APP_MONITORING_RETRY_DELAY', 10),
];
```

### Step 5: Configure the Scheduler

Ensure you have a cron entry set up to run the Laravel scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Usage

### Automatic Heartbeats

Once installed and configured, the package will automatically:
1. Send a heartbeat when your Laravel application boots
2. Send periodic heartbeats based on the `heartbeat_interval` configuration (default: 5 minutes)

### Manual Heartbeats

You can manually trigger a heartbeat using Artisan:

```bash
# Send heartbeat via queue (recommended)
php artisan monitor:heartbeat

# Send heartbeat synchronously (for debugging)
php artisan monitor:heartbeat --sync
```

### Check Package Status

```bash
# Basic status check
php artisan monitor:status

# Show application ID
php artisan monitor:status --show-id

# Test connection to monitoring endpoint
php artisan monitor:status --test-connection
```

## Security Considerations

The package ensures that none of the following are ever transmitted:
- `.env` file contents
- Database passwords
- `APP_KEY`
- API secrets
- Private keys
- OAuth tokens
- JWT secrets
- Session secrets
- Any other sensitive configuration values

All communication with the monitoring server uses HTTPS (HTTP is warned against in production).

## API Contract

The package sends the following data to the monitoring server:

```json
{
    "application_id": "generated-stable-uuid",
    "domain": "example.com",
    "environment": "production",
    "app_name": "My Laravel Application",
    "laravel_version": "12.x",
    "php_version": "8.x",
    "package_version": "1.0.0",
    "database": {
        "driver": "mysql",
        "host": "127.0.0.1",
        "port": 3306,
        "database": "router_os"
    }
}
```

The API endpoint expects a POST request to `/api/v1/applications/heartbeat` with the above payload.

## Troubleshooting

### Common Issues

1. **Monitoring not sending heartbeats**:
   - Verify that `APP_MONITORING_ENABLED` is set to `true` in your `.env` file
   - Check that the endpoint and token are properly configured
   - Ensure the scheduler is running

2. **Application ID not generated**:
   - Make sure the storage directory is writable
   - Check that the application has permission to write to the storage path

3. **Connection errors**:
   - Verify the endpoint URL is correct
   - Ensure the token is valid
   - Check that the server is reachable from your application

### Debugging

To debug issues, you can:

1. Check the Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Enable verbose logging in the package:
   ```php
   // In config/monitor.php
   'log_level' => 'debug',
   ```

3. Use the test connection command:
   ```bash
   php artisan monitor:status --test-connection
   ```