<?php

namespace App\Http\Requests\Api;

use App\Models\Module;
use App\Support\RecordValidationRuleFactory;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via checkAbility()
    }

    public function rules(): array
    {
        $module = Module::with('fields')
            ->where('slug', $this->route('moduleSlug'))
            ->firstOrFail();

        return RecordValidationRuleFactory::forApi($module->fields);
    }
}
