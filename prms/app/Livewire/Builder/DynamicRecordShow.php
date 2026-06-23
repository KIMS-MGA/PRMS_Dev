<?php

namespace App\Livewire\Builder;

use App\Services\ApprovalService;
use App\Services\EditorTokenService;
use App\Services\TextEditorReviewService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordComment;
use App\Models\RecordHistory;
use App\Models\RecordApproval;
use App\Models\User;
use App\Models\WorkflowStage;

class DynamicRecordShow extends Component
{
    use WithFileUploads;

    public $moduleSlug;
    public $module;
    public $record;
    public $recordId;
    public $approvalComment   = '';
    public $newComment        = '';
    public $reviewerAttachment = null;
    public $stageFieldValues   = [];
    protected array $editorTokens = [];

    protected ApprovalService $approvalService;
    protected EditorTokenService $editorToken;
    protected TextEditorReviewService $reviews;

    public function boot(
        ApprovalService $approvalService,
        EditorTokenService $editorToken,
        TextEditorReviewService $reviews,
    ): void {
        $this->approvalService = $approvalService;
        $this->editorToken     = $editorToken;
        $this->reviews         = $reviews;
    }

    public function mount($moduleSlug, $record): void
    {
        $this->moduleSlug = $moduleSlug;
        $this->module     = Module::with('fields')->where('slug', $moduleSlug)->firstOrFail();
        $this->module->setRelation('fields', $this->module->resolvedFields());

        $canView       = auth()->user()->can("view-{$this->moduleSlug}");
        $canApprove    = auth()->user()->can("approve-{$this->moduleSlug}");
        $canReview     = auth()->user()->can("review-{$this->moduleSlug}");
        $stageRoleIds  = WorkflowStage::where('module_id', $this->module->id)->pluck('approver_role_id')->filter();
        $isStageApprover = auth()->user()->roles->pluck('id')->intersect($stageRoleIds)->isNotEmpty();

        if (! $canView && ! $canApprove && ! $canReview && ! $isStageApprover) abort(403);

        $targetModuleId = $this->module->resolvedModuleId();
        $this->record   = Record::with('currentStage')->where('module_id', $targetModuleId)->findOrFail($record);
        $this->recordId = $this->record->id;

        foreach ($this->record->currentStage?->stage_fields_json ?? [] as $sf) {
            $slug = is_array($sf) ? ($sf['slug'] ?? '') : $sf;
            if ($slug) $this->stageFieldValues[$slug] = $this->record->data[$slug] ?? '';
        }

        $prefix      = 'editor-' . $this->recordId . '-';
        $editorSlugs = $this->module->fields->where('type', 'text_editor')->pluck('slug')->all();
        $this->editorTokens = $this->editorToken->mint(auth()->user(), $prefix, $editorSlugs);
    }

    // ─── Approval Actions ──────────────────────────────────────────────────────

