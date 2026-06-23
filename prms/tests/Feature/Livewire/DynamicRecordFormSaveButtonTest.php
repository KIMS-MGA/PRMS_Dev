<?php

use App\Livewire\Builder\DynamicRecordForm;
use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->module = Module::create(['name' => 'Save Button Test', 'slug' => 'save-button-test']);
    Permission::firstOrCreate(['name' => 'edit-save-button-test', 'guard_name' => 'web']);

    $this->proponent = User::factory()->create();
    $this->proponent->givePermissionTo('edit-save-button-test');

    $this->record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $this->proponent->id,
        'updated_by' => $this->proponent->id,
    ]);
});

it('shows a Save Changes button when proponent edits a record in Submitted status', function () {
    Livewire::actingAs($this->proponent)
        ->test(DynamicRecordForm::class, [
            'moduleSlug' => 'save-button-test',
            'record'     => $this->record->id,
        ])
        ->assertSee('Save Changes');
});

it('preserves the record status when Save Changes is called on a Submitted record', function () {
    Livewire::actingAs($this->proponent)
        ->test(DynamicRecordForm::class, [
            'moduleSlug' => 'save-button-test',
            'record'     => $this->record->id,
        ])
        ->call('save');

    expect($this->record->fresh()->status)->toBe('Submitted');
});

it('does not show Save Changes button when record is in Draft status', function () {
    $this->record->update(['status' => 'Draft']);

    Livewire::actingAs($this->proponent)
        ->test(DynamicRecordForm::class, [
            'moduleSlug' => 'save-button-test',
            'record'     => $this->record->id,
        ])
        ->assertDontSee('Save Changes');
});

it('does not show Save Changes button when record is in Returned status', function () {
    $this->record->update(['status' => 'Returned']);

    Livewire::actingAs($this->proponent)
        ->test(DynamicRecordForm::class, [
            'moduleSlug' => 'save-button-test',
            'record'     => $this->record->id,
        ])
        ->assertDontSee('Save Changes');
});
