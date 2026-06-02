<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);

    $this->owner  = User::factory()->create();
    $this->module = Module::create(['name' => 'Docs', 'slug' => 'docs']);
    $this->record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->owner->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Group 1 — text-editor write endpoints must enforce record access
|--------------------------------------------------------------------------
*/

it('forbids storeComment for a user without record access', function () {
    $outsider = User::factory()->create(); // no roles, no module permissions
    Sanctum::actingAs($outsider);

    $this->postJson("/api/text-editor/{$this->record->id}/body/comments", [
        'comment_id'  => (string) Str::uuid(),
        'quoted_text' => 'some text',
        'body'        => 'should be blocked',
    ])->assertForbidden();
});

it('forbids storeHistory for a user without record access', function () {
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);

    $this->postJson("/api/text-editor/{$this->record->id}/body/history", [
        'action'  => 'insert',
        'content' => '<img src=x onerror=alert(1)>',
    ])->assertForbidden();
});

it('still allows a permitted user to comment', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'view-docs', 'guard_name' => 'web'])
    );
    Sanctum::actingAs($user);

    $this->postJson("/api/text-editor/{$this->record->id}/body/comments", [
        'comment_id'  => (string) Str::uuid(),
        'quoted_text' => 'q',
        'body'        => 'allowed comment',
    ])->assertCreated();
});

/*
|--------------------------------------------------------------------------
| Group 2 — /builder admin surface must require super admin
|--------------------------------------------------------------------------
*/

function routeMiddleware(string $name): array
{
    $route = app('router')->getRoutes()->getByName($name);
    expect($route)->not->toBeNull();

    return $route->gatherMiddleware();
}

it('gates every platform-admin builder route behind role:super admin', function () {
    $adminRoutes = [
        'builder.modules.index',
        'builder.modules.create',
        'builder.modules.edit',
        'builder.workflow.manager',
        'builder.audit',
        'builder.webhooks',
        'builder.api.manager',
    ];

    foreach ($adminRoutes as $name) {
        expect(routeMiddleware($name))->toContain('role:super admin');
    }
});

it('leaves operational builder routes reachable by non-super-admin roles', function () {
    // These components authorize per-record/permission internally and must NOT be
    // locked behind super admin, or Reviewers / TRC Secretariat lose access.
    expect(routeMiddleware('builder.approval.queue'))->not->toContain('role:super admin');
    expect(routeMiddleware('builder.workflow.stages'))->not->toContain('role:super admin');
});

/*
|--------------------------------------------------------------------------
| Group 3a — CSV export must neutralize spreadsheet formula injection
|--------------------------------------------------------------------------
*/

it('prefixes formula-leading CSV cells so they cannot execute in a spreadsheet', function () {
    \App\Models\ModuleField::create([
        'module_id'   => $this->module->id,
        'name'        => 'Title',
        'slug'        => 'title',
        'type'        => 'text',
        'is_required' => false,
        'sort_order'  => 1,
    ]);

    $this->record->update(['data' => ['title' => '=HYPERLINK("http://evil","x")'], 'status' => 'Submitted']);

    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'view-docs', 'guard_name' => 'web'])
    );
    $this->actingAs($user);

    // Invoke the controller directly: the neutralization lives here, and the
    // app's full web-middleware stack is not exercised by tests (separate issue).
    $response = app(\App\Http\Controllers\DynamicRecordController::class)->exportCsv('docs');
    $csv = $response->getContent();

    expect($csv)->toContain("'=HYPERLINK");          // neutralized
    expect($csv)->not->toMatch('/(^|\n)=HYPERLINK/'); // never starts a cell raw
});

/*
|--------------------------------------------------------------------------
| Group 3b — DynamicApi enforces per-field type validation
|--------------------------------------------------------------------------
*/

it('rejects a malformed value for a typed field via the dynamic API', function () {
    \App\Models\ModuleField::create([
        'module_id'   => $this->module->id,
        'name'        => 'Contact Email',
        'slug'        => 'contact_email',
        'type'        => 'email',
        'is_required' => true,
        'sort_order'  => 1,
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::firstOrCreate(['name' => 'create-docs', 'guard_name' => 'web'])
    );
    Sanctum::actingAs($user, ['docs:write']);

    $this->postJson('/api/dynamic/docs', ['data' => ['contact_email' => 'not-an-email']])
        ->assertStatus(422);

    $this->postJson('/api/dynamic/docs', ['data' => ['contact_email' => 'real@example.com']])
        ->assertCreated();
});
