<?php

namespace App\Modules\Attributes\Tests;

use Tests\TestCase;
use App\Modules\Attributes\Models\Attribute;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttributeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_Attribute_records(): void
    {
        // Arrange
        Attribute::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/Attribute');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}