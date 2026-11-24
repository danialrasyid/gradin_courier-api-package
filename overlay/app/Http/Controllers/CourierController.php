<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCourierRequest;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    /**
     * GET /api/couriers
     *
     * Query string: search, level (e.g. 2,3), sort (name|registered_at),
     * direction (asc|desc), per_page, page. Sorted by name by default.
     */
    public function index(IndexCourierRequest $request): AnonymousResourceCollection
    {
        $couriers = Courier::query()
            ->search($request->validated('search'))
            ->levels($request->levels())
            ->sorted($request->sortField(), $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return CourierResource::collection($couriers);
    }

    /**
     * POST /api/couriers
     */
    public function store(StoreCourierRequest $request): JsonResponse
    {
        // refresh() loads the database defaults (level, is_active) into the response.
        $courier = Courier::create($request->validated())->refresh();

        return (new CourierResource($courier))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/couriers/{courier}
     */
    public function show(Courier $courier): CourierResource
    {
        return new CourierResource($courier);
    }

    /**
     * PUT|PATCH /api/couriers/{courier}
     */
    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->validated());

        return new CourierResource($courier->refresh());
    }

    /**
     * DELETE /api/couriers/{courier}
     */
    public function destroy(Courier $courier): Response
    {
        $courier->delete();

        return response()->noContent();
    }
}
