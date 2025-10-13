<?php

namespace App\Modules\Statistics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'target_price' => $this->resource['target_price'],
            'confidence' => $this->resource['confidence'],
            'sample_size' => $this->resource['sample_size'],
            'price_range' => $this->resource['price_range'] ? [
                'min' => $this->resource['price_range']['min'] ?? null,
                'max' => $this->resource['price_range']['max'] ?? null,
                'suggested_min' => $this->resource['price_range']['suggested_min'] ?? null,
                'suggested_max' => $this->resource['price_range']['suggested_max'] ?? null,
            ] : null,
            'statistics' => $this->resource['statistics'] ?? null,
            'market_insights' => $this->resource['market_insights'],
            'generated_at' => now()->toISOString(),
        ];
    }
}