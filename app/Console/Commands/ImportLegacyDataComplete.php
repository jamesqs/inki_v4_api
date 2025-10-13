<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportLegacyDataComplete extends Command
{
    protected $signature = 'import:legacy-complete {--file=inki_stage.sql} {--clear-all}';
    protected $description = 'Complete import of all legacy data (locations, attributes, categories, estates)';

    public function handle(): int
    {
        $filePath = $this->option('file');
        $clearAll = $this->option('clear-all');

        $this->info('🚀 Starting COMPLETE legacy data import from ' . $filePath);

        if ($clearAll) {
            $this->warn('⚠️  This will clear ALL existing data!');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Import cancelled.');
                return 0;
            }
        }

        $this->info('');
        $this->info('📍 Step 1: Importing Locations...');
        $this->call('import:legacy-locations', ['--file' => $filePath]);

        $this->info('');
        $this->info('🏷️  Step 2: Importing Attributes and connecting to Categories...');
        $this->call('import:legacy-attributes', ['--file' => $filePath]);

        $this->info('');
        $this->info('🏠 Step 3: Importing Estates with proper mappings...');
        $params = ['--file' => $filePath];
        if ($clearAll) {
            $params['--clear-first'] = true;
        }
        $this->call('import:legacy-estates-with-locations', $params);

        $this->info('');
        $this->info('✅ COMPLETE! Legacy data import finished successfully!');

        // Show final summary
        $this->call('db:show', ['table' => 'all']);

        return 0;
    }
}