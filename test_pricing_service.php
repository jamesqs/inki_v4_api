<?php

require_once 'vendor/autoload.php';

use App\Modules\Statistics\Services\PricingAnalysisService;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pricingService = new PricingAnalysisService();

// Test criteria
$criteria = [
    'location_id' => 1,
    'category_id' => 1,
    'attributes' => [
        'bedrooms' => '2',
        'parking' => 'yes'
    ]
];

echo "Testing Pricing Analysis Service\n";
echo "================================\n";
echo "Criteria: " . json_encode($criteria, JSON_PRETTY_PRINT) . "\n\n";

try {
    $result = $pricingService->calculateTargetPrice($criteria);
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Test market trends
try {
    echo "Testing Market Trends\n";
    $trends = $pricingService->getMarketTrends(1, 1, 6);
    echo "Market Trends Result:\n";
    echo json_encode($trends, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Test price distribution
try {
    echo "Testing Price Distribution\n";
    $distribution = $pricingService->getPriceDistribution(1, 1);
    echo "Price Distribution Result:\n";
    echo json_encode($distribution, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}