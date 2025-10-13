<?php

namespace App\Modules\Categories\Tests;

use Tests\TestCase;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_Category_records(): void
    {
        // Arrange
        Category::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/Category');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}