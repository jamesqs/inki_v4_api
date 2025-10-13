<?php

namespace App\Modules\Counties\Tests;

use Tests\TestCase;
use App\Modules\Counties\Models\County;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CountyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_County_records(): void
    {
        // Arrange
        County::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/County');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}