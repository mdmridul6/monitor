# Laravel Monitor

A production-ready Laravel package for monitoring application health and deployment metadata. This package sends non-sensitive telemetry data to a centralized monitoring API whenever your Laravel application starts or periodically sends a heartbeat.

## Features

- ✅ Automatic heartbeat on application boot
- ✅ Configurable periodic heartbeats (via Laravel scheduler)
- ✅ Queue-based processing to avoid blocking application requests
- ✅ Secure authentication with Bearer tokens
- ✅ Stable, unique application ID generation
- ✅ Comprehensive error handling and logging
- ✅ Zero sensitive data transmission (never sends .env, passwords, keys, etc.)
- ✅ HTTPS enforcement in production
- ✅ Retry mechanism for failed requests
- ✅ Artisan commands for status checks and manual heartbeats
- ✅ Laravel 10+ compatible
- ✅ Fully tested with PHPUnit/Pest

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Composer

## Installation

### 1. Install the package via Composer

```bash
composer require vendor/laravel-monitor
```

### 2. Publish the configuration file

```bash
php artisan vendor:publish --tag=monitoring-config
```

This will create a `config/monitor.php` file in your Laravel application.

### 3. Configure your environment variables

Add the following to your `.env` file:

```env
APP_MONITORING_ENABLED=true
APP_MONITORING_ENDPOINT=https://monitor.example.com/api/v1/applications/heartbeat
APP_MONITORING_TOKEN=your-secret-token-here
```

### 4. Configure the scheduler (for periodic heartbeats)

Ensure you have a cron entry set up to run the Laravel scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Configuration

After publishing the configuration file, you can modify `config/monitor.php`:

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

## What Data is Sent

The package sends the following non-sensitive information:

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

### Data That Is NEVER Sent

For security, the package ensures that none of the following are ever transmitted:
- `.env` file contents
- Database passwords
- `APP_KEY`
- API secrets
- Private keys
- OAuth tokens
- JWT secrets
- Session secrets
- Any other sensitive configuration values

## Security

- All communication with the monitoring server uses HTTPS (HTTP is warned against in production)
- API tokens are only sent in the Authorization header, never in the payload
- Application ID is generated once and stored locally for consistency
- Comprehensive error handling prevents monitoring failures from affecting your application
- Sensitive data is never logged

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
# or
vendor/bin/pest
```

## API Contract (Central Monitoring Server)

For reference, here's the expected API contract for your central monitoring server:

### Endpoint
```
POST /api/v1/applications/heartbeat
```

### Headers
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

### Request Body
```json
{
    "application_id": "uuid-string",
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

### Response
```json
{
    "success": true,
    "message": "Heartbeat received"
}
```

### Database Schema
```sql
CREATE TABLE applications (
    id CHAR(36) PRIMARY KEY, -- UUID
    name VARCHAR(255),
    domain VARCHAR(255),
    environment VARCHAR(50),
    laravel_version VARCHAR(20),
    php_version VARCHAR(20),
    last_heartbeat_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE heartbeats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    application_id CHAR(36),
    status VARCHAR(20) DEFAULT 'online', -- online, offline, unknown
    response_time_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);
```

## How to Use This Package

### Basic Usage

1. **Install the package** as described in the Installation section
2. **Configure your environment variables** in the `.env` file
3. **Publish the configuration file** and customize it if needed
4. **Set up the scheduler** to run periodic heartbeats
5. **Start your Laravel application**

The package will automatically send heartbeats when the application boots and periodically thereafter.

### Advanced Usage

#### Customizing the Heartbeat Payload

If you need to add custom data to the heartbeat payload, you can publish and modify the `config/monitor.php` file. Add your custom fields to the configuration array, and they will be included in the heartbeat payload.

#### Handling Heartbeat Failures

The package includes a retry mechanism for failed requests. You can configure the number of retry attempts and the delay between retries in the configuration file.

#### Manual Heartbeat Triggering

You can manually trigger a heartbeat using the Artisan command:

```bash
php artisan monitor:heartbeat
```

This is useful for testing or when you want to force a heartbeat to be sent immediately.

#### Checking Package Status

You can check the package status and configuration using the Artisan command:

```bash
php artisan monitor:status
```

This will display information about the package configuration, including whether monitoring is enabled, the endpoint URL, and other settings.

#### Testing the Connection

You can test the connection to the monitoring endpoint using the Artisan command:

```bash
php artisan monitor:status --test-connection
```

This will send a test request to the monitoring endpoint and display the response.

## Troubleshooting

### Common Issues

1. **Heartbeats not being sent**:
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

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues or have questions, please open an issue on the GitHub repository.