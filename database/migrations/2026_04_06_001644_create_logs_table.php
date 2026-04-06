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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('consumer_id');
            $table->string('service_name');
            $table->integer('latencies_proxy');
            $table->integer('latencies_gateway');
            $table->integer('latencies_request');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->softDeletes();
            $table->index('consumer_id');
            $table->index('service_name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
