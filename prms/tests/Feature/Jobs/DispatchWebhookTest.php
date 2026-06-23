<?php

use App\Jobs\DispatchWebhook;
use App\Models\Module;
use App\Models\Record;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

// ── Job is dispatched (not fired inline) ─────────────────────────────────────

it('dispatches DispatchWebhook job when a webhook event matches', function () {
    Queue::fake();

    $user   = \App\Models\User::factory()->create();
    $module = Module::create(['name' => 'Hook Test', 'slug' => 'hook-test']);
    $webhook = Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.com/hook',
        'events'    => ['record.submitted'],
        'is_active' => true,
    ]);

    $record = Record::create([
        'module_id'  => $module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    event(new \App\Events\RecordSaved($record, 'record.submitted'));

    Queue::assertPushed(DispatchWebhook::class, function ($job) use ($webhook) {
        return $job->webhook->id === $webhook->id
            && $job->event === 'record.submitted';
    });
});

it('does not dispatch a job for inactive webhooks', function () {
    Queue::fake();

    $user   = \App\Models\User::factory()->create();
    $module = Module::create(['name' => 'Hook Test', 'slug' => 'hook-test']);
    Webhook::create([
        'name'      => 'Inactive Hook',
        'url'       => 'https://example.com/hook',
        'events'    => ['record.submitted'],
        'is_active' => false,
    ]);

    $record = Record::create([
        'module_id'  => $module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    event(new \App\Events\RecordSaved($record, 'record.submitted'));

    Queue::assertNotPushed(DispatchWebhook::class);
});

// ── Job handle(): HTTP + WebhookLog ──────────────────────────────────────────

it('writes a WebhookLog entry on successful HTTP response', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $user   = \App\Models\User::factory()->create();
    $module = Module::create(['name' => 'Hook Test', 'slug' => 'hook-test']);
    $webhook = Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.com/hook',
        'events'    => ['*'],
        'is_active' => true,
    ]);
    $record = Record::create([
        'module_id'  => $module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $job = new DispatchWebhook($webhook, 'record.submitted', ['record_id' => $record->id]);
    $job->handle();

    $this->assertDatabaseHas('webhook_logs', [
        'webhook_id'    => $webhook->id,
        'event'         => 'record.submitted',
        'response_code' => 200,
        'success'       => true,
    ]);
});

it('includes HMAC signature header when webhook has a secret', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $webhook = Webhook::create([
        'name'      => 'Signed Hook',
        'url'       => 'https://example.com/hook',
        'events'    => ['*'],
        'is_active' => true,
        'secret'    => 'my-secret',
    ]);

    $payload = ['record_id' => 1];
    $job = new DispatchWebhook($webhook, 'record.submitted', $payload);
    $job->handle();

    Http::assertSent(function ($request) use ($payload) {
        $expected = hash_hmac('sha256', json_encode($payload), 'my-secret');
        return $request->hasHeader('X-PRMS-Signature', $expected);
    });
});

it('omits HMAC signature header when webhook has no secret', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $webhook = Webhook::create([
        'name'      => 'Unsigned Hook',
        'url'       => 'https://example.com/hook',
        'events'    => ['*'],
        'is_active' => true,
        'secret'    => null,
    ]);

    $job = new DispatchWebhook($webhook, 'record.submitted', []);
    $job->handle();

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-PRMS-Signature');
    });
});

it('writes a failed WebhookLog entry and re-throws on HTTP exception', function () {
    Http::fake(['*' => fn () => throw new \RuntimeException('Connection timed out')]);

    $webhook = Webhook::create([
        'name'      => 'Fail Hook',
        'url'       => 'https://example.com/hook',
        'events'    => ['*'],
        'is_active' => true,
    ]);

    $job = new DispatchWebhook($webhook, 'record.submitted', []);

    expect(fn () => $job->handle())
        ->toThrow(\RuntimeException::class, 'Connection timed out');

    $this->assertDatabaseHas('webhook_logs', [
        'webhook_id' => $webhook->id,
        'success'    => false,
    ]);
});
