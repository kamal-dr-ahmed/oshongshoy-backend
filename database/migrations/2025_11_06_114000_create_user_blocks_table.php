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
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('blocked_by')->constrained('users')->onDelete('cascade');
            $table->enum('block_type', ['temporary', 'permanent']);
            $table->text('reason');
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable(); // For temporary blocks
            $table->boolean('is_active')->default(true);
            $table->text('unblock_reason')->nullable();
            $table->foreignId('unblocked_by')->nullable()->constrained('users');
            $table->timestamp('unblocked_at')->nullable();
            $table->timestamps();
            
            // Index for checking active blocks
            $table->index(['user_id', 'is_active']);
            $table->index('blocked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
    }
};
