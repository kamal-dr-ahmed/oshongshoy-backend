<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('external_link_id')->constrained()->onDelete('cascade');
            $table->string('context')->nullable(); // Where in article this link appears
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['article_id', 'external_link_id']);
            $table->index(['article_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_links');
    }
};
