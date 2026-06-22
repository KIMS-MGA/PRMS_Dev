<?php

use App\Support\RecordValidationRuleFactory;

// Helper — builds a plain object resembling a ModuleField without hitting the DB
function field(string $slug, string $type, bool $required = false, bool $versioning = false): object
{
    return (object) [
        'slug'        => $slug,
        'type'        => $type,
        'is_required' => $required,
        'versioning'  => $versioning,
    ];
}

// ── forForm ──────────────────────────────────────────────────────────────────

it('forForm always includes a required string rule for status', function () {
    $rules = RecordValidationRuleFactory::forForm(collect([]));
    expect($rules['status'])->toBe('required|string');
});

it('forForm generates required rule for a required text field', function () {
    $rules = RecordValidationRuleFactory::forForm(collect([field('title', 'text', required: true)]));
    expect($rules['data.title'])->toBe('required');
});

it('forForm generates nullable rule for an optional text field', function () {
    $rules = RecordValidationRuleFactory::forForm(collect([field('notes', 'text', required: false)]));
    expect($rules['data.notes'])->toBe('nullable');
});

it('forForm generates required|array|min:1 for a required multi_select', function () {
    $rules = RecordValidationRuleFactory::forForm(collect([field('tags', 'multi_select', required: true)]));
    expect($rules['data.tags'])->toBe('required|array|min:1');
});

it('forForm generates nullable|array for an optional multi_select', function () {
    $rules = RecordValidationRuleFactory::forForm(collect([field('cats', 'multi_select', required: false)]));
    expect($rules['data.cats'])->toBe('nullable|array');
});

it('forForm requires a versioned attachment when no versions exist', function () {
    $rules = RecordValidationRuleFactory::forForm(
        collect([field('doc', 'attachment', required: true, versioning: true)]),
        null
    );
    expect($rules['data.doc'])->toBe('required');
});

it('forForm makes versioned attachment nullable when record already has versions', function () {
    $record = (object) ['data' => ['doc' => [['path' => 'attachments/file.pdf']]]];
    $rules  = RecordValidationRuleFactory::forForm(
        collect([field('doc', 'attachment', required: true, versioning: true)]),
        $record
    );
    expect($rules['data.doc'])->toBe('nullable');
});

it('forForm makes non-versioned required attachment simply required', function () {
    $rules = RecordValidationRuleFactory::forForm(
        collect([field('img', 'attachment', required: true, versioning: false)]),
        null
    );
    expect($rules['data.img'])->toBe('required');
});

// ── forApi ───────────────────────────────────────────────────────────────────

it('forApi generates email rule for an email field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('email', 'email', required: true)]));
    expect($rules['data.email'])->toContain('required');
    expect($rules['data.email'])->toContain('email');
});

it('forApi generates numeric rule for a number field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('qty', 'number', required: false)]));
    expect($rules['data.qty'])->toContain('numeric');
});

it('forApi generates numeric rule for a currency field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('amount', 'currency', required: false)]));
    expect($rules['data.amount'])->toContain('numeric');
});

it('forApi generates date rule for a date field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('due', 'date', required: false)]));
    expect($rules['data.due'])->toContain('date');
});

it('forApi generates url rule for a url field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('link', 'url', required: false)]));
    expect($rules['data.link'])->toContain('url');
});

it('forApi generates boolean rule for a boolean field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('active', 'boolean', required: false)]));
    expect($rules['data.active'])->toContain('boolean');
});

it('forApi generates array rule for a multi_select field', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('tags', 'multi_select', required: false)]));
    expect($rules['data.tags'])->toContain('array');
});

it('forApi uses only nullable for unrecognised field types', function () {
    $rules = RecordValidationRuleFactory::forApi(collect([field('phone', 'phone', required: false)]));
    expect($rules['data.phone'])->toEqual(['nullable']);
});
