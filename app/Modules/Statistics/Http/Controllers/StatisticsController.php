<?php

namespace App\Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Statistics\Http\Requests\PricingAnalysisRequest;
use App\Modules\Statistics\Http\Resources\PricingAnalysisResource;
use App\Modules\Statistics\Services\PricingAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    protected PricingAnalysisService $pricingService;

    public function __construct(PricingAnalysisService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Get target price analysis for given criteria
     */
    public function getPricingAnalysis(PricingAnalysisRequest $request): JsonResponse
    {
        try {
            $criteria = $request->validated();
            $analysis = $this->pricingService->calculateTargetPrice($criteria);

            return response()->json([
                'success' => true,
                'data' => new PricingAnalysisResource($analysis),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating pricing analysis',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get market trends for a location and category
     */
    public function getMarketTrends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'months' => 'nullable|integer|min:1|max:24'
        ]);

        try {
            // Resolve location_id (can be numeric ID or slug)
            $locationId = $this->resolveLocationId($validated['location_id']);

            if (!$locationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found',
                    'errors' => [
                        'location_id' => ['The selected location is invalid.']
                    ]
                ], 422);
            }

            $months = $validated['months'] ?? 12;
            $trends = $this->pricingService->getMarketTrends(
                $locationId,
                $validated['category_id'],
                $months
            );

            return response()->json([
                'success' => true,
                'data' => $trends,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving market trends',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get price distribution for a location and category
     */
    public function getPriceDistribution(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        try {
            // Resolve location_id (can be numeric ID or slug)
            $locationId = $this->resolveLocationId($validated['location_id']);

            if (!$locationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found',
                    'errors' => [
                        'location_id' => ['The selected location is invalid.']
                    ]
                ], 422);
            }

            $distribution = $this->pricingService->getPriceDistribution(
                $locationId,
                $validated['category_id']
            );

            return response()->json([
                'success' => true,
                'data' => $distribution,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving price distribution',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get comprehensive market insights
     */
    public function getMarketInsights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'attributes' => 'nullable|array',
            'attributes.*' => 'string|max:255',
        ]);

        try {
            // Resolve location_id (can be numeric ID or slug)
            $locationId = $this->resolveLocationId($validated['location_id']);

            if (!$locationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found',
                    'errors' => [
                        'location_id' => ['The selected location is invalid.']
                    ]
                ], 422);
            }

            $validated['location_id'] = $locationId;

            $analysis = $this->pricingService->calculateTargetPrice($validated);
            $trends = $this->pricingService->getMarketTrends(
                $validated['location_id'],
                $validated['category_id'],
                6 // Last 6 months for insights
            );
            $distribution = $this->pricingService->getPriceDistribution(
                $validated['location_id'],
                $validated['category_id']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'pricing_analysis' => new PricingAnalysisResource($analysis),
                    'market_trends' => $trends,
                    'price_distribution' => $distribution,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving market insights',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available attribute values for a location and category
     */
    public function getAvailableAttributes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        try {
            // Resolve location_id (can be numeric ID or slug)
            $locationId = $this->resolveLocationId($validated['location_id']);

            if (!$locationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found',
                    'errors' => [
                        'location_id' => ['The selected location is invalid.']
                    ]
                ], 422);
            }

            $validated['location_id'] = $locationId;
            $availableValues = $this->getAvailableAttributeValues($validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'location_id' => $locationId,
                    'category_id' => $validated['category_id'],
                    'available_attributes' => $availableValues,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving available attributes',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper method to get available attribute values
     */
    private function getAvailableAttributeValues(array $criteria): array
    {
        $query = \Illuminate\Support\Facades\DB::table('estates')
            ->where('published', true)
            ->whereNotNull('custom_attributes');

        if (isset($criteria['location_id'])) {
            $query->where('location_id', $criteria['location_id']);
        }

        if (isset($criteria['category_id'])) {
            $query->where('category_id', $criteria['category_id']);
        }

        $estates = $query->get(['custom_attributes']);
        $availableValues = [];

        foreach ($estates as $estate) {
            $attributes = json_decode($estate->custom_attributes, true);
            if (is_array($attributes)) {
                foreach ($attributes as $key => $value) {
                    if (!isset($availableValues[$key])) {
                        $availableValues[$key] = [];
                    }
                    if (!in_array($value, $availableValues[$key])) {
                        $availableValues[$key][] = $value;
                    }
                }
            }
        }

        return $availableValues;
    }

    /**
     * Resolve location ID from either numeric ID or slug/name
     */
    private function resolveLocationId($input): ?int
    {
        // If it's already a numeric ID, verify it exists
        if (is_numeric($input)) {
            $location = \App\Modules\Locations\Models\Location::find((int)$input);
            return $location ? $location->id : null;
        }

        // Otherwise, treat it as a slug or name (case-insensitive)
        $location = \App\Modules\Locations\Models\Location::where('slug', strtolower($input))
            ->orWhereRaw('LOWER(name) = ?', [strtolower($input)])
            ->first();

        return $location ? $location->id : null;
    }
}