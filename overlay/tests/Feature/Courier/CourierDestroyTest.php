<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_a_courier_from_the_database(): void
    {
        $courier = Courier::factory()->create();
        $other = Courier::factory()->create();

        $this->deleteJson("/api/couriers/{$courier->id}")->assertNoContent();

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
        $this->assertDatabaseHas('couriers', ['id' => $other->id]);
        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_a_deleted_courier_can_no_longer_be_fetched(): void
    {
        $courier = Courier::factory()->create();

        $this->deleteJson("/api/couriers/{$courier->id}")->assertNoContent();

        $this->getJson("/api/couriers/{$courier->id}")->assertNotFound();
    }

    public function test_it_returns_404_when_the_courier_does_not_exist(): void
    {
        $this->deleteJson('/api/couriers/999')->assertNotFound();
    }
}
