<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Webhook $webhook,
        public readonly string  $event,
        public readonly array   $payload,
    ) {}

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-PRMS-Event' => $this->event,
        ];

        if ($this->webhook->secret) {
            $headers['X-PRMS-Signature'] = hash_hmac(
                'sha256',
                json_encode($this->payload),
                $this->webhook->secret
            );
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($this->webhook->url, $this->payload);

            WebhookLog::create([
                'webhook_id'    => $this->webhook->id,
                'event'         => $this->event,
                'payload'       => $this->payload,
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 1000),
                'success'       => $response->successful(),
            ]);
        } catch (\Throwable $e) {
            WebhookLog::create([
                'webhook_id'    => $this->webhook->id,
                'event'         => $this->event,
                'payload'       => $this->payload,
                'response_code' => null,
                'response_body' => substr($e->getMessage(), 0, 1000),
                'success'       => false,
            ]);
            throw $e;
        }
    }
}
