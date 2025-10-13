<?php

namespace App\Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\Location;
use App\Modules\Locations\Http\Requests\LocationRequest;
use App\Modules\Locations\Http\Resources\LocationResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Location::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Locations = $query->get();
            return LocationResource::collection($Locations);
        }

        $Locations = $query->paginate();
        return LocationResource::collection($Locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LocationRequest $request): LocationResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $location = Location::create($validated);
        return new LocationResource($location);
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $Location): LocationResource
    {
        return new LocationResource($Location);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LocationRequest $request, Location $location): LocationResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $location->update($validated);
        return new LocationResource($location);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $Location): Response
    {
        $Location->delete();
        return response()->noContent();
    }

    /**
     * Search locations by name with autocomplete functionality.
     *
     * @return JsonResponse
     */
    public function search(): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make(request()->all(), [
            'q' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = request('q');
        $limit = request('limit', 50);

        // Build the search query with relevance ordering
        $locations = Location::query()
            // Case-insensitive search using LOWER
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%'])
            // Order by importance first (higher importance = more relevant)
            ->orderByRaw('importance DESC')
            // Order by relevance: exact match first, then starts-with, then contains
            ->orderByRaw("
                CASE
                    WHEN LOWER(name) = ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    ELSE 3
                END
            ", [
                strtolower($query),
                strtolower($query) . '%'
            ])
            // Finally order by name for consistency
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => LocationResource::collection($locations)
        ]);
    }
}
