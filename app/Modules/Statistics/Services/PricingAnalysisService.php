<?php

namespace App\Modules\Statistics\Services;

use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;
use Illuminate\Support\Facades\DB;

class PricingAnalysisService
{
    /**
     * Calculate target price based on location, category, and custom attributes
     */
    public function calculateTargetPrice(array $criteria): array
    {
        // Start with base query using DB builder to avoid potential Eloquent issues
        $query = DB::table('estates')
            ->where('published', true)
            ->whereNotNull('price')
            ->where('price', '>', 0);

        // Apply location filter
        if (isset($criteria['location_id'])) {
            $query->where('location_id', $criteria['location_id']);
        }

        // Apply category filter
        if (isset($criteria['category_id'])) {
            $query->where('category_id', $criteria['category_id']);
        }

        // Apply custom attributes filter
        if (isset($criteria['attributes']) && is_array($criteria['attributes'])) {
            foreach ($criteria['attributes'] as $attributeName => $value) {
                $query->whereJsonContains('custom_attributes->' . $attributeName, $value);
            }
        }

        // Include both active and recently sold properties (within 6 months)
        $query->where(function ($q) {
            $q->where('sold', false)
              ->orWhere(function ($subQ) {
                  $subQ->where('sold', true)
                       ->where('updated_at', '>=', now()->subMonths(6));
              });
        });

        $estates = $query->get(['price', 'sold', 'updated_at']);

        if ($estates->isEmpty()) {
            // Try to find similar properties with relaxed criteria
            $similarResults = $this->findSimilarProperties($criteria);

            return [
                'target_price' => $similarResults['approximate_price'],
                'confidence' => 'no_match',
                'sample_size' => 0,
                'price_range' => $similarResults['price_range'],
                'statistics' => $similarResults['statistics'],
                'market_insights' => [
                    'message' => 'No exact matches found with the specified criteria',
                    'has_similar_properties' => $similarResults['has_similar'],
                    'similar_properties_count' => $similarResults['count'],
                    'closest_matches' => $similarResults['closest_matches'],
                    'suggested_adjustments' => $similarResults['suggestions'],
                    'alternative_search' => $similarResults['alternative_criteria']
                ]
            ];
        }

        $prices = $estates->pluck('price')->sort()->values();
        $sampleSize = $prices->count();

        // Calculate statistics
        $mean = $prices->avg();
        $median = $this->calculateMedian($prices->toArray());
        $q1 = $this->calculatePercentile($prices->toArray(), 25);
        $q3 = $this->calculatePercentile($prices->toArray(), 75);
        $min = $prices->min();
        $max = $prices->max();

        // Calculate target price (weighted average of median and mean, favoring median)
        $targetPrice = ($median * 0.7) + ($mean * 0.3);

        // Determine confidence level
        $confidence = $this->calculateConfidence($sampleSize, $prices->toArray());

        // Calculate price range (interquartile range)
        $priceRange = [
            'min' => $q1,
            'max' => $q3,
            'suggested_min' => $targetPrice * 0.9,
            'suggested_max' => $targetPrice * 1.1
        ];

        // Market insights
        $marketInsights = $this->generateMarketInsights($estates, $targetPrice);

        return [
            'target_price' => round($targetPrice, 2),
            'confidence' => $confidence,
            'sample_size' => $sampleSize,
            'price_range' => $priceRange,
            'statistics' => [
                'mean' => round($mean, 2),
                'median' => round($median, 2),
                'min' => $min,
                'max' => $max,
                'q1' => round($q1, 2),
                'q3' => round($q3, 2)
            ],
            'market_insights' => $marketInsights
        ];
    }

    /**
     * Get market trends for a specific location and category
     */
    public function getMarketTrends(int $locationId, int $categoryId, int $months = 12): array
    {
        $trends = DB::table('estates')
            ->where('location_id', $locationId)
            ->where('category_id', $categoryId)
            ->where('published', true)
            ->whereNotNull('price')
            ->where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('AVG(price) as avg_price'),
                DB::raw('COUNT(*) as listing_count'),
                DB::raw('COUNT(CASE WHEN sold = 1 THEN 1 END) as sold_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'trends' => $trends,
            'summary' => [
                'total_listings' => $trends->sum('listing_count'),
                'total_sold' => $trends->sum('sold_count'),
                'average_price_trend' => $this->calculatePriceTrend($trends),
                'market_activity' => $this->calculateMarketActivity($trends)
            ]
        ];
    }

