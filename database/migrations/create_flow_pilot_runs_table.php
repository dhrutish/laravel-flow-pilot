<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_pilot_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('flow_name')->index();
            $table->string('flow_class')->index();
            $table->string('status')->index();
            $table->string('trigger_type')->nullable()->index();
            $table->string('trigger_name')->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->index(['flow_name', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_pilot_runs');
    }
};
