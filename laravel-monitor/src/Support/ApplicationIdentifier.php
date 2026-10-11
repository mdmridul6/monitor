<?php

namespace Vendor\LaravelMonitor\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class ApplicationIdentifier
{
    /**
     * Cache key for storing the application ID.
     *
     * @var string
     */
    const CACHE_KEY = 'laravel_monitor_application_id';

    /**
     * Storage path for persisting the application ID.
     *
     * @var string
     */
    const STORAGE_PATH = 'laravel-monitor/application_id.txt';

    /**
     * Get the unique and stable application ID.
     *
     * The ID is generated once and stored locally to ensure consistency
     * across application restarts.
     *
     * @return string
     */
    public static function getId()
    {
        // Try to get from cache first
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        // Try to get from file storage
        if (self::hasStoredId()) {
            $id = self::getStoredId();
            // Cache it for faster access next time
            Cache::forever(self::CACHE_KEY, $id);
            return $id;
        }

        // Generate a new ID and store it
        $id = self::generateId();
        self::storeId($id);
        // Cache it for faster access next time
        Cache::forever(self::CACHE_KEY, $id);

        return $id;
    }

    /**
     * Check if we have a stored application ID.
     *
     * @return bool
     */
    protected static function hasStoredId()
    {
        $filesystem = new Filesystem();
        $storagePath = storage_path(self::STORAGE_PATH);

        return $filesystem->exists($storagePath);
    }

    /**
     * Get the stored application ID from file.
     *
     * @return string|null
     */
    protected static function getStoredId()
    {
        $filesystem = new Filesystem();
        $storagePath = storage_path(self::STORAGE_PATH);

        if ($filesystem->exists($storagePath)) {
            return trim($filesystem->get($storagePath));
        }

        return null;
    }

    /**
     * Store the application ID to file.
     *
     * @param  string  $id
     * @return void
     */
    protected static function storeId(string $id)
    {
        $filesystem = new Filesystem();
        $storagePath = storage_path(self::STORAGE_PATH);

        // Ensure the directory exists
        $directory = dirname($storagePath);
        if (!$filesystem->isDirectory($directory)) {
            $filesystem->makeDirectory($directory, 0755, true);
        }

        // Store the ID
        $filesystem->put($storagePath, $id);
    }

    /**
     * Generate a new application ID.
     *
     * @return string
     */
    protected static function generateId()
    {
        // Generate a UUID v4 for the application ID
        return Str::uuid()->toString();
    }

    /**
     * Reset the stored application ID (mainly for testing).
     *
     * @return void
     */
    public static function reset()
    {
        Cache::forget(self::CACHE_KEY);

        $filesystem = new Filesystem();
        $storagePath = storage_path(self::STORAGE_PATH);

        if ($filesystem->exists($storagePath)) {
            $filesystem->delete($storagePath);
        }
    }
}