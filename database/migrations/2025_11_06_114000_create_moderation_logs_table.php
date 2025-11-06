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
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['approved', 'rejected', 'changes_requested', 'published', 'unpublished']);
            $table->text('comment')->nullable(); // Moderator's feedback
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->timestamps();
            
            // Index for querying logs by article or moderator
            $table->index(['article_id', 'created_at']);
            $table->index('moderator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
