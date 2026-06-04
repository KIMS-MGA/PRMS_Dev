<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a submission_cycle counter to both records and record_approvals.
     *
     * Purpose:
     *   The record_approvals table serves a dual role:
     *     1. Active-cycle approval assignments   (reviewed_at IS NOT NULL)
     *     2. Immutable audit log for all actions  (all rows, every cycle)
     *
     *   Before this migration, submitForApproval() deleted every row where
     *   reviewed_at IS NOT NULL across all stages.  That correctly cleared the
     *   stale queue-blocking rows for the new cycle but also wiped out the
     *   full audit history from prior cycles (the Approval Log bug).
     *
     *   With submission_cycle:
     *     - records.submission_cycle increments on each resubmission.
     *     - record_approvals.submission_cycle stamps which cycle each row
     *       belongs to when it is created.
     *     - The cleanup in submitForApproval() now deletes ONLY finalized rows
     *       whose submission_cycle matches the cycle that just ended (i.e. the
     *       stale rows), leaving prior-cycle audit rows untouched.
     *     - allReviewersFinalized() and anyReviewerReturned() scope their
     *       queries to the current submission_cycle so prior-cycle decisions do
     *       not interfere with the live workflow.
     */
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->unsignedInteger('submission_cycle')
                  ->default(1)
                  ->after('stage_entered_at')
                  ->comment('Incremented each time the proponent resubmits. Used to scope active-cycle approval rows vs. historical audit rows.');
        });

        Schema::table('record_approvals', function (Blueprint $table) {
            $table->unsignedInteger('submission_cycle')
                  ->default(1)
                  ->after('reviewed_at')
                  ->comment('Records which submission cycle this approval row was created in. Matches records.submission_cycle at the time of creation.');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn('submission_cycle');
        });

        Schema::table('record_approvals', function (Blueprint $table) {
            $table->dropColumn('submission_cycle');
        });
    }
};