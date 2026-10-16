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
        Schema::create('maintenance_request_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_request_id')
                ->constrained('maintenance_requests')
                ->cascadeOnDelete();

            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('description');

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_histories');
    }
};
