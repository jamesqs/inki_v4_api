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
            // Add user_id to track who created the property
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');

            // Add listing type (sale or rent)
            $table->enum('listing_type', ['sale', 'rent'])->default('sale')->after('category_id');

            // Add price type (fixed or auction)
            $table->enum('price_type', ['fixed', 'auction'])->default('fixed')->after('price');

            // Add currency
            $table->string('currency', 3)->default('HUF')->after('price_type');

            // Add status field (draft, published, archived) - rename from 'published' boolean
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('listing_type');

            // Modify address to be JSON to store full address object
            $table->json('address_data')->nullable()->after('zip');

            // Add photos as JSON array
            $table->json('photos')->nullable()->after('custom_attributes');

            // Add views counter
            $table->integer('views')->default(0)->after('photos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estates', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'listing_type',
                'price_type',
                'currency',
                'status',
                'address_data',
                'photos',
                'views'
            ]);
        });
    }
};
