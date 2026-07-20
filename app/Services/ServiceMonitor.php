<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class ServiceMonitor
{
    private string $bridgeUrl;

    private string $bridgeSecret;

    public function __construct()
    {
        $this->bridgeUrl = config('whatsapp.node_bridge_url', 'http://127.0.0.1:3001');
        $this->bridgeSecret = config('whatsapp.bridge_secret', 'change-me');
    }

    public function getBridgeHealth(): array
    {
        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->get("{$this->bridgeUrl}/api/health");

            if ($response->failed()) {
                return $this->buildOfflineStatus('Bridge returned HTTP '.$response->status());
            }

            $data = $response->json();

            return [
                'status' => 'online',
                'bridge_status' => $data['bridge_status'] ?? 'unknown',
                'client_ready' => $data['client_ready'] ?? false,
                'phone' => $data['phone'] ?? null,
                'has_qr' => $data['has_qr'] ?? false,
                'uptime_seconds' => $data['uptime_seconds'] ?? 0,
                'uptime_human' => $data['uptime_human'] ?? '0s',
                'messages_processed' => $data['messages_processed'] ?? 0,
                'last_message_at' => $data['last_message_at'] ?? null,
                'memory_mb' => $data['memory_mb'] ?? 0,
                'node_version' => $data['node_version'] ?? 'unknown',
                'pid' => $data['pid'] ?? null,
                'started_at' => $data['started_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            return $this->buildOfflineStatus($e->getMessage());
        }
    }

    public function getWhatsAppStatus(): array
    {
        $health = $this->getBridgeHealth();

        if ($health['status'] === 'offline') {
            return [
                'status' => 'offline',
                'label' => 'Offline',
                'color' => 'red',
                'phone' => null,
            ];
        }

        $bridgeStatus = $health['bridge_status'];

        return match ($bridgeStatus) {
            'connected' => [
                'status' => 'connected',
                'label' => 'Connected',
                'color' => 'emerald',
                'phone' => $health['phone'],
            ],
            'awaiting_qr' => [
                'status' => 'awaiting_qr',
                'label' => 'Awaiting QR Scan',
                'color' => 'amber',
                'phone' => null,
            ],
            'initializing' => [
                'status' => 'initializing',
                'label' => 'Initializing',
                'color' => 'amber',
                'phone' => null,
            ],
            'restarting' => [
                'status' => 'restarting',
                'label' => 'Restarting...',
                'color' => 'blue',
                'phone' => null,
            ],
            default => [
                'status' => 'unknown',
                'label' => 'Unknown',
                'color' => 'gray',
                'phone' => null,
            ],
        };
    }

    public function getQueueStatus(): array
    {
        try {
            $result = Process::run('php artisan queue:monitor --once 2>/dev/null || echo "not_running"');

            $output = $result->output();

            if (str_contains($output, 'not_running') || $result->exitCode() !== 0) {
                return [
                    'status' => 'stopped',
                    'label' => 'Stopped',
                    'color' => 'red',
                    'processes' => 0,
                ];
            }

            return [
                'status' => 'running',
                'label' => 'Running',
                'color' => 'emerald',
                'processes' => 1,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unknown',
                'label' => 'Unknown',
                'color' => 'gray',
                'processes' => 0,
            ];
        }
    }

    public function getSchedulerStatus(): array
    {
        try {
            $result = Process::run('pgrep -f "artisan schedule" 2>/dev/null || echo ""');
            $output = trim($result->output());

            if ($output === '') {
                return [
                    'status' => 'stopped',
                    'label' => 'Not Running',
                    'color' => 'red',
                ];
            }

            return [
                'status' => 'running',
                'label' => 'Running',
                'color' => 'emerald',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unknown',
                'label' => 'Unknown',
                'color' => 'gray',
            ];
        }
    }

    public function restartBridge(): array
    {
        $bridgeHealth = $this->getBridgeHealth();

        if ($bridgeHealth['status'] === 'offline') {
            return ['success' => false, 'message' => 'Bridge is offline — start it with: cd bridge && node bot.js'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-Bridge-Secret' => $this->bridgeSecret])
                ->post("{$this->bridgeUrl}/api/restart");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Bridge restart initiated'];
            }

            return ['success' => false, 'message' => 'Bridge returned HTTP '.$response->status()];
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Could not reach bridge — is it running?'];
        }
    }

    public function getOverallStatus(): string
    {
        $bridge = $this->getBridgeHealth();

        if ($bridge['status'] === 'offline') {
            return 'degraded';
        }

        if ($bridge['bridge_status'] !== 'connected') {
            return 'degraded';
        }

        return 'healthy';
    }

    private function buildOfflineStatus(string $reason): array
    {
        return [
            'status' => 'offline',
            'bridge_status' => 'offline',
            'client_ready' => false,
            'phone' => null,
            'has_qr' => false,
            'uptime_seconds' => 0,
            'uptime_human' => '0s',
            'messages_processed' => 0,
            'last_message_at' => null,
            'memory_mb' => 0,
            'node_version' => 'unknown',
            'pid' => null,
            'started_at' => null,
            'error' => $reason,
        ];
    }
}
