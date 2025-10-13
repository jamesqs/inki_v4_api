<?php

namespace App\Modules\Estates\Tests;

use Tests\TestCase;
use App\Modules\Estates\Models\Estate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EstateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_Estate_records(): void
    {
        // Arrange
        Estate::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/Estate');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}