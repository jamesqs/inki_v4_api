<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estates', function (Blueprint $table) {
            // Replace old 'status' field with enum-based workflow status
            $table->dropColumn('status');

            // Add new status workflow fields
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
                ->default('draft')
                ->after('listing_type');

            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('rejection_reason');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->timestamp('published_at')->nullable()->after('reviewed_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('published_at');

            // Add index for filtering by status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estates', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'status',
                'rejection_reason',
                'submitted_at',
                'reviewed_at',
                'published_at',
                'reviewed_by'
            ]);

            // Restore old status field
            $table->string('status')->default('published')->after('listing_type');
        });
    }
};
