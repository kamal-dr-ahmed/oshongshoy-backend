<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10); // bn, en, hi, ur, etc.
            
            // Content in different languages
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('excerpt'); // Short description
            $table->longText('content'); // Main documentary content
            
            // SEO for each language
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable(); // Array of keywords
            
            // Language-specific slugs
            $table->string('slug_translation')->nullable();
            
            $table->timestamps();
            
            // Ensure one translation per language per article
            $table->unique(['article_id', 'locale']);
            $table->index(['locale', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_translations');
    }
};
