<?php

namespace App\Livewire\Builder;

use App\Services\ApprovalService;
use App\Services\EditorTokenService;
use App\Services\RecordPersistenceService;
use App\Services\TextEditorReviewService;
use App\Support\RecordValidationRuleFactory;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordComment;
use App\Models\RecordHistory;
use App\Models\RecordApproval;

class DynamicRecordForm extends Component
{
    use WithFileUploads;

    public $moduleSlug;
    public $module;
    public $recordId = null;
    public $record   = null;
    public $data     = [];
    public $status   = '';
    public $newComment      = '';
    public $approvalComment = '';
    public $showApprovalPanel = false;
    public array $editorTokens = [];

    protected ApprovalService $approvalService;
    protected RecordPersistenceService $persistence;
    protected EditorTokenService $editorToken;
    protected TextEditorReviewService $reviews;

    public function boot(
        ApprovalService $approvalService,
        RecordPersistenceService $persistence,
        EditorTokenService $editorToken,
        TextEditorReviewService $reviews,
    ): void {
        $this->approvalService = $approvalService;
        $this->persistence     = $persistence;
        $this->editorToken     = $editorToken;
        $this->reviews         = $reviews;
    }

    public function mount($moduleSlug, $record = null): void
    {
        $this->moduleSlug = $moduleSlug;
        $this->module     = Module::with('fields')->where('slug', $moduleSlug)->firstOrFail();
        $this->module->setRelation('fields', $this->module->resolvedFields());

        $targetModuleId = $this->module->resolvedModuleId();

        if ($record) {
            $this->record = Record::where('module_id', $targetModuleId)->findOrFail($record);
            if ($this->record->status === 'Completed') abort(403, 'Completed records cannot be edited.');
            if (! Gate::allows('update', $this->record)) abort(403);
            $this->recordId = $this->record->id;
            $this->data     = $this->record->data ?? [];
            $this->status   = $this->record->status ?? $this->module->default_status ?? 'Submitted';

            foreach ($this->module->fields as $field) {
                if ($field->type === 'multi_select' && ! is_array($this->data[$field->slug] ?? null)) {
                    $this->data[$field->slug] = [];
                } elseif ($field->type === 'attachment' && $field->versioning) {
                    $this->data[$field->slug] = '';
                }
            }
        } else {
            if (! auth()->user()->can("create-{$this->moduleSlug}")) abort(403);
            $this->status = $this->module->default_status ?? 'Submitted';

            foreach ($this->module->fields as $field) {
                $this->data[$field->slug] = match ($field->type) {
                    'boolean'      => false,
                    'multi_select' => [],
                    'text_editor'  => $field->options_json['template'] ?? '',
                    default        => '',
                };
            }
        }

        $prefix         = 'editor-' . ($this->recordId ?? 'new') . '-';
        $editorSlugs    = $this->module->fields->where('type', 'text_editor')->pluck('slug')->all();
        $this->editorTokens = $this->editorToken->mint(auth()->user(), $prefix, $editorSlugs);
    }

    // ─── Save Actions ──────────────────────────────────────────────────────────

    public function saveAsDraft()
    {
        $this->status = 'Draft';
        return $this->save();
    }

    public function saveAndSubmit()
    {
        $this->status = 'Draft';
        $record = $this->persistRecord();
        $this->record   = $record;
        $this->recordId = $record->id;
        $this->submitForApproval();
        return redirect()->route('dynamic.index', $this->moduleSlug);
    }

    public function save()
    {
        $this->persistRecord();
        return redirect()->route('dynamic.index', $this->moduleSlug);
    }

    // ─── Approval Actions ──────────────────────────────────────────────────────

    public function submitForApproval(): void
    {
        if (! $this->record) return;

        try {
            $this->approvalService->submit($this->record, auth()->user());
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
            return;
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            return;
        }

        $this->status = 'Submitted';
        session()->flash('message', 'Record submitted for approval.');
    }

