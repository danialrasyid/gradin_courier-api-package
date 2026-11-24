<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'level' => 3,
            'vehicle_type' => 'motorcycle',
            'vehicle_plate_number' => 'L 1234 AB',
            'address' => 'Jl. Raya Surabaya No. 1',
            'is_active' => true,
        ], $overrides);
    }

    public function test_it_stores_a_courier_in_the_database(): void
    {
        $response = $this->postJson('/api/couriers', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.level', 3);

        $this->assertDatabaseHas('couriers', [
            'id' => $response->json('data.id'),
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'level' => 3,
            'vehicle_type' => 'motorcycle',
            'vehicle_plate_number' => 'L 1234 AB',
            'address' => 'Jl. Raya Surabaya No. 1',
        ]);
        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_optional_fields_can_be_left_out_and_defaults_are_applied(): void
    {
        $response = $this->postJson('/api/couriers', [
            'name' => 'Siti Aminah',
            'phone' => '+6281298765432',
            'level' => 1,
            'vehicle_type' => 'bicycle',
        ]);

        $response->assertCreated()->assertJsonPath('data.is_active', true);

        $courier = Courier::firstOrFail();
        $this->assertNull($courier->email);
        $this->assertTrue($courier->is_active);
    }

    public function test_it_requires_the_mandatory_fields(): void
    {
        $this->postJson('/api/couriers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'level', 'vehicle_type']);

        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_it_rejects_invalid_values(): void
    {
        $cases = [
            'name too long' => ['name', str_repeat('a', 256)],
            'name not a string' => ['name', ['array']],
            'phone with letters' => ['phone', 'abc12345678'],
            'phone too short' => ['phone', '0812345'],
            'phone too long' => ['phone', '0812345678901234567'],
            'invalid email' => ['email', 'not-an-email'],
            'level zero' => ['level', 0],
            'level above five' => ['level', 6],
            'level not a number' => ['level', 'abc'],
            'level with decimals' => ['level', 2.5],
            'unknown vehicle' => ['vehicle_type', 'boat'],
            'plate too long' => ['vehicle_plate_number', str_repeat('A', 16)],
            'address too long' => ['address', str_repeat('a', 1001)],
            'is_active not boolean' => ['is_active', 'maybe'],
        ];

        foreach ($cases as $label => [$field, $value]) {
            $this->postJson('/api/couriers', $this->validPayload([$field => $value]))
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_phone_and_email_must_be_unique(): void
    {
        Courier::factory()->create(['phone' => '081234567890', 'email' => 'budi@example.com']);

        $this->postJson('/api/couriers', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'email']);

        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_unknown_fields_are_ignored(): void
    {
        $response = $this->postJson('/api/couriers', $this->validPayload(['id' => 999, 'created_at' => '2000-01-01']));

        $response->assertCreated();
        $this->assertNotSame(999, $response->json('data.id'));
        $this->assertDatabaseMissing('couriers', ['created_at' => '2000-01-01 00:00:00']);
    }
}
