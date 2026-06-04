<?php

namespace App\Livewire\Builder;

use Livewire\Component;
use App\Models\Record;
use App\Models\Module;
use App\Models\RecordHistory;
use App\Models\RecordSchedule;
use App\Models\WorkflowStage;
use Livewire\Attributes\Layout;

class Dashboard extends Component
{
    public bool $showPastTrc = false;

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function togglePastTrc(): void
    {
        $this->showPastTrc = !$this->showPastTrc;
    }

    /**
     * Get a scoped record query builder.
     *
     * - Proponents see only records they created (created_by = their id).
     * - Privileged roles (super admin, Reviewer, TRC Secretariat) see all records.
     * - Unclassified users fall back to module-level my_records_only scoping.
     */
    private function getScopedRecordQuery()
    {
        $user = auth()->user();
        $query = Record::query();

        // Proponents may only see their own records.
        if ($user->hasRole('Proponent') && !$user->hasRole('super admin')) {
            return $query->where('created_by', $user->id);
        }

        // Privileged roles see the full record set.
        if ($user->hasRole('super admin') || $user->hasRole('Reviewer')
            || $user->hasRole('TRC Secretariat')) {
            return $query;
        }

        // Non-role-classified users fall back to module-scoped filtering
        $restrictedModuleIds   = Module::where('my_records_only', true)->pluck('id')->toArray();
        $unrestrictedModuleIds = Module::where('my_records_only', false)->pluck('id')->toArray();

        $query->where(function ($q) use ($restrictedModuleIds, $unrestrictedModuleIds, $user) {
            if (!empty($unrestrictedModuleIds)) {
                $q->orWhereIn('module_id', $unrestrictedModuleIds);
            }
            if (!empty($restrictedModuleIds)) {
                $q->orWhere(function ($sub) use ($restrictedModuleIds, $user) {
                    $sub->whereIn('module_id', $restrictedModuleIds)
                        ->where('created_by', $user->id);
                });
            }
        });

        return $query;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        // Total records excluding Draft and Archived
        $totalRecords = $this->getScopedRecordQuery()
            ->whereNotIn('status', ['Draft', 'Archived'])
            ->count();

        // Status sub-counts
        $statusCounts = $this->getScopedRecordQuery()
            ->whereNotIn('status', ['Draft', 'Archived'])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $completedCount   = $statusCounts->get('Completed', 0);
        $underReviewCount = $statusCounts->get('Under Review', 0);
        $submittedCount   = $statusCounts->get('Submitted', 0);
        $returnedCount    = $statusCounts->get('Returned', 0);

        // Chart: status breakdown (donut)
        $statusChartData = [
            'labels' => ['Completed', 'Under Review', 'Submitted', 'Returned'],
            'data'   => [$completedCount, $underReviewCount, $submittedCount, $returnedCount],
            'colors' => ['#22c55e', '#a855f7', '#eab308', '#f97316'],
        ];

        // Chart: records created over last 30 days — single grouped query
        $trendRaw = $this->getScopedRecordQuery()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->pluck('count', 'date');

        $trendData = collect();
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $trendData->push([
                'date'  => $day->format('M d'),
                'count' => $trendRaw->get($day->format('Y-m-d'), 0),
            ]);
        }
        $trendChartData = [
            'labels' => $trendData->pluck('date')->toArray(),
            'data'   => $trendData->pluck('count')->toArray(),
        ];

        // Per-module stats — single aggregated query instead of 3N queries
        $modules = Module::whereNull('source_module_id')->orderBy('sort_order')->get();
        $moduleIds = $modules->pluck('id');
        $statRows = $this->getScopedRecordQuery()
            ->whereIn('module_id', $moduleIds)
            ->selectRaw("module_id,
                SUM(CASE WHEN status NOT IN ('Draft','Archived') THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN current_stage_id IS NOT NULL THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as approved")
            ->groupBy('module_id')
            ->get()
            ->keyBy('module_id');

        $moduleStats = $modules->map(fn($m) => [
            'name'     => $m->name,
            'slug'     => $m->slug,
            'total'    => $statRows->get($m->id)?->total ?? 0,
            'pending'  => $statRows->get($m->id)?->pending ?? 0,
            'approved' => $statRows->get($m->id)?->approved ?? 0,
        ]);

        // TRC Schedule — latest active (non-cancelled) entry per record from record_schedules
        // MAX(id) gives the most recent row per record since IDs are auto-increment.
        $latestIds = RecordSchedule::selectRaw('MAX(id) as id')->groupBy('record_id')->pluck('id');

        $trcSchedule = RecordSchedule::whereIn('id', $latestIds)
            ->where('action', '!=', 'cancelled')
            ->with(['record.module', 'stage', 'scheduler'])
            ->when(!$this->showPastTrc, fn($q) => $q->where('scheduled_at', '>=', now()->startOfDay()))
            ->orderBy('scheduled_at')
            ->get();

        // Recent activity (last 10 history entries, scoped to authorized records)
        $scopedRecordIds = $this->getScopedRecordQuery()->pluck('id');
        $recentActivity = RecordHistory::whereIn('record_id', $scopedRecordIds)
            ->with(['user', 'record.module'])
            ->latest()
            ->limit(10)
            ->get();

        // Unread notifications
        $notifications = $user->unreadNotifications()->latest()->limit(5)->get();
        $unreadCount   = $user->unreadNotifications()->count();

        return view('livewire.builder.dashboard', compact(
            'totalRecords', 'completedCount', 'underReviewCount', 'submittedCount', 'returnedCount',
            'statusChartData', 'trendChartData', 'moduleStats',
            'trcSchedule', 'recentActivity', 'notifications', 'unreadCount'
        ));
    }
}

