<?php

namespace App\Modules\Estates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estates\Models\Estate;
use App\Modules\Estates\Http\Requests\EstateRequest;
use App\Modules\Estates\Http\Resources\EstateResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EstateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Estate::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Estates = $query->get();
            return EstateResource::collection($Estates);
        }

        $Estates = $query->paginate();
        return EstateResource::collection($Estates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EstateRequest $request): \Illuminate\Http\JsonResponse|EstateResource
    {
        $validated = $request->validated();

        // Check if estate with same name and price already exists
        if (Estate::where('name', $validated['name'])
            ->where('price', $validated['price'])
            ->exists()) {
            return response()->json([
                'message' => 'Estate already exists with this name and price on the same day.'
            ], 422);
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' .
                $validated['price'] . '-' .
                now()->format('Ymd');
        }

        $estate = Estate::create($validated);
        return new EstateResource($estate);
    }

    /**
     * Display the specified resource.
     */
    public function show(Estate $Estate): EstateResource
    {
        return new EstateResource($Estate);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EstateRequest $request, Estate $Estate): EstateResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' .
                $validated['price'] . '-' .
                now()->format('Ymd');
        }

        $Estate->update($validated);
        return new EstateResource($Estate);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Estate $Estate): Response
    {
        $Estate->delete();
        return response()->noContent();
    }
}
