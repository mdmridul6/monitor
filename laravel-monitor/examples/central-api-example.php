<?php
/**
 * Example Central Monitoring API Implementation
 * This is a simplified example showing how a central monitoring server might work.
 * In a real implementation, you would use a proper framework like Laravel, Symfony, etc.
 */

// Example using a simple array for demonstration (in production, use a database)
$applications = [];
$heartbeats = [];

// Helper function to generate standardized responses
function apiResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

// Middleware to check authorization
function checkAuth()
{
    $headers = getallheaders();
    if (!isset($headers['Authorization']) || !preg_match('/^Bearer\s+(.+)$/', $headers['Authorization'], $matches)) {
        apiResponse(false, 'Unauthorized: Missing or invalid Authorization header', null, 401);
    }

    return $matches[1]; // Return the token
}

// Main API router
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove query string if present
$requestUri = explode('?', $requestUri)[0];

// Route: POST /api/v1/applications/heartbeat
if ($method === 'POST' && preg_match('#^/api/v1/applications/heartbeat$#', $requestUri)) {
    // Check authorization
    $token = checkAuth();

    // In a real implementation, you would validate the token against a database
    // For this example, we'll accept any non-empty token
    if (empty($token)) {
        apiResponse(false, 'Unauthorized: Invalid token', null, 401);
    }

    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        apiResponse(false, 'Bad Request: Invalid JSON', null, 400);
    }

    // Validate required fields
    $requiredFields = ['application_id'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            apiResponse(false, "Bad Request: Missing required field '{$field}'", null, 400);
        }
    }

    // Store or update application information
    $applicationId = $input['application_id'];

    if (!isset($applications[$applicationId])) {
        // New application
        $applications[$applicationId] = [
            'id' => $applicationId,
            'name' => $input['app_name'] ?? 'Unknown Application',
            'domain' => $input['domain'] ?? 'unknown',
            'environment' => $input['environment'] ?? 'unknown',
            'laravel_version' => $input['laravel_version'] ?? 'unknown',
            'php_version' => $input['php_version'] ?? 'unknown',
            'last_heartbeat_at' => null,
            'created_at' => new DateTimeImmutable(),
        ];
    } else {
        // Update existing application (but preserve creation time)
        $applications[$applicationId]['name'] = $input['app_name'] ?? $applications[$applicationId]['name'];
        $applications[$applicationId]['domain'] = $input['domain'] ?? $applications[$applicationId]['domain'];
        $applications[$applicationId]['environment'] = $input['environment'] ?? $applications[$applicationId]['environment'];
        $applications[$applicationId]['laravel_version'] = $input['laravel_version'] ?? $applications[$applicationId]['laravel_version'];
        $applications[$applicationId]['php_version'] = $input['php_version'] ?? $applications[$applicationId]['php_version'];
    }

    // Update last heartbeat timestamp
    $applications[$applicationId]['last_heartbeat_at'] = new DateTimeImmutable();
    $applications[$applicationId]['updated_at'] = new DateTimeImmutable();

    // Store heartbeat record
    $heartbeats[] = [
        'id' => count($heartbeats) + 1,
        'application_id' => $applicationId,
        'status' => 'online',
        'response_time_ms' => null, // In a real implementation, you might calculate this
        'created_at' => new DateTimeImmutable(),
    ];

    // Limit heartbeats to prevent unlimited growth (keep last 1000)
    if (count($heartbeats) > 1000) {
        $heartbeats = array_slice($heartbeats, -1000);
    }

    // Return success response
    apiResponse(true, 'Heartbeat received successfully', [
        'application_id' => $applicationId,
        'timestamp' => $applications[$applicationId]['last_heartbeat_at']->format(DateTimeInterface::ATOM),
    ]);

} elseif ($method === 'GET' && preg_match('#^/api/v1/applications$#', $requestUri)) {
    // Route: GET /api/v1/applications - List all applications
    $token = checkAuth();

    if (empty($token)) {
        apiResponse(false, 'Unauthorized: Invalid token', null, 401);
    }

    // Return list of applications
    $applicationList = array_values($applications);

    // Sort by last heartbeat (most recent first)
    usort($applicationList, function($a, $b) {
        $timeA = $a['last_heartbeat_at'] ?: new DateTimeImmutable('@0');
        $timeB = $b['last_heartbeat_at'] ?: new DateTimeImmutable('@0');
        return $timeB <=> $timeA;
    });

    apiResponse(true, 'Applications retrieved successfully', $applicationList);

} elseif ($method === 'GET' && preg_match('#^/api/v1/applications/([^/]+)/heartbeats$#', $requestUri, $matches)) {
    // Route: GET /api/v1/applications/{id}/heartbeats - Get heartbeats for specific application
    $token = checkAuth();

    if (empty($token)) {
        apiResponse(false, 'Unauthorized: Invalid token', null, 401);
    }

    $applicationId = $matches[1];

    if (!isset($applications[$applicationId])) {
        apiResponse(false, 'Not Found: Application not found', null, 404);
    }

    // Filter heartbeats for this application
    $applicationHeartbeats = array_filter($heartbeats, function($hb) use ($applicationId) {
        return $hb['application_id'] === $applicationId;
    });

    // Sort by creation time (most recent first)
    usort($applicationHeartbeats, function($a, $b) {
        return $b['created_at'] <=> $a['created_at'];
    });

    // Limit to last 50 heartbeats
    $applicationHeartbeats = array_slice(array_values($applicationHeartbeats), 0, 50);

    apiResponse(true, 'Application heartbeats retrieved successfully', $applicationHeartbeats);

} else {
    // Route not found
    apiResponse(false, 'Not Found: API endpoint not found', null, 404);
}