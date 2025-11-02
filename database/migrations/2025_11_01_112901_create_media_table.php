<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['image', 'video', 'audio', 'document']);
            
            // File information
            $table->string('file_path'); // Local or cloud storage path
            $table->string('file_name');
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            
            // For images
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            
            // For videos
            $table->integer('duration')->nullable(); // in seconds
            $table->string('video_url')->nullable(); // YouTube, Vimeo etc.
            $table->string('thumbnail_path')->nullable();
            
            // Attribution
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->string('credit')->nullable(); // Photo/video credit
            $table->string('source_url')->nullable(); // Original source
            
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            
            $table->index('type');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
