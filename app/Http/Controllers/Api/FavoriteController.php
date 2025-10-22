<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserFavorite;
use App\Modules\Estates\Models\Estate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    /**
     * Get all favorited properties for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $favorites = UserFavorite::where('user_id', $user->id)
            ->with([
                'estate.location',
                'estate.category',
                'estate.user.profilePicture'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter out favorites where the estate has been deleted
        $favorites = $favorites->filter(function ($favorite) {
            return $favorite->estate !== null;
        });

        // Transform the favorites to include full estate details
        $data = $favorites->map(function ($favorite) {
            $estate = $favorite->estate;

            // Get first photo
            $photos = is_array($estate->photos) ? $estate->photos : [];
            $firstPhoto = !empty($photos) ? $photos[0] : null;

            // Get size from custom_attributes
            $customAttributes = is_array($estate->custom_attributes) ? $estate->custom_attributes : [];
            $size = $customAttributes['size'] ?? $customAttributes['terulet'] ?? null;

            return [
                'id' => $favorite->id,
                'user_id' => $favorite->user_id,
                'estate_id' => $favorite->estate_id,
                'created_at' => $favorite->created_at->toISOString(),
                'estate' => [
                    'id' => $estate->id,
                    'name' => $estate->name,
                    'slug' => $estate->slug,
                    'price' => $estate->price,
                    'currency' => $estate->currency ?? 'HUF',
                    'formatted_price' => $estate->formatted_price ?? number_format($estate->price, 0, ',', ' ') . ' ' . ($estate->currency ?? 'HUF'),
                    'listing_type' => $estate->listing_type,
                    'size' => $size,
                    'address' => $estate->address,
                    'zip' => $estate->zip,
                    'location' => $estate->location ? [
                        'id' => $estate->location->id,
                        'name' => $estate->location->name,
                    ] : null,
                    'category' => $estate->category ? [
                        'id' => $estate->category->id,
                        'name' => $estate->category->name,
                    ] : null,
                    'photo' => $firstPhoto ? [
                        'url' => $firstPhoto['url'] ?? $firstPhoto,
                        'name' => $firstPhoto['name'] ?? null,
                    ] : null,
                    'photos' => $photos,
                    'custom_attributes' => $estate->custom_attributes,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values(),
        ]);
    }

    /**
     * Add a property to favorites
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estate_id' => 'required|integer|exists:estates,id',
        ]);

        $user = $request->user();

        // Check if already favorited
        $existing = UserFavorite::where('user_id', $user->id)
            ->where('estate_id', $validated['estate_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Property is already in your favorites',
            ], 409);
        }

        // Create favorite
        $favorite = UserFavorite::create([
            'user_id' => $user->id,
            'estate_id' => $validated['estate_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Property added to favorites',
            'data' => [
                'id' => $favorite->id,
                'user_id' => $favorite->user_id,
                'estate_id' => $favorite->estate_id,
                'created_at' => $favorite->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Remove a property from favorites
     */
    public function destroy(Request $request, $estateId): JsonResponse
    {
        $user = $request->user();

        $favorite = UserFavorite::where('user_id', $user->id)
            ->where('estate_id', $estateId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Favorite not found',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Property removed from favorites',
        ]);
    }

    /**
     * Check if a specific property is favorited
     */
    public function check(Request $request, $estateId): JsonResponse
    {
        $user = $request->user();

        $isFavorite = UserFavorite::where('user_id', $user->id)
            ->where('estate_id', $estateId)
            ->exists();

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
        ]);
    }
}
