<?php

namespace App\Livewire\Builder;

use Livewire\Component;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordComment;
use App\Models\RecordHistory;
use App\Models\RecordApproval;
use App\Services\RecordSaveService;
use App\Services\RecordApprovalService;
use App\Services\RecordCommentService;
use App\Services\TokenMintingService;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

class DynamicRecordForm extends Component
{
    use WithFileUploads;

    public $moduleSlug;
    public $module;
    public $recordId = null;
    public $record = null;
    public $data = [];
    public $status = '';
    public $newComment = '';

    // Approval
    public $approvalComment = '';
    public $showApprovalPanel = false;
    protected array $editorTokens = []; // Minted in mount(); passed via render() — not exposed to Livewire snapshot

    public function mount($moduleSlug, $record = null)
    {
        $this->moduleSlug = $moduleSlug;
        $this->module = Module::with('fields')->where('slug', $moduleSlug)->firstOrFail();

        $targetModuleId = $this->module->source_module_id ?? $this->module->id;

        if ($this->module->source_module_id) {
            $sourceFields = Module::find($this->module->source_module_id)->fields;
            $ownFields = $this->module->fields;
            $this->module->setRelation('fields', $sourceFields->merge($ownFields));
        }

        $approvalService = app(RecordApprovalService::class);

        if ($record) {
            $this->record = Record::where('module_id', $targetModuleId)->findOrFail($record);
            if ($this->record->status === 'Completed') abort(403, 'Completed records cannot be edited.');
            if (!$approvalService->canEditRecord(auth()->user(), $this->moduleSlug, $this->record)) abort(403);
            $this->recordId = $this->record->id;
            $this->data = $this->record->data ?? [];
            $this->status = $this->record->status ?? $this->module->default_status ?? 'Submitted';

            // Ensure multi_select fields are arrays; clear versioned attachment fields for file input
            foreach ($this->module->fields as $field) {
                if ($field->type === 'multi_select' && !is_array($this->data[$field->slug] ?? null)) {
                    $this->data[$field->slug] = [];
                } elseif ($field->type === 'attachment' && $field->versioning) {
                    // Keep the versions array in the record; clear the form binding so the file input works
                    $this->data[$field->slug] = '';
                }
            }
        } else {
            if (!auth()->user()->can("create-{$this->moduleSlug}")) abort(403);
            $this->status = $this->module->default_status ?? 'Submitted';

            foreach ($this->module->fields as $field) {
                $this->data[$field->slug] = match($field->type) {
                    'boolean'     => false,
                    'multi_select'=> [],
                    'text_editor' => $field->options_json['template'] ?? '',
                    default       => '',
                };
            }
        }

        // Mint one editor token per text_editor field
        $this->editorTokens = app(TokenMintingService::class)->mintEditorTokens(
            auth()->user(),
            $this->module,
            $this->recordId
        );
    }

    public function saveAsDraft()
    {
        $this->status = 'Draft';
        return $this->save();
    }

    public function saveAndSubmit()
    {
        $this->status = 'Draft';
        $record = $this->persistRecord();
        $this->record = $record;
        $this->recordId = $record->id;
        $this->submitForApproval();
        return redirect()->route('dynamic.index', $this->moduleSlug);
    }

    public function save()
    {
        $this->persistRecord();
        return redirect()->route('dynamic.index', $this->moduleSlug);
    }

    private function persistRecord(): Record
    {
        $rules = $this->getValidationRules();
        $this->validate($rules);

        // Validate individual file sizes/types dynamically
        foreach ($this->module->fields as $field) {
            if ($field->type === 'attachment' && isset($this->data[$field->slug])) {
                $file = $this->data[$field->slug];
                if (is_object($file) && method_exists($file, 'store')) {
                    $this->validate([
                        'data.' . $field->slug => 'file|max:20480|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip|extensions:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip',
                    ]);
                }
            }
        }

        $record = app(RecordSaveService::class)->save(
            $this->module,
            $this->record,
            $this->data,
            $this->status,
            auth()->user()
        );

        return $record;
    }

