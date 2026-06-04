<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('record_approvals', function (Blueprint $table) {
            // Tracks whether this approval row represents the reviewer's
            // final decision for the stage (as opposed to an intermediate
            // comment / partial action).  NULL means the row is a log entry;
            // a timestamp means the reviewer has formally concluded their review.
            $table->timestamp('reviewed_at')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('record_approvals', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });
    }
};
