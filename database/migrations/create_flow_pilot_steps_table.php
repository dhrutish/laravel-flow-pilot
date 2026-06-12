<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_pilot_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_run_id')->constrained('flow_pilot_runs')->cascadeOnDelete();
            $table->string('name');
            $table->string('class')->nullable();
            $table->string('status')->index();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('attempts')->default(1);
            $table->unsignedInteger('max_attempts')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->index(['flow_run_id', 'status']);
            $table->index(['name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_pilot_steps');
    }
};
