<?php

namespace App\Support;

use Illuminate\Support\Collection;

class RecordValidationRuleFactory
{
    /**
     * Validation rules for the Livewire record form.
     * Handles multi_select arrays, versioned attachment presence, and all other field types.
     *
     * @param  Collection  $fields  Collection of ModuleField models (or compatible objects)
     * @param  object|null $record  Existing record whose data is checked for attachment versions
     */
    public static function forForm(Collection $fields, ?object $record = null): array
    {
        $rules = ['status' => 'required|string'];

        foreach ($fields as $field) {
            if ($field->type === 'multi_select') {
                $rules['data.' . $field->slug] = $field->is_required
                    ? 'required|array|min:1'
                    : 'nullable|array';
            } elseif ($field->type === 'attachment' && $field->versioning) {
                $hasVersions = !empty($record?->data[$field->slug]);
                $rules['data.' . $field->slug] = ($field->is_required && !$hasVersions)
                    ? 'required'
                    : 'nullable';
            } else {
                $rules['data.' . $field->slug] = $field->is_required ? 'required' : 'nullable';
            }
        }

        return $rules;
    }

    /**
     * Validation rules for the REST API controller.
     * Adds type-appropriate Laravel rules (email, numeric, date, url, boolean, array).
     *
     * @param  Collection  $fields  Collection of ModuleField models (or compatible objects)
     */
    public static function forApi(Collection $fields): array
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
}