    /**
     * Get price distribution for a category in a location
     */
    public function getPriceDistribution(int $locationId, int $categoryId): array
    {
        $prices = DB::table('estates')
            ->where('location_id', $locationId)
            ->where('category_id', $categoryId)
            ->where('published', true)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->pluck('price')
            ->sort()
            ->values();

        if ($prices->isEmpty()) {
            return ['distribution' => [], 'message' => 'No data available'];
        }

        $min = $prices->min();
        $max = $prices->max();
        $range = $max - $min;
        $bucketSize = $range / 10; // 10 buckets

        $distribution = [];
        for ($i = 0; $i < 10; $i++) {
            $bucketMin = $min + ($i * $bucketSize);
            $bucketMax = $min + (($i + 1) * $bucketSize);

            $count = $prices->filter(function ($price) use ($bucketMin, $bucketMax, $i) {
                return $i === 9 ? ($price >= $bucketMin && $price <= $bucketMax) : ($price >= $bucketMin && $price < $bucketMax);
            })->count();

            $distribution[] = [
                'range' => [
                    'min' => round($bucketMin, 2),
                    'max' => round($bucketMax, 2)
                ],
                'count' => $count,
                'percentage' => round(($count / $prices->count()) * 100, 1)
            ];
        }

        return ['distribution' => $distribution];
    }

    /**
     * Calculate median value
     */
    private function calculateMedian(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        sort($values);

        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        }

