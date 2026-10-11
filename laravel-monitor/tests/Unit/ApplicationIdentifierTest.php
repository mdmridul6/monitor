<?php

namespace Vendor\LaravelMonitor\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Vendor\LaravelMonitor\Support\ApplicationIdentifier;
use Tests\TestCase;

class ApplicationIdentifierTest extends TestCase
{
    public function test_application_id_is_generated_when_none_exists()
    {
        // Clear any existing cache and storage
        Cache::forget(ApplicationIdentifier::CACHE_KEY);
        $filesystem = new Filesystem();
        $storagePath = storage_path(ApplicationIdentifier::STORAGE_PATH);
        if ($filesystem->exists($storagePath)) {
            $filesystem->delete($storagePath);
        }

        $id1 = ApplicationIdentifier::getId();
        $id2 = ApplicationIdentifier::getId();

        $this->assertNotEmpty($id1);
        $this->assertEquals($id1, $id2); // Should be consistent
        $this->assertEquals(36, strlen($id1)); // UUID length
    }

    public function test_application_id_is_cached()
    {
        Cache::forget(ApplicationIdentifier::CACHE_KEY);
        $filesystem = new Filesystem();
        $storagePath = storage_path(ApplicationIdentifier::STORAGE_PATH);
        if ($filesystem->exists($storagePath)) {
            $filesystem->delete($storagePath);
        }

        // First call should generate and store
        $id1 = ApplicationIdentifier::getId();

        // Second call should return cached value
        $id2 = ApplicationIdentifier::getId();

        $this->assertEquals($id1, $id2);
        $this->assertTrue(Cache::has(ApplicationIdentifier::CACHE_KEY));
    }

    public function test_application_id_is_persisted_to_storage()
    {
        Cache::forget(ApplicationIdentifier::CACHE_KEY);
        $filesystem = new Filesystem();
        $storagePath = storage_path(ApplicationIdentifier::STORAGE_PATH);
        if ($filesystem->exists($storagePath)) {
            $filesystem->delete($storagePath);
        }

        $id = ApplicationIdentifier::getId();

        $filesystem = new Filesystem();
        $storagePath = storage_path(ApplicationIdentifier::STORAGE_PATH);
        $this->assertTrue($filesystem->exists($storagePath));
        $this->assertEquals($id, trim($filesystem->get($storagePath)));
    }

    public function test_application_id_can_be_reset()
    {
        $id1 = ApplicationIdentifier::getId();
        ApplicationIdentifier::reset();
        $id2 = ApplicationIdentifier::getId();

        $this->assertNotEquals($id1, $id2);
        $this->assertFalse(Cache::has(ApplicationIdentifier::CACHE_KEY));
        $filesystem = new Filesystem();
        $storagePath = storage_path(ApplicationIdentifier::STORAGE_PATH);
        $this->assertFalse($filesystem->exists($storagePath));
    }
}