<?php

use App\Models\Module;
use App\Models\ModuleField;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);

    $this->module = Module::create(['name' => 'API Test', 'slug' => 'api-test']);

    ModuleField::create([
        'module_id'   => $this->module->id,
        'name'        => 'Title',
        'slug'        => 'title',
        'type'        => 'text',
        'is_required' => false,
        'sort_order'  => 1,
    ]);

    $this->user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'view-api-test',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create-api-test', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete-api-test', 'guard_name' => 'web']);
    $this->user->givePermissionTo(['view-api-test', 'create-api-test', 'delete-api-test']);

    Sanctum::actingAs($this->user, ['*']);
});

// ── index / search ────────────────────────────────────────────────────────────

it('returns paginated records for a module', function () {
    Record::create([
        'module_id'  => $this->module->id,
        'data'       => ['title' => 'Hello'],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/dynamic/api-test');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links'])
        ->assertJsonPath('meta.total', 1);
});

it('filters by status', function () {
    Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/dynamic/api-test?status=Draft');

    $response->assertOk()->assertJsonPath('meta.total', 1);
    expect($response->json('data.0.status'))->toBe('Draft');
});

it('filters by date range', function () {
    if (DB::connection()->getDriverName() === 'sqlite') {
        $this->markTestSkipped('Requires MySQL JSON_UNQUOTE — not available on SQLite test driver.');
    }

    Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_at' => '2025-01-15 00:00:00',
    ]);
    Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_at' => '2025-03-20 00:00:00',
    ]);

    $response = $this->getJson('/api/dynamic/api-test?date_from=2025-03-01&date_to=2025-03-31');

    $response->assertOk()->assertJsonPath('meta.total', 1);
});

it('filters by created_by', function () {
    $other = User::factory()->create();

    Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $other->id,
        'updated_by' => $other->id,
    ]);

    $response = $this->getJson("/api/dynamic/api-test?created_by={$this->user->id}");

    $response->assertOk()->assertJsonPath('meta.total', 1);
    expect($response->json('data.0.created_by'))->toBe($this->user->id);
});

it('applies default sorting (created_at desc)', function () {
    $first = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    DB::table('records')->where('id', $first->id)->update(['created_at' => '2025-01-01 00:00:00']);

    $second = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    DB::table('records')->where('id', $second->id)->update(['created_at' => '2025-06-01 00:00:00']);

    $response = $this->getJson('/api/dynamic/api-test');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids[0])->toBe($second->id);
});

it('respects per_page cap at 100', function () {
    $response = $this->getJson('/api/dynamic/api-test?per_page=500');

    $response->assertOk()->assertJsonPath('meta.per_page', 100);
});

it('returns 403 when token lacks read ability', function () {
    $restricted = User::factory()->create();
    $restricted->givePermissionTo('view-api-test');

    Sanctum::actingAs($restricted, ['other-module:read']);

    $this->getJson('/api/dynamic/api-test')->assertStatus(403);
});

// ── store ─────────────────────────────────────────────────────────────────────

it('stores a record via api', function () {
    $response = $this->postJson('/api/dynamic/api-test', [
        'data' => ['title' => 'New Record'],
    ]);

    $response->assertCreated()->assertJsonPath('data.status', $this->module->default_status ?? 'Submitted');
    $this->assertDatabaseHas('records', ['module_id' => $this->module->id]);
});

// ── update ────────────────────────────────────────────────────────────────────

it('updates a record via api', function () {
    $this->user->givePermissionTo('create-api-test');
    Sanctum::actingAs($this->user, ['*']);

    $record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => ['title' => 'Old Title'],
        'status'     => 'Draft',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $this->putJson("/api/dynamic/api-test/{$record->id}", [
        'data' => ['title' => 'New Title'],
    ])->assertOk();

    expect($record->fresh()->data['title'])->toBe('New Title');
});
