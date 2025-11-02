<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_bn'); // Bengali name
            $table->string('name_en'); // English name  
            $table->string('slug')->unique();
            $table->text('description_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->string('icon')->nullable(); // Icon class or image
            $table->string('color')->nullable(); // Hex color for UI
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
