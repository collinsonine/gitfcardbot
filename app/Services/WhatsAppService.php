<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $nodeBridgeUrl;

    public function __construct()
    {
        $this->nodeBridgeUrl = config('whatsapp.node_bridge_url', 'http://127.0.0.1:3001');
    }

    public function sendMessage(string $phoneNumber, string $message): bool
    {
        return $this->sendToBridge($this->nodeBridgeUrl.'/api/send-message', [
            'phone' => $phoneNumber,
            'message' => $message,
        ]);
    }

    public function sendListMessage(
        string $phoneNumber,
        string $body,
        array $sections,
        string $buttonText = 'Select',
        ?string $title = null,
        ?string $footer = null,
    ): bool {
        return $this->sendToBridge($this->nodeBridgeUrl.'/api/send-message', [
            'phone' => $phoneNumber,
            'message' => $body,
            'interactive' => [
                'type' => 'list',
                'body' => $body,
                'buttonText' => $buttonText,
                'sections' => $sections,
                'title' => $title,
                'footer' => $footer,
            ],
        ]);
    }

    public function sendMedia(string $phoneNumber, string $base64Data, string $mimetype, ?string $filename = null, ?string $caption = null): bool
    {
        return $this->sendToBridge($this->nodeBridgeUrl.'/api/send-media', [
            'phone' => $phoneNumber,
            'media' => $base64Data,
            'mimetype' => $mimetype,
            'filename' => $filename,
            'caption' => $caption,
        ]);
    }

    private function sendToBridge(string $url, array $payload): bool
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(5)
                ->retry(2, 100)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('WhatsApp bridge send failed', [
                    'phone' => $payload['phone'] ?? 'unknown',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException $e) {
            Log::error('WhatsApp bridge connection failed', [
                'phone' => $payload['phone'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
