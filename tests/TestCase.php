<?php

namespace Tests;

use JMac\Testing\Traits\AdditionalAssertions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// Models
// use Cms\Domain\Organizations\Organization;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, AdditionalAssertions,
        RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // $this->organization = Organization::factory()->create();

        // $this->property = Property::factory()->for($this->organization)->create();
    }
}
