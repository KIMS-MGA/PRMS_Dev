<?php

use App\Models\Module;
use App\Models\ModuleField;

it('resolvedModuleId returns own id when no source module', function () {
    $module = Module::create([
        'name'             => 'Direct',
        'slug'             => 'direct',
        'source_module_id' => null,
    ]);

    expect($module->resolvedModuleId())->toBe($module->id);
});

it('resolvedModuleId returns source_module_id when set', function () {
    $source = Module::create(['name' => 'Source', 'slug' => 'source']);
    $mirror = Module::create([
        'name'             => 'Mirror',
        'slug'             => 'mirror',
        'source_module_id' => $source->id,
    ]);

    expect($mirror->resolvedModuleId())->toBe($source->id);
});

it('resolvedFields returns own fields when no source module', function () {
    $module = Module::create(['name' => 'Solo', 'slug' => 'solo']);
    ModuleField::create(['module_id' => $module->id, 'name' => 'Title', 'slug' => 'title', 'type' => 'text']);

    $fields = $module->resolvedFields();

    expect($fields)->toHaveCount(1);
    expect($fields->first()->slug)->toBe('title');
});

it('resolvedFields merges source fields before own fields', function () {
    $source = Module::create(['name' => 'Base', 'slug' => 'base']);
    ModuleField::create(['module_id' => $source->id, 'name' => 'Ref No', 'slug' => 'ref_no', 'type' => 'text']);

    $mirror = Module::create(['name' => 'Mirror', 'slug' => 'mirror', 'source_module_id' => $source->id]);
    ModuleField::create(['module_id' => $mirror->id, 'name' => 'Extra', 'slug' => 'extra', 'type' => 'text']);

    $fields = $mirror->resolvedFields();

    expect($fields)->toHaveCount(2);
    expect($fields->first()->slug)->toBe('ref_no');
    expect($fields->last()->slug)->toBe('extra');
});
