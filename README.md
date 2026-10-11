# Laravel Monitor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-monitor/laravel-monitor.svg?style=flat-square)](https://packagist.org/packages/laravel-monitor/laravel-monitor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/laravel-monitor/laravel-monitor/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laravel-monitor/laravel-monitor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-monitor/laravel-monitor.svg?style=flat-square)](https://packagist.org/packages/laravel-monitor/laravel-monitor)

A Laravel package to send application health and deployment metadata to a centralized monitoring API. The package automatically sends non-sensitive telemetry when your Laravel application boots and can send periodic heartbeats.

## Features

- ✅ **Secure by Design**: Never sends sensitive data (`.env`, passwords, API keys, etc.)
- ✅ **Non-blocking**: Uses Laravel's queue system to send heartbeats asynchronously
- ✅ **Automatic**: Initializes automatically when your Laravel application boots
- ✅ **Configurable**: Enable/disable specific telemetry data via configuration
- ✅ **Stable Identification**: Generates a unique, persistent application ID per installation
- ✅ **Production Ready**: Handles network failures gracefully without affecting your application
- ✅ **Well Tested**: Comprehensive test suite covering security, reliability, and edge cases
- ✅ **Laravel Conventions**: Follows Laravel best practices for service providers, configuration, and testing

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Security](#security)
- [Telemetry Data](#telemetry-data)
- [API Contract](#api-contract)
- [Testing](#testing)
- [Example Monitoring Server](#example-monitoring-server)
- [Changelog](#changelog)
- [License](#license)

## Installation

You can install the package via Composer:

```bash
composer require laravel-monitor/laravel-monitor
```

The package will automatically register itself. You can publish the configuration file with:

```bash
php artisan vendor:publish --tag=monitoring-config
```

This will publish the `config/monitor.php` file where you can customize the package behavior.

## Configuration

After publishing the configuration file, you can modify `config/monitor.php`:

```php
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
```

### Environment Variables

Set these in your `.env` file:

```env
APP_MONITORING_ENABLED=true
APP_MONITORING_ENDPOINT=https://monitor.example.com/api/v1/applications/heartbeat
APP_MONITORING_TOKEN=your-secret-token-here
```

### Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `enabled` | Toggle the monitoring package | `false` |
| `endpoint` | URL to send heartbeats to | `null` |
| `api_token` | Bearer token for authentication | `null` |
| `timeout` | HTTP request timeout in seconds | `5` |
| `send_database_metadata` | Include database connection info | `true` |
| `send_environment` | Include app environment (`local`, `production`, etc.) | `true` |
| `send_version_information` | Include Laravel/PHP/package versions | `true` |
| `queue_driver` | Queue connection to use for heartbeats | `default` |
| `max_retries` | Number of times to retry failed heartbeats | `3` |
| `retry_delay` | Seconds to wait between retries | `60` |
| `heartbeat_interval` | Seconds between automatic heartbeats | `3600` (1 hour) |
| `include_git_info` | Include Git branch/commit information | `false` |
| `include_os_info` | Include operating system information | `false` |
| `include_hostname` | Include server hostname | `false` |
| `include_app_version` | Include custom application version | `false` |
| `warn_http_endpoint` | Warn if using HTTP in production | `true` |
| `storage_path` | Where to store the persistent application ID | `storage_path('laravel-monitor')` |

## Usage

Once installed and configured, the package works automatically:

1. When your Laravel application boots, it will send an initial heartbeat
2. Subsequently, it will send heartbeats at the interval specified in `heartbeat_interval`
3. All heartbeats are sent via Laravel's queue system (non-blocking)
4. If the monitoring endpoint is unavailable, your application continues to work normally

### Manual Heartbeats

You can manually trigger a heartbeat using Artisan commands:

```bash
# Send heartbeat via queue (non-blocking)
php artisan monitor:heartbeat

# Send heartbeat synchronously (useful for debugging)
php artisan monitor:heartbeat --sync

# Check monitoring status
php artisan monitor:status
```

## Security

This package is designed with security as the top priority:

### What We Never Send
- `.env` file contents
- Database passwords
- `APP_KEY`
- API secrets/tokens
- Private keys
- OAuth tokens
- JWT secrets
- Session secrets
- Any configuration value that looks like a secret

### How We Ensure Security
1. **Allow-list Approach**: Only specific, non-sensitive data is included in payloads
2. **Runtime Scrubbing**: Additional safety check removes any data matching secret patterns
3. **Token Isolation**: API token is only used in HTTP headers, never in payloads
4. **Log Sanitization**: Sensitive data is never written to application logs
5. **HTTP Only**: API token is transmitted only via Authorization header
6. **Endpoint Validation**: Warns about using HTTP endpoints in production

### Data Handling
All outbound payloads are constructed from explicitly allowed data sources:
- Application metadata (name, version, environment)
- System information (PHP version, hostname - when enabled)
- Connection information (database driver, host, port - never credentials)
- Version control information (branch, commit - when enabled)
- Generated identifiers (stable application ID)

## Telemetry Data

The package sends the following non-sensitive information by default:

```json
{
    "application_id": "generated-stable-uuid",
    "domain": "example.com",
    "app_name": "My Laravel Application",
    "environment": "production",
    "laravel_version": "12.x",
    "php_version": "8.2",
    "package_version": "1.0.0",
    "timestamp": "2026-10-08T10:30:00+00:00",
    "database": {
        "driver": "mysql",
        "host": "127.0.0.1",
        "port": 3306,
        "database": "router_os"
    }
}
```

### Optional Telemetry
The following data can be enabled via configuration:

- **Git Information**: Branch, latest commit, describe output
- **OS Information**: System name, platform, architecture
- **Hostname**: Server hostname
- **App Version**: Custom application version (from `VERSION` file or `package.json`)

## API Contract

See [API_CONTRACT.md](API_CONTRACT.md) for the complete specification of the expected central monitoring server, including:
- Request/response formats
- Authentication requirements
- Validation rules
- Database schema
- Rate limiting guidelines
- Security considerations

## Testing

Run the test suite with:

```bash
composer test
```

Or with PHPUnit directly:

```bash
vendor/bin/phpunit
```

The test suite includes:
- Security tests (ensuring secrets are never sent)
- Feature tests (Artisan commands, HTTP client, jobs)
- Unit tests (MonitorManager, ApplicationIdentifier, PayloadBuilder)
- Configuration caching tests
- Retry and failure handling tests

## Example Monitoring Server

See the `example-server/` directory for a basic Laravel API implementation that can receive heartbeats from this package.

## Laravel Version Compatibility

This package supports Laravel 11.x and 12.x. It uses Laravel's HTTP client and queue system which are stable across these versions.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel-monitor/laravel-monitor/security/policy) on how to report security vulnerabilities.

## Credits

- [Developer Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.