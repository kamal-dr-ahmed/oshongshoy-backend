<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url');
            $table->enum('type', ['reference', 'source', 'related', 'citation']);
            
            // Link metadata
            $table->string('domain')->nullable(); // Extracted domain
            $table->boolean('is_verified')->default(false); // Link verified to work
            $table->datetime('last_checked')->nullable();
            $table->integer('click_count')->default(0);
            
            // Link preview data (Open Graph)
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_site_name')->nullable();
            
            $table->timestamps();
            
            $table->index('type');
            $table->index('domain');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_links');
    }
};
