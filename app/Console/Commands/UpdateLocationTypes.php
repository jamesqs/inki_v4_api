<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Locations\Models\Location;
use Illuminate\Support\Facades\Storage;

class UpdateLocationTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:update-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update location types from Hungarian settlements CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting location types update...');

        $csvPath = storage_path('app/hungarian_settlements.csv');

        if (!file_exists($csvPath)) {
            $this->error('CSV file not found at: ' . $csvPath);
            return 1;
        }

        // Open CSV file
        $handle = fopen($csvPath, 'r');

        if (!$handle) {
            $this->error('Could not open CSV file');
            return 1;
        }

        // Skip header row
        fgetcsv($handle, 0, "\t");

        $updated = 0;
        $notFound = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 0, "\t")) !== false) {
            if (count($data) < 8) {
                continue;
            }

            $name = trim($data[1]);
            $type = trim($data[7]);

            // Skip empty types or entries without names
            if (empty($name) || empty($type)) {
                continue;
            }

            // Normalize type to lowercase
            $normalizedType = strtolower($type);

            // Find location by name
            $location = Location::where('name', $name)->first();

            if ($location) {
                $location->type = $normalizedType;
                $location->save();
                $updated++;

                if ($updated % 100 == 0) {
                    $this->info("Updated {$updated} locations...");
                }
            } else {
                $notFound++;
                if ($notFound <= 10) {
                    $errors[] = "Location not found: {$name}";
                }
            }
        }

        fclose($handle);

        $this->newLine();
        $this->info("✅ Update complete!");
        $this->info("Updated: {$updated} locations");
        $this->warn("Not found: {$notFound} locations");

        if (!empty($errors)) {
            $this->newLine();
            $this->warn("Sample of not found locations:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        // Show type breakdown
        $this->newLine();
        $this->info("Type breakdown:");
        $types = Location::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();

        foreach ($types as $typeData) {
            $typeName = $typeData->type ?: 'NULL';
            $this->line("  {$typeName}: {$typeData->count}");
        }

        return 0;
    }
}
