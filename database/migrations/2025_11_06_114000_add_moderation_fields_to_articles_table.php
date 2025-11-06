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
        Schema::table('articles', function (Blueprint $table) {
            // Change status column to support moderation workflow
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'changes_requested', 'published'])
                ->default('draft')->change();
            
            // Moderation tracking
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_notes')->nullable();
            
            // Submission tracking
            $table->timestamp('submitted_at')->nullable(); // When user submitted for review
            $table->integer('revision_count')->default(0); // Track how many times revised
            
            // Soft delete support
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'moderated_by',
                'moderated_at',
                'moderation_notes',
                'submitted_at',
                'revision_count',
                'deleted_at'
            ]);
        });
    }
};
