# Laravel Monitor - Central Monitoring API Contract

## Overview
This document describes the API contract for the central monitoring server that receives heartbeat telemetry from Laravel applications using the `laravel-monitor` package.

## Endpoint
```
POST /api/v1/applications/heartbeat
```

## Authentication
The central API must authenticate incoming requests using Bearer token authentication.

**Required Header:**
```
Authorization: Bearer <API_TOKEN>
Content-Type: application/json
Accept: application/json
```

The API token must be kept secret and should be randomly generated per application registration.

## Request Format
The payload must be a JSON object with the following structure:

```json
{
    "application_id": "string (UUID)",
    "domain": "string",
    "app_name": "string",
    "environment": "string",
    "laravel_version": "string",
    "php_version": "string",
    "package_version": "string",
    "timestamp": "string (ISO 8601)",
    "database": {
        "driver": "string",
        "host": "string|null",
        "port": "integer|null",
        "database": "string|null",
        "unix_socket": "string|null"
    },
    "git": {
        "branch": "string|null",
        "commit": "string|null",
        "describe": "string|null"
    },
    "os": {
        "name": "string",
        "platform": "string",
        "arch": "string"
    },
    "hostname": "string|null",
    "app_version": "string|null"
}
```

**Notes:**
- Fields marked as `null` are optional and depend on package configuration.
- The `database`, `git`, and `os` objects are only included when their respective configuration flags are enabled.
- All sensitive data (passwords, tokens, keys) must be stripped by the Laravel package before transmission.

## Validation Rules
The central API must validate incoming requests according to these rules:

1. **application_id**: Required, must be a valid UUID v4
2. **domain**: Required, must be a valid hostname or IP address
3. **app_name**: Required, string, max 255 characters
4. **environment**: Required, one of: `local`, `testing`, `development`, `staging`, `production`
5. **laravel_version**: Required, string format (e.g., "11.x", "12.x")
6. **php_version**: Required, string format (e.g., "8.2", "8.3")
7. **package_version**: Required, semantic version format
8. **timestamp**: Required, valid ISO 8601 datetime string
9. **database.driver**: Optional, one of: `mysql`, `pgsql`, `sqlite`, `sqlsrv`
10. **database.host**: Optional, valid hostname, IP, or empty string
11. **database.port**: Optional, integer between 1-65535
12. **database.database**: Optional, string, max 64 characters
13. **git.branch**: Optional, string
14. **git.commit**: Optional, 40-character hex string (SHA-1)
15. **git.describe**: Optional, string
16. **os.name**: Optional, one of: `Windows`, `Linux`, `Darwin`
17. **os.platform**: Optional, string
18. **os.arch**: Optional, string (e.g., `x86_64`, `arm64`)
19. **hostname**: Optional, string, max 255 characters
20. **app_version**: Optional, string, max 50 characters

## Response Format
Successful requests must return a JSON response with HTTP status 200:

```json
{
    "success": true,
    "message": "Heartbeat received",
    "application_id": "string (UUID)",
    "received_at": "string (ISO 8601)"
}
```

Error responses must return appropriate HTTP status codes with JSON error details:

### 400 Bad Request
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Invalid or missing authentication token"
}
```

### 429 Too Many Requests
```json
{
    "success": false,
    "message": "Rate limit exceeded",
    "retry_after": "integer (seconds)"
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Internal server error"
}
```

## Database Schema
The central monitoring server should store application registrations and heartbeats using the following schema:

### Applications Table
```sql
CREATE TABLE applications (
    id CHAR(36) PRIMARY KEY, -- UUID v4
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    environment VARCHAR(50) NOT NULL,
    laravel_version VARCHAR(20) NOT NULL,
    php_version VARCHAR(20) NOT NULL,
    api_token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_heartbeat_at TIMESTAMP NULL,
    status ENUM('online', 'offline', 'unknown') DEFAULT 'unknown',
    INDEX idx_status (status),
    INDEX idx_last_heartbeat (last_heartbeat_at)
);
```

### Heartbeats Table
```sql
CREATE TABLE heartbeats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    application_id CHAR(36) NOT NULL,
    domain VARCHAR(255),
    app_name VARCHAR(255),
    environment VARCHAR(50),
    laravel_version VARCHAR(20),
    php_version VARCHAR(20),
    package_version VARCHAR(20),
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    database_driver VARCHAR(20),
    database_host VARCHAR(255),
    database_port INTEGER,
    database_name VARCHAR(64),
    git_branch VARCHAR(255),
    git_commit CHAR(40),
    git_describe VARCHAR(255),
    os_name VARCHAR(50),
    os_platform VARCHAR(100),
    os_arch VARCHAR(20),
    hostname VARCHAR(255),
    app_version VARCHAR(50),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_received_at (received_at),
    INDEX idx_application_received (application_id, received_at)
);
```

## Rate Limiting
To prevent abuse, the API should implement rate limiting:
- Maximum 10 requests per minute per application
- Maximum 100 requests per minute per IP address
- Return HTTP 429 with `Retry-After` header when limit exceeded

## Duplicate Handling
If a heartbeat is received for an application that doesn't exist in the `applications` table:
1. Option 1: Reject with 404 (requires pre-registration)
2. Option 2: Auto-register the application (less secure but easier setup)
3. Option 3: Store heartbeat but mark application as pending verification

Recommended: Require pre-registration via a separate `/api/v1/applications/register` endpoint.

## Security Considerations
1. **HTTPS Required**: All communication must use HTTPS in production
2. **Input Sanitization**: All incoming data must be sanitized to prevent injection attacks
3. **Log Sanitization**: Never log the full payload or authorization token
4. **CORS**: Restrict origins if the API is browser-accessible
5. **Database Protection**: Use prepared statements to prevent SQL injection
6. **Secrets Storage**: API tokens should be hashed in the database (like passwords)

## Example Implementation (Laravel)
See the `example-server/` directory for a basic Laravel API implementation.