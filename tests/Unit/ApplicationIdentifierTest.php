<?php

namespace LaravelMonitor\Tests\Unit;

use LaravelMonitor\Support\ApplicationIdentifier;
use LaravelMonitor\Tests\TestCase;

class ApplicationIdentifierTest extends TestCase
{
    public function test_application_id_is_stable_and_cached(): void
    {
        $identifier = new ApplicationIdentifier($this->app);
        $identifier->reset();

        $id1 = $identifier->get();
        $id2 = $identifier->get();

        $this->assertNotEmpty($id1);
        $this->assertEquals($id1, $id2);
    }
}