    public function approve(): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);
        if (! $this->validateRequiredStageFields()) return;

        $this->approvalService->approve($this->record, auth()->user(), $this->approvalComment);

        $this->record = $this->record->fresh();
        $this->approvalComment = '';

        $msg = $this->record->status === 'Completed'
            ? 'Record approved successfully.'
            : 'Approved. Record advanced to next stage.';
        $this->dispatch('notify', type: 'success', message: $msg);
    }

    public function forwardToBranch($index): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);
        if (! $this->validateRequiredStageFields()) return;

        try {
            $this->approvalService->forwardToBranch($this->record, auth()->user(), (int) $index, $this->approvalComment);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
            return;
        }

        $this->approvalComment = '';
        $this->dispatch('notify', type: 'success', message: "Record forwarded.");
    }

    public function returnForRevision(): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);
        $this->validate(['approvalComment' => 'required|string|max:2000'], [], ['approvalComment' => 'revision notes']);

        try {
            $this->approvalService->returnForRevision($this->record, auth()->user(), $this->approvalComment);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
            return;
        }

        $this->approvalComment = '';
        $this->dispatch('notify', type: 'success', message: 'Record returned for revision.');
    }

    public function markReviewDone(string $fieldSlug): void
    {
        if (! Gate::allows('review', $this->record)) abort(403);

        $this->reviews->recordReview($this->record->id, $fieldSlug, auth()->id());

        $stage         = $this->record->currentStage;
        $reviewerCount = $stage ? $this->reviews->countStageReviewers($stage) : 0;
        $doneCount     = $this->reviews->countDone($this->record->id, $fieldSlug);

        if ($reviewerCount > 0 && $doneCount >= $reviewerCount) {
            $this->approve();
            return;
        }

        $this->dispatch('review-marked-done', fieldSlug: $fieldSlug);
        $this->dispatch('notify', type: 'success', message: 'Review marked as done.');
    }

    // ─── Stage Field Actions ───────────────────────────────────────────────────

    public function saveStageFieldValues(): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);
        $stageFields = $this->record->currentStage?->stage_fields_json ?? [];
        if (empty($stageFields)) return;

        foreach ($stageFields as $sf) {
            if (! is_array($sf) || empty($sf['slug']) || empty($sf['is_required'])) continue;
            if (! array_key_exists($sf['slug'], $this->stageFieldValues)) continue;

            $val = $this->stageFieldValues[$sf['slug']];
            if ($val === null || $val === '') {
                $label = $sf['label'] ?? $sf['slug'];
                $this->dispatch('notify', type: 'error', message: "'{$label}' is required before saving.");
                return;
            }
        }

        $data = $this->record->data ?? [];
        foreach ($stageFields as $sf) {
            $slug = is_array($sf) ? ($sf['slug'] ?? '') : $sf;
            if ($slug && array_key_exists($slug, $this->stageFieldValues)) {
                $data[$slug] = $this->stageFieldValues[$slug];
            }
        }
        $this->record->update(['data' => $data]);

        RecordHistory::create([
            'record_id'    => $this->record->id,
            'user_id'      => auth()->id(),
            'action'       => 'updated stage fields',
            'changes_json' => ['fields' => array_column($stageFields, 'slug')],
        ]);

        $this->dispatch('notify', type: 'success', message: 'Stage fields saved.');
    }

    public function attachStageFile($fieldSlug): void
    {
        abort_if(empty($fieldSlug), 422);
        if (! Gate::allows('approve', $this->record)) abort(403);
        if (! $this->reviewerAttachment) return;

        $allowedSlugs = collect($this->record->currentStage?->stage_fields_json ?? [])
            ->pluck('slug')->flip();
        if (! isset($allowedSlugs[$fieldSlug])) abort(422);

        $this->validate(['reviewerAttachment' => 'file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip|extensions:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip']);

        $path = $this->reviewerAttachment->store('attachments', 'public');
        $data = $this->record->data;
        $data[$fieldSlug] = $path;
        $this->record->update(['data' => $data]);
        $this->stageFieldValues[$fieldSlug] = $path;

        RecordHistory::create([
            'record_id'    => $this->record->id,
            'user_id'      => auth()->id(),
            'action'       => 'attached file',
            'changes_json' => ['field' => $fieldSlug, 'path' => $path],
        ]);

        $this->reviewerAttachment = null;
        $this->dispatch('notify', type: 'success', message: 'File attached.');
    }

    // ─── Comments ──────────────────────────────────────────────────────────────

    public function addComment(): void
    {
        $this->validate(['newComment' => 'required|string|max:2000']);

        RecordComment::create([
            'record_id' => $this->recordId,
            'user_id'   => auth()->id(),
            'body'      => $this->newComment,
        ]);

        $this->newComment = '';
    }

    public function deleteComment($commentId): void
    {
        if (! auth()->user()->can('delete-comments')) abort(403);
        RecordComment::where('id', $commentId)->where('record_id', $this->recordId)->delete();
    }

    // ─── Render ────────────────────────────────────────────────────────────────

    #[Layout('layouts.app')]
    public function render()
    {
        $this->module = Module::with('fields')->find($this->module->id);
        $this->module->setRelation('fields', $this->module->resolvedFields());
        $this->record = Record::with('currentStage')->find($this->recordId);

        $usersMap     = User::pluck('name', 'id');
        $comments     = RecordComment::where('record_id', $this->recordId)->with('user')->oldest()->get();
        $histories    = RecordHistory::where('record_id', $this->recordId)->with('user')->latest()->get();
        $approvals    = RecordApproval::where('record_id', $this->recordId)->with(['user', 'stage'])->latest()->get();
        $canAct       = $this->record && $this->record->current_stage_id
            ? Gate::allows('approve', $this->record)
            : false;
        $currentStage = $this->record->currentStage;

        $targetModuleId  = $this->module->resolvedModuleId();
        $user            = auth()->user();
        $stageAllowsEdit = $currentStage === null || ($currentStage->allow_edit ?? true);
        $canEdit         = $stageAllowsEdit && $user->can("edit-{$this->moduleSlug}");
        $canDeleteComments = $user->can('delete-comments');

        $allStages      = WorkflowStage::where('module_id', $targetModuleId)->orderBy('order')->get();
        $currentOrder   = $currentStage?->order ?? PHP_INT_MAX;
        $hasCurrentStage = $this->record->current_stage_id !== null;

        $stageFieldGroups = [];
        foreach ($allStages as $stage) {
            $defs = array_values(array_filter(
                $stage->stage_fields_json ?? [],
                fn ($sf) => is_array($sf) && ! empty($sf['slug'])
            ));
            if (empty($defs)) continue;
            if ($hasCurrentStage && $stage->order > $currentOrder) continue;
            $stageFieldGroups[] = [
                'stage'      => $stage,
                'defs'       => $defs,
                'is_current' => $stage->id === $this->record->current_stage_id,
            ];
        }

        $canReview      = Gate::allows('review', $this->record);
        $reviewedFields = $this->reviews->getReviewedFields($this->recordId, auth()->id());
        $reviewersByField = $this->reviews->getReviewersByField($this->recordId);

        if (empty($this->editorTokens)) {
            $prefix      = 'editor-' . $this->recordId . '-';
            $editorSlugs = $this->module->fields->where('type', 'text_editor')->pluck('slug')->all();
            $this->editorTokens = $this->editorToken->mint(auth()->user(), $prefix, $editorSlugs);
        }
        $editorTokens = $this->editorTokens;

        return view('livewire.builder.dynamic-record-show',
            compact('usersMap', 'comments', 'histories', 'approvals', 'canAct', 'canEdit',
                'canDeleteComments', 'currentStage', 'stageFieldGroups', 'canReview',
                'reviewedFields', 'reviewersByField', 'editorTokens'));
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function validateRequiredStageFields(): bool
    {
        $stageFields = $this->record->currentStage?->stage_fields_json ?? [];
        $missing = [];

        foreach ($stageFields as $sf) {
            if (! is_array($sf) || empty($sf['slug']) || empty($sf['is_required'])) continue;
            $dataValue = $this->record->data[$sf['slug']] ?? null;
            $value = ($dataValue !== null && $dataValue !== '' && $dataValue !== [])
                ? $dataValue
                : ($this->stageFieldValues[$sf['slug']] ?? null);
            if ($value === null || $value === '' || $value === []) {
                $missing[] = $sf['label'] ?? $sf['slug'];
            }
        }

        if (! empty($missing)) {
            $this->dispatch('notify', type: 'error', message: 'Required stage fields must be filled before proceeding: ' . implode(', ', $missing) . '.');
            return false;
        }

        return true;
    }
}
