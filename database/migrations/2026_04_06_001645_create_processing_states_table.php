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
        Schema::create('processing_states', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->unique();
            $table->unsignedBigInteger('last_processed_line')->default(0);
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processing_states');
    }
};
