<?php

namespace App\Modules\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attributes\Models\Attribute;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Http\Requests\CategoryRequest;
use App\Modules\Categories\Http\Requests\CategoryAttributeRequest;
use App\Modules\Categories\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Category::query();

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Categories = $query->get();
            return CategoryResource::collection($Categories);
        }

        $Categories = $query->paginate();
        return CategoryResource::collection($Categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): CategoryResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = Category::create($validated);
        return new CategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $Category): CategoryResource
    {
        return new CategoryResource($Category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);
        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $Category): Response
    {
        $Category->delete();
        return response()->noContent();
    }

    /**
     * Get attributes attached to a category
     */
    public function getAttributes(Category $category): JsonResponse
    {
        $attributes = $category->attributes()->get();

        return response()->json([
            'data' => $attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'display_name' => $attribute->display_name,
                    'type' => $attribute->type,
                    'options' => $attribute->options,
                    'required' => $attribute->pivot->required,
                    'order' => $attribute->pivot->order,
                    'created_at' => $attribute->pivot->created_at,
                    'updated_at' => $attribute->pivot->updated_at,
                ];
            })
        ]);
    }

    /**
     * Attach attributes to a category
     */
    public function attachAttributes(CategoryAttributeRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        foreach ($validated['attributes'] as $attributeData) {
            $category->attributes()->attach($attributeData['attribute_id'], [
                'required' => $attributeData['required'] ?? false,
                'order' => $attributeData['order'] ?? 0,
            ]);
        }

        return response()->json(['message' => 'Attributes attached successfully']);
    }

    /**
     * Detach attributes from a category
     */
    public function detachAttributes(Category $category): JsonResponse
    {
        $attributeIds = request('attribute_ids', []);

        if (empty($attributeIds)) {
            $category->attributes()->detach();
        } else {
            $category->attributes()->detach($attributeIds);
        }

        return response()->json(['message' => 'Attributes detached successfully']);
    }

    /**
     * Update attribute pivot data for a category
     */
    public function updateAttributePivot(CategoryAttributeRequest $request, Category $category, Attribute $attribute): JsonResponse
    {
        $validated = $request->validated();
        $attributeData = $validated['attributes'][0];

        $category->attributes()->updateExistingPivot($attribute->id, [
            'required' => $attributeData['required'] ?? false,
            'order' => $attributeData['order'] ?? 0,
        ]);

        return response()->json(['message' => 'Attribute updated successfully']);
    }

    /**
     * Sync attributes with a category (replace all existing attachments)
     */
    public function syncAttributes(CategoryAttributeRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        $syncData = [];
        foreach ($validated['attributes'] as $attributeData) {
            $syncData[$attributeData['attribute_id']] = [
                'required' => $attributeData['required'] ?? false,
                'order' => $attributeData['order'] ?? 0,
            ];
        }

        $category->attributes()->sync($syncData);

        return response()->json(['message' => 'Attributes synchronized successfully']);
    }
}
