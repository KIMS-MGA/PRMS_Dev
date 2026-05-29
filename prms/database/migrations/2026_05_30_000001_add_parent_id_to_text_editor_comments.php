<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds threaded replies to inline comments. A reply rows points at its root
 * comment via parent_id. Additive only — existing comment rows have a NULL
 * parent_id and continue to behave exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('text_editor_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('comment_id');
            $table->foreign('parent_id')
                ->references('id')->on('text_editor_comments')
                ->nullOnDelete();
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('text_editor_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