        return (float) $values[floor($count / 2)];
    }

    /**
     * Calculate percentile
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (count($values) === 0) {
            return 0.0;
        }

        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);

        if (floor($index) === $index) {
            return (float) $values[$index];
        }

        $lower = (float) $values[floor($index)];
        $upper = (float) $values[ceil($index)];
        $fraction = $index - floor($index);

        return $lower + ($fraction * ($upper - $lower));
    }

    /**
     * Calculate confidence level based on sample size and price variance
     */
    private function calculateConfidence(int $sampleSize, array $prices): string
    {
        if ($sampleSize < 3) {
            return 'very_low';
        }

        if ($sampleSize < 5) {
            return 'low';
        }

        if ($sampleSize < 10) {
            return 'medium';
        }

        // Calculate coefficient of variation
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $prices)) / count($prices);
        $stdDev = sqrt($variance);
        $cv = $stdDev / $mean;

        if ($cv > 0.5) {
            return 'medium'; // High variance
        }

        return $sampleSize >= 20 ? 'very_high' : 'high';
    }

    /**
     * Generate market insights
     */
    private function generateMarketInsights(\Illuminate\Support\Collection $estates, float $targetPrice): array
    {
        $totalEstates = $estates->count();
        $soldEstates = $estates->where('sold', true)->count();
        $activeEstates = $totalEstates - $soldEstates;

        $insights = [];

        if ($soldEstates > 0) {
            $soldRate = ($soldEstates / $totalEstates) * 100;
            $insights['sold_rate'] = round($soldRate, 1);

            if ($soldRate > 70) {
                $insights['market_status'] = 'seller_market';
                $insights['recommendation'] = 'Consider pricing at the higher end of the range';
            } elseif ($soldRate < 30) {
                $insights['market_status'] = 'buyer_market';
                $insights['recommendation'] = 'Consider competitive pricing';
            } else {
                $insights['market_status'] = 'balanced_market';
                $insights['recommendation'] = 'Target price looks appropriate for current market';
            }
        }

        $insights['active_listings'] = $activeEstates;
        $insights['competition_level'] = $activeEstates > 10 ? 'high' : ($activeEstates > 5 ? 'medium' : 'low');

        return $insights;
    }

    /**
     * Calculate price trend
     */
    private function calculatePriceTrend(\Illuminate\Support\Collection $trends): string
    {
        if ($trends->count() < 2) {
            return 'insufficient_data';
        }

        $first = $trends->first()->avg_price;
        $last = $trends->last()->avg_price;

        $change = (($last - $first) / $first) * 100;

        if ($change > 5) {
            return 'increasing';
        } elseif ($change < -5) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Calculate market activity level
     */
    private function calculateMarketActivity(\Illuminate\Support\Collection $trends): string
    {
        $avgListings = $trends->avg('listing_count');

        if ($avgListings > 20) {
            return 'very_high';
        } elseif ($avgListings > 10) {
            return 'high';
        } elseif ($avgListings > 5) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Find similar properties when exact match fails
     */
    private function findSimilarProperties(array $criteria): array
    {
        $suggestions = [];
        $alternativeCriteria = [];
        $hasAttributes = isset($criteria['attributes']) && is_array($criteria['attributes']);

        // Start with location and category only (no attributes)
        $baseQuery = DB::table('estates')
            ->where('published', true)
            ->whereNotNull('price')
            ->where('price', '>', 0);

        if (isset($criteria['location_id'])) {
            $baseQuery->where('location_id', $criteria['location_id']);
        }

        if (isset($criteria['category_id'])) {
            $baseQuery->where('category_id', $criteria['category_id']);
        }

        $baseQuery->where(function ($q) {
            $q->where('sold', false)
              ->orWhere(function ($subQ) {
                  $subQ->where('sold', true)
                       ->where('updated_at', '>=', now()->subMonths(6));
              });
        });

        $baseCount = $baseQuery->count();

        if ($baseCount === 0) {
            return [
                'has_similar' => false,
                'count' => 0,
                'approximate_price' => null,
                'price_range' => null,
                'statistics' => null,
                'closest_matches' => [],
                'suggestions' => ['Try searching in a different location or category'],
                'alternative_criteria' => null
            ];
        }

        // Get all properties in this location/category for baseline
        $allProperties = $baseQuery->get(['id', 'name', 'price', 'custom_attributes', 'sold']);

        // If we have attributes, try to find best matches
        if ($hasAttributes) {
            $scoredMatches = $this->scorePropertyMatches($allProperties, $criteria['attributes']);
            $closestMatches = array_slice($scoredMatches, 0, 5); // Top 5 matches

            // Get available attribute values for suggestions
            $availableValues = $this->getAvailableAttributeValues($criteria);

            foreach ($criteria['attributes'] as $attributeName => $requestedValue) {
                if (isset($availableValues[$attributeName])) {
                    $available = $availableValues[$attributeName];
                    $closest = $this->findClosestValue($requestedValue, $available);

                    if ($closest && $closest !== $requestedValue) {
                        $suggestions[] = "Consider '$closest' instead of '$requestedValue' for $attributeName";
                        $alternativeCriteria[$attributeName] = $closest;
                    }
                }
            }

            // Calculate approximate pricing from closest matches
            $matchPrices = array_column($closestMatches, 'price');
            $approximatePrice = !empty($matchPrices) ? array_sum($matchPrices) / count($matchPrices) : null;

            return [
                'has_similar' => count($closestMatches) > 0,
                'count' => count($allProperties),
                'approximate_price' => $approximatePrice ? round($approximatePrice, 2) : null,
                'price_range' => $this->calculateRangeFromMatches($matchPrices),
                'statistics' => $this->calculateStatsFromMatches($matchPrices),
                'closest_matches' => $closestMatches,
                'suggestions' => $suggestions ?: ['Try removing some specific attribute filters'],
                'alternative_criteria' => $alternativeCriteria
            ];
        }

        // No specific attributes - just return basic info
        $prices = $allProperties->pluck('price')->toArray();
        $avgPrice = array_sum($prices) / count($prices);

        return [
            'has_similar' => $baseCount > 0,
            'count' => $baseCount,
            'approximate_price' => round($avgPrice, 2),
            'price_range' => $this->calculateRangeFromMatches($prices),
            'statistics' => $this->calculateStatsFromMatches($prices),
            'closest_matches' => $allProperties->take(5)->map(function($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'price' => $property->price,
                    'attributes' => json_decode($property->custom_attributes, true) ?: [],
                    'match_score' => 0.5, // Base score for location/category match only
                    'sold' => (bool) $property->sold
                ];
            })->toArray(),
            'suggestions' => ['Properties available without specific attribute filters'],
            'alternative_criteria' => null
        ];
    }

    /**
     * Try relaxed attribute searches by removing one attribute at a time
     */
    private function tryRelaxedAttributeSearch(array $criteria, $baseQuery): array
    {
        $attributes = $criteria['attributes'];
        $bestMatchCount = 0;
        $bestMatchCriteria = null;

        // Try removing each attribute one by one
        foreach ($attributes as $skipAttribute => $value) {
            $relaxedQuery = clone $baseQuery;

            foreach ($attributes as $attributeName => $attributeValue) {
                if ($attributeName !== $skipAttribute) {
                    $relaxedQuery->whereJsonContains('custom_attributes->' . $attributeName, $attributeValue);
                }
            }

            $count = $relaxedQuery->count();

            if ($count > $bestMatchCount) {
                $bestMatchCount = $count;
                $relaxedAttributes = $attributes;
                unset($relaxedAttributes[$skipAttribute]);
                $bestMatchCriteria = $relaxedAttributes;
            }
        }

        // Also try with no attribute filters
        $noAttributesCount = $baseQuery->count();
        if ($noAttributesCount > $bestMatchCount) {
            $bestMatchCount = $noAttributesCount;
            $bestMatchCriteria = null;
        }

        return [
            'count' => $bestMatchCount,
            'best_match_criteria' => $bestMatchCriteria
        ];
    }

    /**
     * Get available attribute values for the given location and category
     */
    private function getAvailableAttributeValues(array $criteria): array
    {
        $query = DB::table('estates')
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
     * Find the closest value from available options
     */
    private function findClosestValue(string $requested, array $available): ?string
    {
        if (empty($available)) {
            return null;
        }

        // If it's a number, find the closest numeric value
        if (is_numeric($requested)) {
            $requestedNum = (float) $requested;
            $closest = null;
            $closestDiff = PHP_FLOAT_MAX;

            foreach ($available as $value) {
                if (is_numeric($value)) {
                    $diff = abs($requestedNum - (float) $value);
                    if ($diff < $closestDiff) {
                        $closestDiff = $diff;
                        $closest = $value;
                    }
                }
            }

            return $closest;
        }

        // For strings, try to find similar values
        $requested = strtolower($requested);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($available as $value) {
            $similarity = 0;
            similar_text(strtolower($value), $requested, $similarity);

            if ($similarity > $bestScore && $similarity > 50) { // At least 50% similarity
                $bestScore = $similarity;
                $bestMatch = $value;
            }
        }

        return $bestMatch;
    }

    /**
     * Score property matches based on attribute similarity
     */
    private function scorePropertyMatches($properties, array $targetAttributes): array
    {
        $scoredMatches = [];

        foreach ($properties as $property) {
            $propertyAttributes = json_decode($property->custom_attributes, true) ?: [];
            $matchScore = $this->calculateMatchScore($propertyAttributes, $targetAttributes);

            $scoredMatches[] = [
                'id' => $property->id,
                'name' => $property->name,
                'price' => (float) $property->price,
                'attributes' => $propertyAttributes,
                'match_score' => $matchScore,
                'sold' => (bool) $property->sold
            ];
        }

        // Sort by match score (highest first)
        usort($scoredMatches, function ($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        return $scoredMatches;
    }

    /**
     * Calculate match score between property attributes and target attributes
     */
    private function calculateMatchScore(array $propertyAttributes, array $targetAttributes): float
    {
        $totalAttributes = count($targetAttributes);
        if ($totalAttributes === 0) {
            return 0.5; // Base score
        }

        $matchingAttributes = 0;
        $partialMatches = 0;

        foreach ($targetAttributes as $key => $targetValue) {
            if (isset($propertyAttributes[$key])) {
                $propertyValue = $propertyAttributes[$key];

                // Exact match
                if ($propertyValue === $targetValue) {
                    $matchingAttributes++;
                } elseif (is_numeric($targetValue) && is_numeric($propertyValue)) {
                    // Numeric similarity (within 20% range)
                    $diff = abs((float) $propertyValue - (float) $targetValue);
                    $average = ((float) $propertyValue + (float) $targetValue) / 2;
                    $percentDiff = $average > 0 ? ($diff / $average) : 1;

                    if ($percentDiff <= 0.2) { // Within 20%
                        $partialMatches += 0.8;
                    } elseif ($percentDiff <= 0.5) { // Within 50%
                        $partialMatches += 0.5;
                    }
                } else {
                    // String similarity
                    $similarity = 0;
                    similar_text(strtolower($propertyValue), strtolower($targetValue), $similarity);
                    if ($similarity > 60) {
                        $partialMatches += ($similarity / 100) * 0.8;
                    }
                }
            }
        }

        // Calculate final score (0 to 1)
        $score = ($matchingAttributes + $partialMatches) / $totalAttributes;
        return min(1.0, $score);
    }

    /**
     * Calculate price range from array of prices
     */
    private function calculateRangeFromMatches(array $prices): ?array
    {
        if (empty($prices)) {
            return null;
        }

        $cleanPrices = array_filter($prices, function($price) {
            return is_numeric($price) && $price > 0;
        });

        if (empty($cleanPrices)) {
            return null;
        }

        sort($cleanPrices);
        $min = min($cleanPrices);
        $max = max($cleanPrices);
        $avg = array_sum($cleanPrices) / count($cleanPrices);

        return [
            'min' => $min,
            'max' => $max,
            'suggested_min' => round($avg * 0.9, 2),
            'suggested_max' => round($avg * 1.1, 2)
        ];
    }

    /**
     * Calculate statistics from array of prices
     */
    private function calculateStatsFromMatches(array $prices): ?array
    {
        if (empty($prices)) {
            return null;
        }

        $cleanPrices = array_filter($prices, function($price) {
            return is_numeric($price) && $price > 0;
        });

        if (empty($cleanPrices)) {
            return null;
        }

        $mean = array_sum($cleanPrices) / count($cleanPrices);
        $median = $this->calculateMedian($cleanPrices);
        $min = min($cleanPrices);
        $max = max($cleanPrices);

        return [
            'mean' => round($mean, 2),
            'median' => round($median, 2),
            'min' => $min,
            'max' => $max,
            'sample_size' => count($cleanPrices)
        ];
    }
}