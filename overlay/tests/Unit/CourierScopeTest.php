<?php

namespace Tests\Unit;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the query scopes of the Courier model on their own, without going through HTTP.
 */
class CourierScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_scope_requires_every_keyword(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Budi Santoso']);

        $names = Courier::query()->search('  budi   agung ')->pluck('name')->all();

        $this->assertSame(['Budiono Hadi Agung'], $names);
    }

    public function test_search_scope_without_a_term_returns_everyone(): void
    {
        Courier::factory()->count(3)->create();

        $this->assertCount(3, Courier::query()->search(null)->get());
        $this->assertCount(3, Courier::query()->search('   ')->get());
    }

    public function test_search_scope_treats_wildcards_literally(): void
    {
        Courier::factory()->create(['name' => 'Andi_Putra']);
        Courier::factory()->create(['name' => 'Andi Putra']);
        Courier::factory()->create(['name' => '100% Express']);
        Courier::factory()->create(['name' => '100 Express']);

        $this->assertSame(['Andi_Putra'], Courier::query()->search('Andi_Putra')->pluck('name')->all());
        $this->assertSame(['100% Express'], Courier::query()->search('100%')->pluck('name')->all());
    }

    public function test_levels_scope_filters_and_ignores_an_empty_list(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->level($level)->create();
        }

        $this->assertEqualsCanonicalizing(
            [2, 3],
            Courier::query()->levels([2, 3])->pluck('level')->all()
        );
        $this->assertCount(5, Courier::query()->levels([])->get());
    }

    public function test_sorted_scope_falls_back_to_name_for_an_unknown_field(): void
    {
        Courier::factory()->create(['name' => 'Bravo']);
        Courier::factory()->create(['name' => 'Alpha']);

        $this->assertSame(
            ['Alpha', 'Bravo'],
            Courier::query()->sorted('does_not_exist', 'asc')->pluck('name')->all()
        );
    }
}
