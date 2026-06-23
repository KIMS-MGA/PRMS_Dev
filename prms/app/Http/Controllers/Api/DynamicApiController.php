<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRecordRequest;
use App\Http\Requests\Api\UpdateRecordRequest;
use App\Models\Module;
use App\Models\Record;
use App\Repositories\RecordRepository;
use Illuminate\Http\Request;

class DynamicApiController extends Controller
{
    public function __construct(private RecordRepository $repository) {}

    private function checkAbility(Request $request, string $moduleSlug, string $action): void
    {
        if (!$request->user()->tokenCan("{$moduleSlug}:{$action}")) {
            abort(response()->json(['message' => "Token missing ability: {$moduleSlug}:{$action}"], 403));
        }

        // Also verify the user's live Spatie permission, so revoking a role takes effect immediately
        $spatieAction = match($action) {
            'read'   => "view-{$moduleSlug}",
            'write'  => "create-{$moduleSlug}",
            'delete' => "delete-{$moduleSlug}",
            default  => "view-{$moduleSlug}",
        };
        if (!$request->user()->can($spatieAction)) {
            abort(response()->json(['message' => 'Unauthorized'], 403));
        }
    }

    public function index(Request $request, $moduleSlug)
    {
        $this->checkAbility($request, $moduleSlug, 'read');
        $module = Module::where('slug', $moduleSlug)->firstOrFail();

        $paginator = $this->repository->search($module, $request->only([
            'status', 'search', 'date_from', 'date_to', 'assigned_to',
            'created_by', 'sort_by', 'sort_dir', 'per_page',
        ]));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    public function store(StoreRecordRequest $request, $moduleSlug)
    {
        $this->checkAbility($request, $moduleSlug, 'write');
        $module = Module::where('slug', $moduleSlug)->firstOrFail();

        $record = Record::create([
            'module_id'  => $module->id,
            'data'       => $request->validated()['data'] ?? [],
            'status'     => $module->default_status ?? 'Submitted',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['data' => $record], 201);
    }

    public function show(Request $request, $moduleSlug, $recordId)
    {
        $this->checkAbility($request, $moduleSlug, 'read');
        $module = Module::where('slug', $moduleSlug)->firstOrFail();
        $record = Record::where('module_id', $module->id)->findOrFail($recordId);
        return response()->json(['data' => $record]);
    }

    public function update(UpdateRecordRequest $request, $moduleSlug, $recordId)
    {
        $this->checkAbility($request, $moduleSlug, 'write');
        $module = Module::where('slug', $moduleSlug)->firstOrFail();
        $record = Record::where('module_id', $module->id)->findOrFail($recordId);

        $record->update([
            'data'       => array_merge($record->data ?? [], $request->validated()['data'] ?? []),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['data' => $record]);
    }

    public function destroy(Request $request, $moduleSlug, $recordId)
    {
        $this->checkAbility($request, $moduleSlug, 'delete');
        $module = Module::where('slug', $moduleSlug)->firstOrFail();
        $record = Record::where('module_id', $module->id)->findOrFail($recordId);
        $record->delete();

        return response()->json(['message' => 'Record deleted']);
    }
}