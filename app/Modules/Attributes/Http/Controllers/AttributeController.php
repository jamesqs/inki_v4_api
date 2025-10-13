<?php

namespace App\Modules\Attributes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attributes\Models\Attribute;
use App\Modules\Attributes\Http\Requests\AttributeRequest;
use App\Modules\Attributes\Http\Resources\AttributeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Attribute::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Attributes = $query->get();
            return AttributeResource::collection($Attributes);
        }

        $Attributes = $query->paginate();
        return AttributeResource::collection($Attributes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttributeRequest $request): AttributeResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $attribute = Attribute::create($validated);
        return new AttributeResource($attribute);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attribute $Attribute): AttributeResource
    {
        return new AttributeResource($Attribute);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttributeRequest $request, Attribute $attribute): AttributeResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $attribute->update($validated);
        return new AttributeResource($attribute);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attribute $Attribute): Response
    {
        $Attribute->delete();
        return response()->noContent();
    }
}
