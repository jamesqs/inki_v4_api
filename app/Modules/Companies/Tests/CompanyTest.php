<?php

namespace App\Modules\Companies\Tests;

use Tests\TestCase;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_Company_records(): void
    {
        // Arrange
        Company::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/Company');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}