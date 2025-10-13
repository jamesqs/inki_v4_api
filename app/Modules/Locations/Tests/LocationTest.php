<?php

namespace App\Modules\Locations\Tests;

use Tests\TestCase;
use App\Modules\Locations\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_Location_records(): void
    {
        // Arrange
        Location::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/Location');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}