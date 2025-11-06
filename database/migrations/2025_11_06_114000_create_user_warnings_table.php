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
        Schema::create('user_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('issued_by')->constrained('users')->onDelete('cascade');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('reason');
            $table->boolean('is_read')->default(false);
            $table->timestamp('expires_at')->nullable(); // Warning expiration
            $table->timestamps();
            
            // Index for querying user's warnings
            $table->index(['user_id', 'is_read']);
            $table->index('issued_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_warnings');
    }
};