    public function approve(): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);

        if ($this->record && $this->data) {
            $this->record->update(['data' => $this->data, 'updated_by' => auth()->id()]);
        }

        $this->approvalService->approve($this->record, auth()->user(), $this->approvalComment);

        $this->status = $this->record->status;
        $this->approvalComment = '';

        $msg = $this->record->status === 'Completed'
            ? 'Record approved successfully.'
            : 'Approved. Record advanced to next stage.';
        session()->flash('message', $msg);
    }

    public function forwardToBranch($index): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);

        if ($this->record && $this->data) {
            $this->record->update(['data' => $this->data, 'updated_by' => auth()->id()]);
        }

        try {
            $this->approvalService->forwardToBranch($this->record, auth()->user(), (int) $index, $this->approvalComment);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            return;
        }

        $this->status = $this->record->status;
        $this->approvalComment = '';
        session()->flash('message', "Record forwarded.");
    }

    public function returnForRevision(): void
    {
        if (! Gate::allows('approve', $this->record)) abort(403);
        $this->validate(['approvalComment' => 'required|string|max:2000'], [], ['approvalComment' => 'revision notes']);

        try {
            $this->approvalService->returnForRevision($this->record, auth()->user(), $this->approvalComment);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
            return;
        }

        $this->status = 'Returned';
        $this->approvalComment = '';
        session()->flash('message', 'Record returned for revision.');
    }

    public function markReviewDone(string $fieldSlug): void
    {
        if (! $this->record) return;
        if (! Gate::allows('review', $this->record)) abort(403);

        // Always persist current editor content when marking a review done.
        if ($this->data) {
            $this->record->update(['data' => $this->data, 'updated_by' => auth()->id()]);
        }

        $this->reviews->recordReview($this->record->id, $fieldSlug, auth()->id());
        $this->approvalService->logReview($this->record, auth()->user());

        $reviewerCount = $this->reviews->countPermissionReviewers($this->moduleSlug);
        $doneCount     = $this->reviews->countDone($this->record->id, $fieldSlug);

        if ($reviewerCount > 0 && $doneCount >= $reviewerCount) {
            // All reviewers done — auto-approve without re-checking the approve gate,
            // because the reviewer role does not hold approve permission.
            $this->approvalService->approve($this->record, auth()->user(), '', false);
            $this->status = $this->record->fresh()->status;
            $this->approvalComment = '';
            $msg = $this->record->status === 'Completed'
                ? 'All reviews completed. Record approved successfully.'
                : 'All reviews completed. Record advanced to next stage.';
            session()->flash('message', $msg);
            return;
        }

        $this->dispatch('review-marked-done', fieldSlug: $fieldSlug);
        $this->dispatch('notify', type: 'success', message: 'Review marked as done.');
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
        $comments  = $this->recordId
            ? RecordComment::where('record_id', $this->recordId)->with('user')->oldest()->get()
            : collect();
        $histories = $this->recordId
            ? RecordHistory::where('record_id', $this->recordId)->with('user')->latest()->get()
            : collect();
        $approvals = $this->recordId
            ? RecordApproval::where('record_id', $this->recordId)->with(['user', 'stage'])->latest()->get()
            : collect();

        $canDeleteComments = auth()->user()->can('delete-comments');
        $hasStages         = $this->module->workflowStages()->exists();
        $canAct            = $this->record && $this->record->current_stage_id
            ? Gate::allows('approve', $this->record)
            : false;
        $canReview         = $this->record ? Gate::allows('review', $this->record) : false;
        $canSubmit         = $hasStages
            && $this->module->has_submit_button
            && (! $this->recordId || in_array($this->record->status ?? '', ['Draft', 'Returned']))
            && auth()->user()->can("create-{$this->moduleSlug}");
        $currentStage = $this->record?->currentStage;

        $reviewedFields   = $this->recordId
            ? $this->reviews->getReviewedFields($this->recordId, auth()->id())
            : [];
        $reviewersByField = $this->recordId
            ? $this->reviews->getReviewersByField($this->recordId)
            : collect();

        if (empty($this->editorTokens)) {
            $prefix      = 'editor-' . ($this->recordId ?? 'new') . '-';
            $editorSlugs = $this->module->fields->where('type', 'text_editor')->pluck('slug')->all();
            $this->editorTokens = $this->editorToken->mint(auth()->user(), $prefix, $editorSlugs);
        }
        $editorTokens = $this->editorTokens;

        return view('livewire.builder.dynamic-record-form',
            compact('comments', 'canDeleteComments', 'histories', 'approvals', 'hasStages',
                'canAct', 'canReview', 'canSubmit', 'currentStage', 'reviewedFields',
                'reviewersByField', 'editorTokens'));
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function persistRecord(): Record
    {
        if ($this->record && $this->record->status === 'Completed') {
            abort(403, 'Completed records cannot be edited.');
        }

        $this->validate(RecordValidationRuleFactory::forForm($this->module->fields, $this->record));

        foreach ($this->module->fields as $field) {
            if ($field->type === 'attachment') {
                $value = $this->data[$field->slug] ?? null;
                if (is_object($value) && method_exists($value, 'store')) {
                    $this->validate([
                        'data.' . $field->slug => 'file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip',
                    ]);
                }
            }
        }

        return $this->persistence->save(
            $this->module,
            array_merge($this->data, ['status' => $this->status]),
            $this->record
        );
    }
}
