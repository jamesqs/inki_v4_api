<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class FixUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-roles {--check : Only check without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix users with NULL or invalid roles by setting them to "user"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $checkOnly = $this->option('check');

        // Count users with NULL roles
        $nullRoleCount = User::whereNull('role')->count();

        if ($nullRoleCount > 0) {
            $this->info("Found {$nullRoleCount} users with NULL role");

            if (!$checkOnly) {
                // Update users with NULL roles
                $updated = User::whereNull('role')->update(['role' => 'user']);
                $this->info("Updated {$updated} users to have 'user' role");
            }
        } else {
            $this->info("No users with NULL role found");
        }

        // Show role distribution
        $this->newLine();
        $this->info("Current role distribution:");

        $roles = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();

        $this->table(['Role', 'Count'], $roles->map(function ($item) {
            return [
                'role' => $item->role ?? 'NULL',
                'count' => $item->count
            ];
        }));

        if ($checkOnly) {
            $this->newLine();
            $this->comment('Run without --check flag to update the roles');
        }

        return 0;
    }
}
