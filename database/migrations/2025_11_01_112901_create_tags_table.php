<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name_bn');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // For tag styling
            $table->integer('usage_count')->default(0); // How many articles use this tag
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->index('slug');
            $table->index(['is_featured', 'usage_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
