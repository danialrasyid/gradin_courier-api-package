<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;

    public const MIN_LEVEL = 1;
    public const MAX_LEVEL = 5;

    /**
     * Sortable fields exposed to the API, mapped to the real column.
     * "registered_at" is the date the courier was registered (created_at).
     */
    public const SORTABLE = [
        'name' => 'name',
        'registered_at' => 'created_at',
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'level',
        'vehicle_type',
        'vehicle_plate_number',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'vehicle_type' => VehicleType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Search by name. Every word must appear somewhere in the name, in any order,
     * so "budi agung" matches "Budiono Hadi Agung".
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $keywords = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);
        $column = $query->getModel()->getTable().'.name';

        foreach ($keywords as $keyword) {
            // Escape LIKE wildcards so "%" and "_" typed by the user are matched literally.
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword);

            $query->whereRaw("{$column} LIKE ? ESCAPE '!'", ["%{$escaped}%"]);
        }

        return $query;
    }

    /**
     * Keep only couriers whose level is in the given list. An empty list means no filter.
     *
     * @param  list<int>  $levels
     */
    public function scopeLevels(Builder $query, array $levels): Builder
    {
        return $levels === [] ? $query : $query->whereIn('level', $levels);
    }

    /**
     * Order by one of the allowed sort fields. "id" is a tie-breaker so pagination is stable.
     */
    public function scopeSorted(Builder $query, string $sort, string $direction): Builder
    {
        $column = self::SORTABLE[$sort] ?? self::SORTABLE['name'];
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}
