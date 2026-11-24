<?php

namespace App\Http\Requests;

use App\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates and normalises the query string of GET /api/couriers.
 */
class IndexCourierRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 15;
    public const MAX_PER_PAGE = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            // Comma separated levels between 1 and 5, e.g. "2,3".
            'level' => ['nullable', 'string', 'regex:/^[1-5](,[1-5])*$/'],
            'sort' => ['nullable', Rule::in(array_keys(Courier::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.regex' => 'The level must be a comma separated list of numbers between 1 and 5, e.g. 2,3.',
        ];
    }

    /**
     * @return list<int>
     */
    public function levels(): array
    {
        $level = $this->validated('level');

        if (blank($level)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', explode(',', $level))));
    }

    public function sortField(): string
    {
        return $this->validated('sort') ?? 'name';
    }

    /**
     * Name sorts A-Z by default, registration date sorts newest first by default.
     * The frontend can always override it with ?direction=asc|desc.
     */
    public function sortDirection(): string
    {
        return $this->validated('direction')
            ?? ($this->sortField() === 'registered_at' ? 'desc' : 'asc');
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
