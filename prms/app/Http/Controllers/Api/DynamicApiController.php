<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Record;
use Illuminate\Http\Request;

class DynamicApiController extends Controller
{
    /**
     * Build per-field validation rules from the module's field definitions.
     * Adds type-appropriate rules so email/number/date/url/boolean fields cannot
     * be stored with malformed data. Ambiguous types (select, user, phone,
     * attachment) stay lenient to preserve backward compatibility with clients.
     */
    private function fieldRules($fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $set = [$field->is_required ? 'required' : 'nullable'];
            switch ($field->type) {
                case 'email':        $set[] = 'email';   break;
                case 'number':
                case 'currency':     $set[] = 'numeric'; break;
                case 'date':         $set[] = 'date';    break;
                case 'url':          $set[] = 'url';     break;
                case 'boolean':      $set[] = 'boolean'; break;
                case 'multi_select': $set[] = 'array';   break;
            }
            $rules['data.' . $field->slug] = $set;
        }
        return $rules;
    }

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

        $query = Record::where('module_id', $module->id);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $fields = $module->fields;
            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    $q->orWhereRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$field->slug}'))) LIKE ?",
                        ['%' . strtolower($search) . '%']
                    );
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Sorting
        $sortBy  = in_array($request->sort_by, ['created_at', 'updated_at', 'status']) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($request->per_page ?? 20), 100);
        $paginator = $query->paginate($perPage);

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

    public function store(Request $request, $moduleSlug)
    {
        $this->checkAbility($request, $moduleSlug, 'write');
        $module = Module::with('fields')->where('slug', $moduleSlug)->firstOrFail();

        $validated = $request->validate($this->fieldRules($module->fields));

        $record = Record::create([
            'module_id'  => $module->id,
            'data'       => $validated['data'] ?? [],
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

    public function update(Request $request, $moduleSlug, $recordId)
    {
        $this->checkAbility($request, $moduleSlug, 'write');
        $module = Module::with('fields')->where('slug', $moduleSlug)->firstOrFail();
        $record = Record::where('module_id', $module->id)->findOrFail($recordId);

        $validated = $request->validate($this->fieldRules($module->fields));

        $record->update([
            'data'       => array_merge($record->data ?? [], $validated['data'] ?? []),
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
