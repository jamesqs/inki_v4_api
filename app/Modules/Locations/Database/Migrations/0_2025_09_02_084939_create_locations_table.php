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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->integer('importance')->default(0);
            // add county that is connected to countys table
            $table->foreignId('county_id')->constrained('counties')->onDelete('cascade');
            // add type of the city (city, town, village, hamlet, etc)
            $table->string('type')->nullable();
            $table->boolean('has_districts')->default(false);
            $table->boolean('has_sub_districts')->default(false);
            $table->softDeletes();
            // Add your columns here
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
