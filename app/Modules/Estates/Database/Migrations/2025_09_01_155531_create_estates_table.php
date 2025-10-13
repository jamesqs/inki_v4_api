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
        Schema::create('estates', function (Blueprint $table) {
            $table->id();
            // Add your columns here
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            //location_id as foreign key
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained('locations')->onDelete('set null');
            // category_id as foreign key
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

            $table->boolean('accepted')->default(false);
            $table->boolean('sold')->default(false);
            $table->boolean('published')->default(false);
            $table->string('address')->nullable();
            $table->string('zip')->nullable();

            $table->timestamps();

            $table->softDeletes();
            $table->index('location_id');
            $table->index('category_id');
            $table->index('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estates');
    }
};
