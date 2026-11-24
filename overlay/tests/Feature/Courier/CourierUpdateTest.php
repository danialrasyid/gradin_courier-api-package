<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_a_courier_in_the_database(): void
    {
        $courier = Courier::factory()->create(['name' => 'Old Name', 'level' => 1, 'vehicle_type' => 'bicycle']);

        $this->putJson("/api/couriers/{$courier->id}", [
            'name' => 'New Name',
            'level' => 4,
            'vehicle_type' => 'car',
            'address' => 'Jl. Baru No. 2',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.level', 4)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'New Name',
            'level' => 4,
            'vehicle_type' => 'car',
            'address' => 'Jl. Baru No. 2',
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('couriers', ['id' => $courier->id, 'name' => 'Old Name']);
    }

    public function test_a_partial_update_keeps_the_other_fields(): void
    {
        $courier = Courier::factory()->create(['name' => 'Budi', 'phone' => '081234567890', 'level' => 2]);

        $this->patchJson("/api/couriers/{$courier->id}", ['level' => 5])->assertOk();

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Budi',
            'phone' => '081234567890',
            'level' => 5,
        ]);
    }

    public function test_optional_fields_can_be_cleared(): void
    {
        $courier = Courier::factory()->create(['email' => 'budi@example.com', 'address' => 'Somewhere']);

        $this->putJson("/api/couriers/{$courier->id}", ['email' => null, 'address' => null])->assertOk();

        $courier->refresh();
        $this->assertNull($courier->email);
        $this->assertNull($courier->address);
    }

    public function test_it_rejects_invalid_values_and_leaves_the_database_untouched(): void
    {
        $courier = Courier::factory()->create(['name' => 'Budi', 'level' => 2, 'vehicle_type' => 'bicycle']);

        $cases = [
            'name' => null,
            'phone' => 'not-a-phone',
            'email' => 'not-an-email',
            'level' => 9,
            'vehicle_type' => 'boat',
            'is_active' => 'maybe',
        ];

        foreach ($cases as $field => $value) {
            $this->putJson("/api/couriers/{$courier->id}", [$field => $value])
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Budi',
            'level' => 2,
            'vehicle_type' => 'bicycle',
        ]);
    }

    public function test_a_courier_can_keep_its_own_phone_and_email(): void
    {
        $courier = Courier::factory()->create(['phone' => '081234567890', 'email' => 'budi@example.com']);

        $this->putJson("/api/couriers/{$courier->id}", [
            'name' => 'Budi Updated',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
        ])->assertOk();

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'name' => 'Budi Updated']);
    }

    public function test_it_cannot_take_the_phone_or_email_of_another_courier(): void
    {
        Courier::factory()->create(['phone' => '081111111111', 'email' => 'other@example.com']);
        $courier = Courier::factory()->create(['phone' => '082222222222', 'email' => 'me@example.com']);

        $this->putJson("/api/couriers/{$courier->id}", [
            'phone' => '081111111111',
            'email' => 'other@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'email']);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'phone' => '082222222222',
            'email' => 'me@example.com',
        ]);
    }

    public function test_it_returns_404_when_the_courier_does_not_exist(): void
    {
        $this->putJson('/api/couriers/999', ['name' => 'Nobody'])->assertNotFound();
    }
}
