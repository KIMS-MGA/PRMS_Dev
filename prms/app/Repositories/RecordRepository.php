<?php

namespace App\Repositories;

use App\Models\Module;
use App\Models\Record;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordRepository
{
    public function search(Module $module, array $filters): LengthAwarePaginator
    {
        $query = Record::where('module_id', $module->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $fields = $module->fields;
            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    if (!preg_match('/^[a-z0-9_\-]+$/', $field->slug)) {
                        continue;
                    }
                    $q->orWhereRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$field->slug}'))) LIKE ?",
                        ['%' . strtolower($search) . '%']
                    );
                }
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        $sortBy  = in_array($filters['sort_by'] ?? null, ['created_at', 'updated_at', 'status'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? '') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $query->paginate($perPage);
    }
}