    private function getValidationRules(): array
    {
        $rules = ['status' => 'required|string'];

        foreach ($this->module->fields as $field) {
            if ($field->type === 'multi_select') {
                $rules['data.' . $field->slug] = $field->is_required ? 'required|array|min:1' : 'nullable|array';
            } elseif ($field->type === 'attachment' && $field->versioning) {
                // Required only when there are no existing versions yet
                $hasVersions = !empty($this->record?->data[$field->slug]);
                $rules['data.' . $field->slug] = ($field->is_required && !$hasVersions) ? 'required' : 'nullable';
            } else {
                $rules['data.' . $field->slug] = $field->is_required ? 'required' : 'nullable';
            }
        }

        return $rules;
    }

    // ─── Approval Actions ──────────────────────────────────────────────────────

    public function submitForApproval()
    {
        if (!$this->record) return;

        try {
            app(RecordApprovalService::class)->submitForApproval($this->record, $this->module, auth()->user());
            $this->status = 'Submitted';
            session()->flash('message', 'Record submitted for approval.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function approve()
    {
        $approvalService = app(RecordApprovalService::class);
        $approvalService->authorizeApprovalAction($this->record, auth()->user(), $this->moduleSlug);

        // Persist any in-progress edits (e.g. text editor changes) before advancing
        if ($this->record && $this->data) {
            $this->record->update(['data' => $this->data, 'updated_by' => auth()->id()]);
        }

        $currentStage = $this->record->currentStage;
        $isFinal = !$currentStage || $currentStage->is_final_approval;

        $advanced = $approvalService->approve($this->record, $this->module, auth()->user(), $this->approvalComment);

        if (!$advanced) {
            $this->approvalComment = '';
            session()->flash('message', 'Review submitted. Waiting for other reviewers.');
            return;
        }

        if (!$isFinal) {
            // Find next stage to set local status correctly
            $targetModuleId = $this->module->source_module_id ?? $this->module->id;
            $nextStage = \App\Models\WorkflowStage::where('module_id', $targetModuleId)
                ->where('order', '>', $currentStage->order)
                ->orderBy('order')
                ->first();

            if ($nextStage) {
                $this->status = $nextStage->default_status ?? 'Under Review';
                $this->approvalComment = '';
                session()->flash('message', 'Approved. Record advanced to next stage.');
                return;
            }
        }

        $this->status = 'Completed';
        $this->approvalComment = '';
        session()->flash('message', 'Record approved successfully.');
    }

    public function forwardToBranch($index)
    {
        $approvalService = app(RecordApprovalService::class);
        $approvalService->authorizeApprovalAction($this->record, auth()->user(), $this->moduleSlug);

        // Persist any in-progress edits before advancing
        if ($this->record && $this->data) {
            $this->record->update(['data' => $this->data, 'updated_by' => auth()->id()]);
        }

        try {
            $currentStage = $this->record->currentStage;
            $branches = $currentStage?->branches_json ?? [];
            $branch = $branches[$index] ?? null;

            if ($branch && !empty($branch['stage_id'])) {
                $targetStage = \App\Models\WorkflowStage::find($branch['stage_id']);
                $label = $branch['label'];

                $advanced = $approvalService->forwardToBranch($this->record, $this->module, auth()->user(), $index, $this->approvalComment);

                if (!$advanced) {
                    $this->approvalComment = '';
                    session()->flash('message', "Review forwarded ({$label}). Waiting for other reviewers.");
                    return;
                }

                $this->status = $targetStage?->default_status ?? 'Under Review';
                $this->approvalComment = '';
                session()->flash('message', "Record forwarded: {$label}.");
            } else {
                session()->flash('error', 'Invalid branch.');
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function returnForRevision()
    {
        $approvalService = app(RecordApprovalService::class);
        $approvalService->authorizeApprovalAction($this->record, auth()->user(), $this->moduleSlug);
        $this->validate(['approvalComment' => 'required|string|max:2000'], [], ['approvalComment' => 'revision notes']);

        $approvalService->returnForRevision($this->record, $this->module, auth()->user(), $this->approvalComment);

        $this->status = 'Returned';
        $this->approvalComment = '';
        session()->flash('message', 'Record returned for revision.');
    }

    // ─── Comments & Reviews ───────────────────────────────────────────────────

    public function markReviewDone(string $fieldSlug): void
    {
        if (!$this->record) return;

        $approvalService = app(RecordApprovalService::class);
        if (!$approvalService->canReview(auth()->user(), $this->moduleSlug)) abort(403);

        $autoAdvanced = $approvalService->markReviewDone($this->record, $this->module, auth()->user(), $fieldSlug);

        if ($autoAdvanced) {
            $this->status = 'Completed';
            $this->approvalComment = '';
        }

        $this->dispatch('review-marked-done', fieldSlug: $fieldSlug);
        $this->dispatch('notify', type: 'success', message: 'Review marked as done.');
    }

    public function addComment()
    {
        $this->validate(['newComment' => 'required|string|max:2000']);

        app(RecordCommentService::class)->addComment($this->recordId, auth()->user(), $this->newComment);

        $this->newComment = '';
    }

    public function deleteComment($commentId)
    {
        app(RecordCommentService::class)->deleteComment($commentId, $this->recordId, auth()->user());
    }

    // ─── Helpers & Render ──────────────────────────────────────────────────────

    private function canReview(): bool
    {
        return app(RecordApprovalService::class)->canReview(auth()->user(), $this->moduleSlug);
    }

    private function canAct(): bool
    {
        return app(RecordApprovalService::class)->canAct($this->record, auth()->user(), $this->moduleSlug);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $comments = $this->recordId
            ? RecordComment::where('record_id', $this->recordId)->with('user')->oldest()->get()
            : collect();

        $histories = $this->recordId
            ? RecordHistory::where('record_id', $this->recordId)->with('user')->latest()->get()
            : collect();

        $approvals = $this->recordId
            ? RecordApproval::where('record_id', $this->recordId)->with(['user', 'stage'])->latest()->get()
            : collect();

        $canDeleteComments = auth()->user()->can('delete-comments');

        $hasStages = $this->module->workflowStages()->exists();
        $canAct    = $this->canAct();
        $canReview = $this->canReview();

        $canSubmit = $hasStages
            && $this->module->has_submit_button
            && (!$this->recordId || in_array($this->record->status ?? '', ['Draft', 'Returned']))
            && auth()->user()->can("create-{$this->moduleSlug}");
        $currentStage = $this->record?->currentStage;

        $reviewedFields = $this->recordId
            ? \DB::table('text_editor_reviews')
                ->where('record_id', $this->recordId)
                ->where('user_id', auth()->id())
                ->pluck('field_slug')->all()
            : [];

        $reviewersByField = $this->recordId
            ? \DB::table('text_editor_reviews')
                ->join('users', 'text_editor_reviews.user_id', '=', 'users.id')
                ->where('text_editor_reviews.record_id', $this->recordId)
                ->orderBy('text_editor_reviews.reviewed_at')
                ->select('text_editor_reviews.field_slug', 'users.name')
                ->get()
                ->groupBy('field_slug')
            : collect();

        // Re-mint editor tokens if lost after Livewire re-hydration (protected property not in snapshot)
        if (empty($this->editorTokens)) {
            $this->editorTokens = app(TokenMintingService::class)->mintEditorTokens(
                auth()->user(),
                $this->module,
                $this->recordId
            );
        }
        $editorTokens = $this->editorTokens;

        return view('livewire.builder.dynamic-record-form',
            compact('comments', 'canDeleteComments', 'histories', 'approvals', 'hasStages', 'canAct', 'canReview', 'canSubmit', 'currentStage', 'reviewedFields', 'reviewersByField', 'editorTokens'));
    }
}

