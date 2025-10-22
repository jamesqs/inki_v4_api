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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            // File information
            $table->string('name'); // Original filename
            $table->string('file_name'); // Stored filename (UUID)
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->unsignedBigInteger('size'); // File size in bytes

            // Storage information
            $table->string('disk')->default('digitalocean'); // Storage disk
            $table->string('path'); // Path on disk
            $table->string('url'); // Public URL

            // Organization
            $table->string('collection')->nullable(); // e.g., 'estate_images', 'blog_images'
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, alt text, etc.)

            // Polymorphic relationship
            $table->string('mediable_type')->nullable();
            $table->unsignedBigInteger('mediable_id')->nullable();
            $table->index(['mediable_type', 'mediable_id']);

            // User tracking
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // Ordering
            $table->integer('order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
