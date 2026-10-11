<?php

namespace LaravelMonitor\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\UniqueId;

class ApplicationIdentifier
{
    protected const CACHE_KEY = 'laravel-monitor:application_id';
    protected const CACHE_TTL = 365 * 24 * 60; // 1 year in minutes
    protected const STORAGE_FILENAME = '.monitor-id';

    public static function make(\Illuminate\Contracts\Foundation\Application $app): self
    {
        return new self($app);
    }

    public function __construct(protected \Illuminate\Contracts\Foundation\Application $app)
    {
    }

    public function get(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached) {
            return $cached;
        }

        $fromFile = $this->readFromFile();

        if ($fromFile) {
            Cache::put(self::CACHE_KEY, $fromFile, \Carbon\Carbon::now()->addMinutes(self::CACHE_TTL));
            return $fromFile;
        }

        $newId = $this->generateId();
        $this->persistToCache($newId);
        $this->persistToFile($newId);

        return $newId;
    }

    public function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->deleteFile();
    }

    protected function generateId(): string
    {
        // Use UniqueId if available, fall back to Str::uuid
        if (class_exists(UniqueId::class)) {
            return (string) new UniqueId();
        }

        return (string) Str::uuid();
    }

    protected function persistToCache(string $id): void
    {
        Cache::forever(self::CACHE_KEY, $id);
    }

    protected function persistToFile(string $id): void
    {
        $storagePath = config('monitor.storage_path', storage_path('laravel-monitor'));

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filePath = $storagePath . DIRECTORY_SEPARATOR . self::STORAGE_FILENAME;

        @file_put_contents($filePath, $id, LOCK_EX);
    }

    protected function readFromFile(): ?string
    {
        $storagePath = config('monitor.storage_path', storage_path('laravel-monitor'));
        $filePath = $storagePath . DIRECTORY_SEPARATOR . self::STORAGE_FILENAME;

        if (!is_file($filePath)) {
            return null;
        }

        $content = trim(@file_get_contents($filePath));

        return $content !== '' ? $content : null;
    }

    protected function deleteFile(): void
    {
        $storagePath = config('monitor.storage_path', storage_path('laravel-monitor'));
        $filePath = $storagePath . DIRECTORY_SEPARATOR . self::STORAGE_FILENAME;

        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
}
