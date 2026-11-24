<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_paginated_list_with_15_items_per_page_by_default(): void
    {
        Courier::factory()->count(20)->create();

        $response = $this->getJson('/api/couriers');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_it_respects_per_page_and_page(): void
    {
        Courier::factory()->count(25)->create();

        $response = $this->getJson('/api/couriers?per_page=10&page=3');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_is_sorted_by_name_ascending_by_default(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $response = $this->getJson('/api/couriers');

        $response->assertOk();
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $response->json('data.*.name'));
    }

    public function test_name_sort_direction_can_be_reversed(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $response = $this->getJson('/api/couriers?direction=desc');

        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], $response->json('data.*.name'));
    }

    public function test_it_can_be_sorted_by_registration_date_newest_first(): void
    {
        Courier::factory()->create(['name' => 'Oldest', 'created_at' => now()->subDays(3)]);
        Courier::factory()->create(['name' => 'Newest', 'created_at' => now()->subDay()]);
        Courier::factory()->create(['name' => 'Middle', 'created_at' => now()->subDays(2)]);

        $response = $this->getJson('/api/couriers?sort=registered_at');

        $response->assertOk();
        $this->assertSame(['Newest', 'Middle', 'Oldest'], $response->json('data.*.name'));
    }

    public function test_registration_date_sort_direction_can_be_overridden(): void
    {
        Courier::factory()->create(['name' => 'Oldest', 'created_at' => now()->subDays(3)]);
        Courier::factory()->create(['name' => 'Newest', 'created_at' => now()->subDay()]);
        Courier::factory()->create(['name' => 'Middle', 'created_at' => now()->subDays(2)]);

        $response = $this->getJson('/api/couriers?sort=registered_at&direction=asc');

        $this->assertSame(['Oldest', 'Middle', 'Newest'], $response->json('data.*.name'));
    }

    public function test_search_matches_every_word_in_any_part_of_the_name(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Agung Prasetyo']);
        Courier::factory()->create(['name' => 'Siti Aminah']);

        $response = $this->getJson('/api/couriers?search=budi+agung');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame(['Budiono Hadi Agung'], $response->json('data.*.name'));
    }

    public function test_search_with_one_word_is_case_insensitive(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Siti Aminah']);

        $response = $this->getJson('/api/couriers?search=BUDI');

        $this->assertSame(['Budi Santoso', 'Budiono Hadi Agung'], $response->json('data.*.name'));
    }

    public function test_search_with_no_match_returns_an_empty_list(): void
    {
        Courier::factory()->create(['name' => 'Siti Aminah']);

        $this->getJson('/api/couriers?search=nobody')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_can_filter_by_one_level(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->level($level)->create();
        }

        $response = $this->getJson('/api/couriers?level=4');

        $this->assertSame([4], $response->json('data.*.level'));
    }

    public function test_it_can_filter_by_several_levels(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->level($level)->create();
        }

        $response = $this->getJson('/api/couriers?level=2,3');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing([2, 3], $response->json('data.*.level'));
    }

    public function test_search_level_and_sort_can_be_combined(): void
    {
        Courier::factory()->level(2)->create(['name' => 'Budi Zed', 'created_at' => now()->subDays(2)]);
        Courier::factory()->level(3)->create(['name' => 'Budi Alpha', 'created_at' => now()->subDay()]);
        Courier::factory()->level(5)->create(['name' => 'Budi Level Five']);
        Courier::factory()->level(2)->create(['name' => 'Siti']);

        $byName = $this->getJson('/api/couriers?search=budi&level=2,3');
        $this->assertSame(['Budi Alpha', 'Budi Zed'], $byName->json('data.*.name'));

        $byDate = $this->getJson('/api/couriers?search=budi&level=2,3&sort=registered_at');
        $this->assertSame(['Budi Alpha', 'Budi Zed'], $byDate->json('data.*.name'));

        $byDateAsc = $this->getJson('/api/couriers?search=budi&level=2,3&sort=registered_at&direction=asc');
        $this->assertSame(['Budi Zed', 'Budi Alpha'], $byDateAsc->json('data.*.name'));
    }

    public function test_pagination_links_keep_the_query_string(): void
    {
        Courier::factory()->count(5)->create();

        $response = $this->getJson('/api/couriers?per_page=2&sort=registered_at');

        $this->assertStringContainsString('sort=registered_at', $response->json('links.next'));
        $this->assertStringContainsString('per_page=2', $response->json('links.next'));
    }

    public function test_it_returns_the_expected_fields_for_each_courier(): void
    {
        Courier::factory()->create();

        $this->getJson('/api/couriers')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id', 'name', 'phone', 'email', 'level', 'vehicle_type',
                    'vehicle_plate_number', 'address', 'is_active', 'registered_at', 'updated_at',
                ]],
                'links',
                'meta',
            ]);
    }

    public function test_it_rejects_invalid_query_parameters(): void
    {
        $cases = [
            'level' => ['6', '0', 'abc', '2,', ',2', '2;3'],
            'sort' => ['phone', 'id'],
            'direction' => ['up', 'ASCENDING'],
            'per_page' => ['0', '101', 'ten'],
        ];

        foreach ($cases as $field => $values) {
            foreach ($values as $value) {
                $this->getJson('/api/couriers?'.http_build_query([$field => $value]))
                    ->assertStatus(422)
                    ->assertJsonValidationErrors($field);
            }
        }
    }
}
