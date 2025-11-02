<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0); // Order within article
            $table->string('position')->default('content'); // header, content, gallery, thumbnail
            $table->text('caption')->nullable(); // Article-specific caption
            $table->timestamps();
            
            $table->unique(['article_id', 'media_id']);
            $table->index(['article_id', 'position', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_media');
    }
};
