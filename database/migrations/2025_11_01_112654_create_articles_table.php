<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Author
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            
            // Publication info
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->datetime('published_at')->nullable();
            $table->integer('reading_time')->nullable(); // estimated reading time in minutes
            
            // SEO & Discovery
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00); // Average rating 0.00-5.00
            $table->integer('rating_count')->default(0);
            
            // Featured content
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->string('featured_image')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
