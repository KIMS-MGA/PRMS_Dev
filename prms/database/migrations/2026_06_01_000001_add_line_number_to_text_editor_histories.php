<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('text_editor_histories', function (Blueprint $table) {
            // 1-based document line/paragraph where the change began (nullable for legacy rows).
            $table->unsignedInteger('line_number')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('text_editor_histories', function (Blueprint $table) {
            $table->dropColumn('line_number');
        });
    }
};
