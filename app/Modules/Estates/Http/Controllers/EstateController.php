<?php

namespace App\Modules\Estates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estates\Models\Estate;
use App\Modules\Estates\Http\Requests\EstateRequest;
use App\Modules\Estates\Http\Resources\EstateResource;
use Illuminate\Http\Request;
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
        // Public index only shows approved estates
        $query = Estate::query()
            ->approved()
            ->with(['category', 'location', 'user']);

        // Category filtering - support both ID and slug
        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        } elseif (request()->has('category_slug')) {
            // Find category by slug and filter
            $category = \App\Modules\Categories\Models\Category::where('slug', request('category_slug'))->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Location filtering - support both ID and slug
        if (request()->has('location_id')) {
            $query->where('location_id', request('location_id'));
        } elseif (request()->has('location_slug')) {
            // Find location by slug and filter
            $location = \App\Modules\Locations\Models\Location::where('slug', request('location_slug'))->first();
            if ($location) {
                $query->where('location_id', $location->id);
            }
        }

        // Listing type filtering (sale/rent)
        if (request()->has('listing_type')) {
            $query->where('listing_type', request('listing_type'));
        }

        // Price filtering
        if (request()->has('price_min')) {
            $query->where('price', '>=', request('price_min'));
        }
        if (request()->has('price_max')) {
            $query->where('price', '<=', request('price_max'));
        }

        // Area filtering (stored in custom_attributes as 'alapterulet')
        if (request()->has('area_min')) {
            $query->whereRaw("CAST(JSON_EXTRACT(custom_attributes, '$.alapterulet') AS UNSIGNED) >= ?", [request('area_min')]);
        }
        if (request()->has('area_max')) {
            $query->whereRaw("CAST(JSON_EXTRACT(custom_attributes, '$.alapterulet') AS UNSIGNED) <= ?", [request('area_max')]);
        }

        // Bedrooms filtering (stored as 'egesz-szobak-szama' - full rooms)
        if (request()->has('bedrooms')) {
            $query->whereRaw("CAST(JSON_EXTRACT(custom_attributes, '$.\"egesz-szobak-szama\"') AS UNSIGNED) >= ?", [request('bedrooms')]);
        }

        // Bathrooms filtering (stored as 'furdoszobak-szama')
        if (request()->has('bathrooms')) {
            $query->whereRaw("CAST(JSON_EXTRACT(custom_attributes, '$.\"furdoszobak-szama\"') AS UNSIGNED) >= ?", [request('bathrooms')]);
        }

        // Total rooms filtering (sum of full + half rooms)
        if (request()->has('rooms')) {
            $query->whereRaw("(CAST(JSON_EXTRACT(custom_attributes, '$.\"egesz-szobak-szama\"') AS UNSIGNED) + CAST(JSON_EXTRACT(custom_attributes, '$.\"fel-szobak-szama\"') AS UNSIGNED)) >= ?", [request('rooms')]);
        }

        // Custom attributes filtering
        // Example: attributes[alapterulet][min] = 50
        if (request()->has('attributes')) {
            foreach (request('attributes') as $attrKey => $attrValue) {
                if (is_array($attrValue)) {
                    // Range filter (min/max)
                    if (isset($attrValue['min'])) {
                        $query->whereRaw("CAST(JSON_EXTRACT(custom_attributes, '$.{$attrKey}') AS UNSIGNED) >= ?", [$attrValue['min']]);
                    }
                    if (isset($attrValue['max'])) {
                        $query->whereRaw("CAST(JSON_EXTRACT(custom_attributes, '$.{$attrKey}') AS UNSIGNED) <= ?", [$attrValue['max']]);
                    }
                } else {
                    // Exact match
                    $query->whereRaw("JSON_EXTRACT(custom_attributes, '$.{$attrKey}') = ?", [$attrValue]);
                }
            }
        }

        // If you need to search by specific fields
        if ($name = request('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        // Sorting - support both new format (sort) and old format (sort_by + sort_order)
        if (request()->has('sort')) {
            // New format: sort=newest, sort=price_asc, etc.
            $sortMap = [
                'newest' => ['created_at', 'desc'],
                'oldest' => ['created_at', 'asc'],
                'price_asc' => ['price', 'asc'],
                'price_desc' => ['price', 'desc'],
                'area_asc' => ['alapterulet_numeric', 'asc'],
                'area_desc' => ['alapterulet_numeric', 'desc'],
            ];

            $sortValue = request('sort', 'newest');
            [$sortBy, $sortOrder] = $sortMap[$sortValue] ?? ['created_at', 'desc'];

            // For area sorting, we need to sort by the JSON field
            if (in_array($sortValue, ['area_asc', 'area_desc'])) {
                $query->orderByRaw("CAST(JSON_EXTRACT(custom_attributes, '$.alapterulet') AS UNSIGNED) {$sortOrder}");
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            // Old format: sort_by=price&sort_order=asc
            $sortBy = request('sort_by', 'created_at');
            $sortOrder = request('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
        }

        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            $Estates = $query->get();
            return EstateResource::collection($Estates);
        }

        // Pagination
        $perPage = request('per_page', 20);
        $Estates = $query->paginate($perPage);
        return EstateResource::collection($Estates);
    }

    /**
     * Get authenticated user's estates
     */
    public function myEstates(): \Illuminate\Http\JsonResponse
    {
        $user = request()->user();

        $estates = Estate::where('user_id', $user->id)
            ->with(['category', 'location', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'estates' => $estates,
            'total' => $estates->count()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EstateRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validated();

            // Add authenticated user ID
            $validated['user_id'] = $request->user()->id;

            // Use 'title' as 'name' for backward compatibility
            if (isset($validated['title']) && !isset($validated['name'])) {
                $validated['name'] = $validated['title'];
            }

            // Store address data in both fields for compatibility
            if (isset($validated['address'])) {
                $validated['address_data'] = $validated['address'];
                $validated['zip'] = $validated['address']['zip'] ?? null;
                $validated['address'] = $validated['address']['street'] ?? null;
            }

            // Store attributes in custom_attributes field
            if (isset($validated['attributes'])) {
                $validated['custom_attributes'] = $validated['attributes'];
            }

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
            }

            // Always create as draft - users must submit for review
            $validated['status'] = 'draft';

            // Create estate
            $estate = Estate::create($validated);

            // Load relationships
            $estate->load(['category', 'location', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Ingatlan sikeresen létrehozva piszkozatként!',
                'property' => $estate
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Estate creation error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Hiba történt az ingatlan létrehozása során.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($estate): EstateResource
    {
        $estate = Estate::with(['category', 'location', 'user'])
            ->findOrFail($estate);

        // Only show approved estates to public
        // (unless the user is the owner - checked in resource)
        if ($estate->status !== 'approved') {
            $user = request()->user();
            if (!$user || $user->id !== $estate->user_id) {
                abort(404, 'Estate not found');
            }
        }

        return new EstateResource($estate);
    }

    /**
     * Get single estate for authenticated user (for editing)
     */
    public function showForUser($id): \Illuminate\Http\JsonResponse
    {
        $user = request()->user();

        $estate = Estate::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['category', 'location', 'user'])
            ->firstOrFail();

        return response()->json($estate);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EstateRequest $request, $estateId): \Illuminate\Http\JsonResponse
    {
        // Fetch the estate by ID
        $estate = Estate::findOrFail($estateId);

        // Verify ownership
        if ($estate->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod módosítani ezt a hirdetést.'
            ], 403);
        }

        try {
            $validated = $request->validated();

            // Protect status changes - users can't change approved/rejected/archived status
            if (in_array($estate->status, ['approved', 'rejected', 'archived'])) {
                // If trying to change status to something other than current
                if (isset($validated['status']) && $validated['status'] !== $estate->status) {
                    // Prevent unauthorized status change - keep original status
                    $validated['status'] = $estate->status;
                }
            }

            // Users can only set status to draft or pending_review (unless keeping current status)
            if (isset($validated['status']) && !in_array($validated['status'], ['draft', 'pending_review', $estate->status])) {
                $validated['status'] = $estate->status; // Keep original
            }

            // Use 'title' as 'name' for backward compatibility
            if (isset($validated['title']) && !isset($validated['name'])) {
                $validated['name'] = $validated['title'];
            }

            // Store address data in both fields for compatibility
            if (isset($validated['address'])) {
                $validated['address_data'] = $validated['address'];
                $validated['zip'] = $validated['address']['zip'] ?? null;
                $validated['address'] = $validated['address']['street'] ?? null;
            }

            // Store attributes in custom_attributes field
            if (isset($validated['attributes'])) {
                $validated['custom_attributes'] = $validated['attributes'];
            }

            // Update estate
            $estate->update($validated);

            // Load relationships
            $estate->load(['category', 'location', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Hirdetés sikeresen frissítve!',
                'property' => $estate
            ]);

        } catch (\Exception $e) {
            \Log::error('Estate update error: ' . $e->getMessage(), [
                'estate_id' => $estate->id,
                'user_id' => $request->user()->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Hiba történt a frissítés során.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Submit estate for admin review
     */
    public function submitForReview(Request $request, $estateId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $estate = Estate::findOrFail($estateId);

        // Verify ownership
        if ($estate->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nincs jogosultságod ehhez a művelethez.',
            ], 403);
        }

        // Check if can be submitted
        if (!$estate->canBeSubmitted()) {
            $errors = [];

            if ($estate->status !== 'draft') {
                $errors[] = 'Csak piszkozat állapotú hirdetések küldhetők felülvizsgálatra.';
            }

            if (!$estate->isComplete()) {
                if (empty($estate->name)) $errors[] = 'A hirdetés címe kötelező.';
                if (empty($estate->description)) $errors[] = 'A leírás kötelező.';
                if (strlen($estate->description) < 50) $errors[] = 'A leírásnak legalább 50 karakter hosszúnak kell lennie.';
                if (empty($estate->price)) $errors[] = 'Az ár kötelező.';
                if (empty($estate->listing_type)) $errors[] = 'A hirdetés típusa kötelező.';
                if (empty($estate->location_id)) $errors[] = 'A helyszín kötelező.';
                if (empty($estate->category_id)) $errors[] = 'A kategória kötelező.';
                if (empty($estate->photos) || count($estate->photos) === 0) $errors[] = 'Legalább egy fénykép szükséges.';
            }

            return response()->json([
                'success' => false,
                'message' => 'A hirdetés nem küldhető be felülvizsgálatra.',
                'errors' => $errors,
            ], 422);
        }

        // Submit for review
        if ($estate->submitForReview()) {
            return response()->json([
                'success' => true,
                'message' => 'Hirdetés sikeresen beküldve felülvizsgálatra!',
                'property' => $estate->fresh(['category', 'location', 'user']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hiba történt a beküldés során.',
        ], 500);
    }

    /**
     * Cancel review submission
     */
    public function cancelReview(Request $request, $estateId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $estate = Estate::findOrFail($estateId);

        // Verify ownership
        if ($estate->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nincs jogosultságod ehhez a művelethez.',
            ], 403);
        }

        // Check if can cancel
        if ($estate->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'Csak felülvizsgálat alatt álló hirdetések visszavonhatók.',
            ], 422);
        }

        // Cancel review
        if ($estate->cancelReview()) {
            return response()->json([
                'success' => true,
                'message' => 'Felülvizsgálat sikeresen visszavonva!',
                'property' => $estate->fresh(['category', 'location', 'user']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hiba történt a visszavonás során.',
        ], 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($estateId): Response
    {
        $estate = Estate::findOrFail($estateId);
        $estate->delete();
        return response()->noContent();
    }

    /**
     * Upload photos for an estate
     */
    public function uploadPhotos(\Illuminate\Http\Request $request, $estateId): \Illuminate\Http\JsonResponse
    {
        // Fetch the estate by ID
        $estate = Estate::findOrFail($estateId);

        // Verify ownership
        if ($estate->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod módosítani ezt a hirdetést.'
            ], 403);
        }

        $request->validate([
            'photos' => 'required|array|max:20',
            'photos.*' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:102400', // 100MB max per image
        ]);

        try {
            $uploadedPhotos = [];
            $photos = $estate->photos ?? [];

            foreach ($request->file('photos') as $photo) {
                // Upload to media system
                $mediaController = new \App\Modules\Media\Http\Controllers\MediaController();

                // Create a new request for media upload
                $mediaRequest = new \Illuminate\Http\Request();
                $mediaRequest->files->set('file', $photo);
                $mediaRequest->merge([
                    'collection' => 'estate_images',
                    'mediable_type' => Estate::class,
                    'mediable_id' => $estate->id
                ]);
                $mediaRequest->setUserResolver($request->getUserResolver());

                $response = $mediaController->upload($mediaRequest);
                $responseData = json_decode($response->getContent(), true);

                if ($responseData['success']) {
                    $uploadedPhotos[] = $responseData['data'];
                    $photos[] = [
                        'id' => $responseData['data']['id'],
                        'url' => $responseData['data']['url'],
                        'name' => $responseData['data']['name'],
                        'order' => count($photos)
                    ];
                }
            }

            // Update estate photos
            $estate->update(['photos' => $photos]);
            $estate->load(['category', 'location', 'user']);

            return response()->json([
                'success' => true,
                'message' => count($uploadedPhotos) . ' fotó sikeresen feltöltve!',
                'photos' => $photos,
                'property' => $estate
            ]);

        } catch (\Exception $e) {
            \Log::error('Photo upload error: ' . $e->getMessage(), [
                'estate_id' => $estate->id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'message' => 'Hiba történt a fotók feltöltése során.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Delete a photo from an estate
     */
    public function deletePhoto(\Illuminate\Http\Request $request, $estateId, $photoId): \Illuminate\Http\JsonResponse
    {
        // Fetch the estate by ID
        $estate = Estate::findOrFail($estateId);

        // Verify ownership
        if ($estate->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod módosítani ezt a hirdetést.'
            ], 403);
        }

        try {
            $photos = $estate->photos ?? [];

            // Find and remove the photo
            $photos = array_values(array_filter($photos, function($photo) use ($photoId) {
                return $photo['id'] != $photoId;
            }));

            // Reorder remaining photos
            foreach ($photos as $index => &$photo) {
                $photo['order'] = $index;
            }

            // Update estate
            $estate->update(['photos' => $photos]);

            // Delete from media system
            $media = \App\Modules\Media\Models\Media::find($photoId);
            if ($media) {
                $media->forceDelete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Fotó sikeresen törölve!',
                'photos' => $photos
            ]);

        } catch (\Exception $e) {
            \Log::error('Photo delete error: ' . $e->getMessage(), [
                'estate_id' => $estate->id,
                'photo_id' => $photoId
            ]);

            return response()->json([
                'message' => 'Hiba történt a fotó törlése során.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Reorder estate photos
     */
    public function reorderPhotos(\Illuminate\Http\Request $request, $estateId): \Illuminate\Http\JsonResponse
    {
        // Fetch the estate by ID
        $estate = Estate::findOrFail($estateId);

        // Verify ownership
        if ($estate->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod módosítani ezt a hirdetést.'
            ], 403);
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|integer',
            'photos.*.order' => 'required|integer'
        ]);

        try {
            $photos = $request->photos;

            // Sort by order
            usort($photos, function($a, $b) {
                return $a['order'] - $b['order'];
            });

            // Update estate
            $estate->update(['photos' => $photos]);

            return response()->json([
                'success' => true,
                'message' => 'Fotók sorrendje frissítve!',
                'photos' => $photos
            ]);

        } catch (\Exception $e) {
            \Log::error('Photo reorder error: ' . $e->getMessage(), [
                'estate_id' => $estate->id
            ]);

            return response()->json([
                'message' => 'Hiba történt a sorrend frissítése során.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Get all estates with optional status filter (Admin only)
     */
    public function adminIndex(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Estate::query()
            ->with(['category', 'location', 'user.profilePicture']);

        // Filter by status if provided
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        // Get all estates (no pagination for counts)
        $estates = $query->get();

        return response()->json([
            'success' => true,
            'data' => $estates,
            'total' => $estates->count(),
        ]);
    }

    /**
     * Show single estate for admin (Admin only)
     */
    public function showForAdmin($id): \Illuminate\Http\JsonResponse
    {
        $estate = Estate::where('id', $id)
            ->with(['category', 'location', 'user.profilePicture', 'reviewer'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'estate' => $estate
        ]);
    }

    /**
     * Get all estates pending review (Admin only)
     */
    public function getPendingReview(Request $request): \Illuminate\Http\JsonResponse
    {
        $estates = Estate::pendingReview()
            ->with(['category', 'location', 'user.profilePicture'])
            ->orderBy('submitted_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $estates,
            'total' => $estates->count(),
        ]);
    }

    /**
     * Approve an estate (Admin only)
     */
    public function approve(Request $request, $estateId): \Illuminate\Http\JsonResponse
    {
        $admin = $request->user();
        $estate = Estate::findOrFail($estateId);

        if ($estate->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending estates can be approved.',
            ], 422);
        }

        if ($estate->approve($admin->id)) {
            return response()->json([
                'success' => true,
                'message' => 'Estate approved successfully!',
                'property' => $estate->fresh(['category', 'location', 'user', 'reviewer']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to approve estate.',
        ], 500);
    }

    /**
     * Reject an estate with reason (Admin only)
     */
    public function reject(Request $request, $estateId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000',
        ]);

        $admin = $request->user();
        $estate = Estate::findOrFail($estateId);

        if ($estate->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending estates can be rejected.',
            ], 422);
        }

        if ($estate->reject($admin->id, $validated['rejection_reason'])) {
            return response()->json([
                'success' => true,
                'message' => 'Estate rejected successfully!',
                'property' => $estate->fresh(['category', 'location', 'user', 'reviewer']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reject estate.',
        ], 500);
    }
}
