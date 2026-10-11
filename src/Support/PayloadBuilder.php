<?php

namespace LaravelMonitor\Support;

use Illuminate\Support\Str;

class PayloadBuilder
{
    // Fields that must NEVER appear in any outbound payload.
    protected const FORBIDDEN_FIELDS = [
        'password',
        'db_password',
        'app_key',
        'api_key',
        'api_secret',
        'private_key',
        'secret_key',
        'token',
        'access_token',
        'refresh_token',
        'session_secret',
        'jwt_secret',
        'auth_key',
        'signing_secret',
        'encryption_key',
        'private_key_path',
        'ssh_key',
        'aws_secret',
        'stripe_secret',
        'sendgrid_secret',
    ];

    protected function __construct(protected \Illuminate\Contracts\Foundation\Application $app)
    {
    }

    public static function make(\Illuminate\Contracts\Foundation\Application $app): self
    {
        return new self($app);
    }

    public function build(): array
    {
        $payload = [
            'application_id' => $this->getApplicationId(),
            'domain'         => $this->getDomain(),
            'app_name'       => $this->appName(),
            'environment'    => $this->environment(),
            'laravel_version' => $this->laravelVersion(),
            'php_version'    => PHP_VERSION,
            'package_version' => $this->packageVersion(),
            'timestamp'      => \Carbon\Carbon::now()->toIso8601String(),
        ];

        if ($this->configBool('send_database_metadata')) {
            $payload['database'] = $this->buildDatabaseMeta();
        }

        if ($this->configBool('include_git_info')) {
            $payload['git'] = $this->buildGitInfo();
        }

        if ($this->configBool('include_os_info')) {
            $payload['os'] = $this->buildOsInfo();
        }

        if ($this->configBool('include_hostname')) {
            $payload['hostname'] = $this->getHostname();
        }

        if ($this->configBool('include_app_version')) {
            $payload['app_version'] = $this->appVersion();
        }

        return $this->sanitize($payload);
    }

    /**
     * Recursively scrub the payload of any sensitive data.
     * This is a safety net in addition to the explicit allow-listed field approach.
     */
    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
                continue;
            }

            // Skip the api_token — it must NEVER be in the payload.
            if (Str::lower($key) === 'api_token') {
                unset($data[$key]);
                continue;
            }

            // Check key name against forbidden list.
            if (in_array(Str::lower($key), self::FORBIDDEN_FIELDS, true)) {
                unset($data[$key]);
                continue;
            }

            // Check value for patterns that look like secrets.
            if (is_string($value) && $this->looksLikeSecret($key, $value)) {
                unset($data[$key]);
            }
        }

        return array_filter($data, fn($v) => $v !== null);
    }

    protected function looksLikeSecret(string $key, string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $key = Str::lower($key);

        // Never send anything whose value is a valid UUID/UUIDv4 that matches an ID pattern —
        // that's how we can still send application_id safely.
        if ($key === 'application_id' || $key === 'id') {
            return false;
        }

        // Detect common secret patterns: base64-encoded blocks of ~32+ chars,
        // hex strings of 32+ chars, or values prefixed with common secret prefixes.
        $hexPattern = '/^[a-f0-9]{32,}$/i';
        $base64Pattern = '/^[A-Za-z0-9+\/=]{40,}$/';

        if (preg_match($hexPattern, $value) || preg_match($base64Pattern, $value)) {
            return true;
        }

        // Key-based detection: if key name contains sensitive keywords, treat value as secret.
        $sensitiveKeyHints = [
            'key', 'secret', 'token', 'password', 'private', 'credential',
        ];

        foreach ($sensitiveKeyHints as $hint) {
            if (Str::contains($key, $hint) && $value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function getApplicationId(): string
    {
        return ApplicationIdentifier::make($this->app)->get();
    }

    protected function getDomain(): string
    {
        return Str::after($this->app['request']->getSchemeAndHttpHost(), '://');
    }

    protected function appName(): string
    {
        return $this->app['config']->get('app.name', 'Laravel');
    }

    protected function environment(): string
    {
        return $this->app['config']->get('app.env', 'production');
    }

    protected function laravelVersion(): string
    {
        return \Illuminate\Foundation\Application::VERSION;
    }

    protected function packageVersion(): string
    {
        return '1.0.0';
    }

    protected function buildDatabaseMeta(): array
    {
        $default = $this->app['config']->get('database.default', 'sqlite');
        $conn = $this->app['config']->get("database.connections.{$default}", []);

        return [
            'driver'   => $conn['driver'] ?? $default,
            'host'     => $conn['host'] ?? null,
            'port'     => $conn['port'] ?? null,
            'database' => $conn['database'] ?? null,
            'unix_socket' => $conn['unix_socket'] ?? null,
        ];
    }

    protected function buildGitInfo(): array
    {
        $base = $this->app->basePath();

        $headFile = $base . '/.git/HEAD';
        $head = @file_get_contents($headFile);

        $ref = null;
        $commit = null;

        if ($head) {
            $head = trim($head);

            // Detached HEAD or branch ref
            if (Str::startsWith($head, 'ref:')) {
                $ref = Str::after($head, 'ref: ');
            } else {
                $commit = $head;
            }
        }

        // Try to resolve ref to a commit hash
        if ($ref) {
            $refPath = $base . '/.git/' . str_replace('/', '/', $ref);
            $refHash = @file_get_contents($refPath);

            if ($refHash) {
                $commit = trim($refHash);
            }
        }

        // Try reading git describe for tag/version
        $describe = null;
        $output = [];
        $ret = 0;
        @exec("cd " . escapeshellarg($base) . " && git describe --tags --always 2>/dev/null", $output, $ret);
        if ($ret === 0 && !empty($output)) {
            $describe = trim($output[0]);
        }

        return [
            'branch'  => $ref ? str_replace('refs/heads/', '', $ref) : null,
            'commit'  => $commit,
            'describe' => $describe,
        ];
    }

    protected function buildOsInfo(): array
    {
        return [
            'name'     => 'Linux',
            'platform' => 'Linux x86_64',
            'arch'     => 'x86_64',
        ];
    }

    protected function getHostname(): string
    {
        return php_uname('n');
    }

    protected function appVersion(): ?string
    {
        $base = $this->app->basePath();
        $versionFile = $base . '/version';
        $version = @file_get_contents($versionFile);

        if ($version) {
            return trim($version);
        }

        // Try package.json version
        $packageJson = $base . '/package.json';
        if (is_file($packageJson)) {
            $json = json_decode(file_get_contents($packageJson), true);
            return $json['version'] ?? null;
        }

        return null;
    }

    protected function configBool(string $key): bool
    {
        return (bool) $this->app['config']->get("monitor.{$key}", false);
    }
}
