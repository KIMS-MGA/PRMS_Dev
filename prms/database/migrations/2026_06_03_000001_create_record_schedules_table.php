<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
            $table->foreignId('scheduled_by')->constrained('users');
            $table->dateTime('scheduled_at');
            $table->text('notes')->nullable();
            $table->string('action')->default('scheduled'); // scheduled | rescheduled | cancelled
            $table->timestamps();

            $table->index('record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_schedules');
    }
};
