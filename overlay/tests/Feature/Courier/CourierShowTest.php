<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_data_of_a_courier(): void
    {
        $courier = Courier::factory()->create([
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'level' => 3,
            'vehicle_type' => 'motorcycle',
            'vehicle_plate_number' => 'L 1234 AB',
            'address' => 'Jl. Raya Surabaya No. 1',
            'is_active' => true,
        ]);

        $this->getJson("/api/couriers/{$courier->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $courier->id)
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.phone', '081234567890')
            ->assertJsonPath('data.email', 'budi@example.com')
            ->assertJsonPath('data.level', 3)
            ->assertJsonPath('data.vehicle_type', 'motorcycle')
            ->assertJsonPath('data.vehicle_plate_number', 'L 1234 AB')
            ->assertJsonPath('data.address', 'Jl. Raya Surabaya No. 1')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => ['registered_at', 'updated_at']]);
    }

    public function test_it_returns_404_when_the_courier_does_not_exist(): void
    {
        $this->getJson('/api/couriers/999')->assertNotFound();
    }
}
