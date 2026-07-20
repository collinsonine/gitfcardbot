<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class WhatsAppPanel extends Component
{
    public ?string $status = null;

    public ?string $phone = null;

    public ?string $error = null;

    public bool $checking = false;

    public ?string $qrCode = null;

    public string $broadcastMessage = '';

    public bool $broadcastSending = false;

    public array $broadcastResults = [];

    private const int BROADCAST_CHUNK = 20;

    public function mount(): void
    {
        $this->checkConnection();
    }

    public function checkConnection(): void
    {
        $this->checking = true;
        $this->error = null;

        try {
            $bridgeUrl = config('whatsapp.node_bridge_url', 'http://127.0.0.1:3001');
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->get("{$bridgeUrl}/api/health");

            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['client_ready'] ? 'connected' : 'authenticating';
                $this->phone = $data['phone'] ?? null;

                if (! $data['client_ready'] && ($data['has_qr'] ?? false)) {
                    $this->fetchQrCode();
                }
            } else {
                $this->status = 'error';
                $this->error = "Bridge returned status {$response->status()}";
            }
        } catch (ConnectionException $e) {
            $this->status = 'disconnected';
            $this->error = 'Could not reach the WhatsApp bridge service.';
        } catch (\Exception $e) {
            $this->status = 'error';
            $this->error = $e->getMessage();
        } finally {
            $this->checking = false;
        }
    }

    public function fetchQrCode(): void
    {
        try {
            $bridgeUrl = config('whatsapp.node_bridge_url', 'http://127.0.0.1:3001');
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->get("{$bridgeUrl}/api/qr");

            if ($response->successful()) {
                $data = $response->json();
                $this->qrCode = $data['qr'] ?? null;
            }
        } catch (\Exception $e) {
            // silently fail, QR is not critical
        }
    }

    public function disconnect(): void
    {
        $this->error = null;

        try {
            $bridgeUrl = config('whatsapp.node_bridge_url', 'http://127.0.0.1:3001');
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->post("{$bridgeUrl}/api/disconnect");

            if ($response->successful()) {
                $this->status = 'authenticating';
                $this->phone = null;
                $this->qrCode = null;
                $this->fetchQrCode();
            } else {
                $this->error = 'Failed to disconnect: bridge returned status '.$response->status();
            }
        } catch (ConnectionException $e) {
            $this->error = 'Could not reach the WhatsApp bridge service to disconnect.';
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function getStatusClassProperty(): string
    {
        return match ($this->status) {
            'connected' => 'bg-emerald-50 border border-emerald-100',
            'authenticating' => 'bg-amber-50 border border-amber-100',
            'disconnected' => 'bg-red-50 border border-red-100',
            default => 'bg-gray-50 border border-gray-100',
        };
    }

    public function getDotClassProperty(): string
    {
        return match ($this->status) {
            'connected' => 'bg-emerald-500',
            'authenticating' => 'bg-amber-500',
            'disconnected' => 'bg-red-500',
            default => 'bg-gray-400',
        };
    }

    public function getStatusTextClassProperty(): string
    {
        return match ($this->status) {
            'connected' => 'text-emerald-800',
            'authenticating' => 'text-amber-800',
            'disconnected' => 'text-red-800',
            default => 'text-gray-800',
        };
    }

    public function getStatusLabelProperty(): string
    {
        return match ($this->status) {
            'connected' => 'Connected',
            'authenticating' => 'Authenticating...',
            'disconnected' => 'Disconnected',
            'error' => 'Error',
            null => 'Not checked',
            default => ucfirst((string) $this->status),
        };
    }

    public function sendBroadcast(): void
    {
        $this->validate(['broadcastMessage' => 'required|string|max:4096']);

        $this->broadcastSending = true;
        $this->broadcastResults = [];

        $users = User::whereNotNull('phone_number')
            ->where('is_bot_paused', false)
            ->get();

        $whatsapp = app(WhatsAppService::class);
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $phone = $user->whatsapp_id ?? $user->phone_number;

            $ok = $whatsapp->sendMessage($phone, $this->broadcastMessage);

            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->broadcastResults = [
            'sent' => $sent,
            'failed' => $failed,
            'total' => $users->count(),
        ];

        $this->broadcastSending = false;
    }

    public function render()
    {
        return view('livewire.admin.whatsapp-panel', [
            'statusClass' => $this->statusClass,
            'dotClass' => $this->dotClass,
            'statusTextClass' => $this->statusTextClass,
            'statusLabel' => $this->statusLabel,
        ])->layout('layouts.admin', ['title' => 'WhatsApp']);
    }
}
