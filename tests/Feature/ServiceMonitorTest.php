<?php

use App\Services\ServiceMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('returns offline status when bridge is unreachable', function () {
    Http::fake(function ($request) {
        throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
    });

    $monitor = app(ServiceMonitor::class);
    $health = $monitor->getBridgeHealth();

    expect($health['status'])->toBe('offline');
    expect($health['client_ready'])->toBeFalse();
    expect($health['uptime_seconds'])->toBe(0);
    expect($health['error'])->not->toBeEmpty();
});

it('returns online status when bridge responds with connected client', function () {
    Http::fake([
        '127.0.0.1:3001/api/health' => Http::response([
            'status' => 'ok',
            'bridge_status' => 'connected',
            'client_ready' => true,
            'phone' => '2348012345678',
            'has_qr' => false,
            'uptime_seconds' => 3600,
            'uptime_human' => '1h 0m',
            'messages_processed' => 42,
            'last_message_at' => '2026-07-17T10:30:00Z',
            'memory_mb' => 128,
            'node_version' => 'v20.0.0',
            'pid' => 12345,
            'started_at' => '2026-07-17T09:30:00Z',
        ], 200),
    ]);

    $monitor = app(ServiceMonitor::class);
    $health = $monitor->getBridgeHealth();

    expect($health['status'])->toBe('online');
    expect($health['client_ready'])->toBeTrue();
    expect($health['bridge_status'])->toBe('connected');
    expect($health['phone'])->toBe('2348012345678');
    expect($health['uptime_seconds'])->toBe(3600);
    expect($health['messages_processed'])->toBe(42);
    expect($health['memory_mb'])->toBe(128);
});

it('returns correct whatsapp status for connected state', function () {
    Http::fake([
        '127.0.0.1:3001/api/health' => Http::response([
            'status' => 'ok',
            'bridge_status' => 'connected',
            'client_ready' => true,
            'phone' => '2348012345678',
            'has_qr' => false,
            'uptime_seconds' => 100,
            'uptime_human' => '1m 40s',
            'messages_processed' => 5,
            'last_message_at' => null,
            'memory_mb' => 64,
            'node_version' => 'v20.0.0',
            'pid' => 9999,
            'started_at' => null,
        ], 200),
    ]);

    $monitor = app(ServiceMonitor::class);
    $status = $monitor->getWhatsAppStatus();

    expect($status['status'])->toBe('connected');
    expect($status['label'])->toBe('Connected');
    expect($status['color'])->toBe('emerald');
    expect($status['phone'])->toBe('2348012345678');
});

it('returns awaiting_qr status when bridge is online but not authenticated', function () {
    Http::fake([
        '127.0.0.1:3001/api/health' => Http::response([
            'status' => 'ok',
            'bridge_status' => 'awaiting_qr',
            'client_ready' => false,
            'phone' => null,
            'has_qr' => true,
            'uptime_seconds' => 10,
            'uptime_human' => '10s',
            'messages_processed' => 0,
            'last_message_at' => null,
            'memory_mb' => 32,
            'node_version' => 'v20.0.0',
            'pid' => 8888,
            'started_at' => null,
        ], 200),
    ]);

    $monitor = app(ServiceMonitor::class);
    $status = $monitor->getWhatsAppStatus();

    expect($status['status'])->toBe('awaiting_qr');
    expect($status['label'])->toBe('Awaiting QR Scan');
    expect($status['color'])->toBe('amber');
    expect($status['phone'])->toBeNull();
});

it('reports offline whatsapp status when bridge is down', function () {
    Http::fake(function ($request) {
        throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
    });

    $monitor = app(ServiceMonitor::class);
    $status = $monitor->getWhatsAppStatus();

    expect($status['status'])->toBe('offline');
    expect($status['label'])->toBe('Offline');
    expect($status['color'])->toBe('red');
});

it('reports overall status as degraded when bridge is offline', function () {
    Http::fake(function ($request) {
        throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
    });

    $monitor = app(ServiceMonitor::class);
    expect($monitor->getOverallStatus())->toBe('degraded');
});

it('reports overall status as healthy when bridge is connected', function () {
    Http::fake([
        '127.0.0.1:3001/api/health' => Http::response([
            'status' => 'ok',
            'bridge_status' => 'connected',
            'client_ready' => true,
            'phone' => '2348012345678',
            'has_qr' => false,
            'uptime_seconds' => 100,
            'uptime_human' => '1m 40s',
            'messages_processed' => 5,
            'last_message_at' => null,
            'memory_mb' => 64,
            'node_version' => 'v20.0.0',
            'pid' => 9999,
            'started_at' => null,
        ], 200),
    ]);

    $monitor = app(ServiceMonitor::class);
    expect($monitor->getOverallStatus())->toBe('healthy');
});

it('restart bridge sends correct request with secret when online', function () {
    Http::fake([
        '127.0.0.1:3001/api/health' => Http::response([
            'status' => 'ok',
            'bridge_status' => 'connected',
            'client_ready' => true,
            'phone' => '2348012345678',
            'has_qr' => false,
            'uptime_seconds' => 100,
            'uptime_human' => '1m 40s',
            'messages_processed' => 5,
            'last_message_at' => null,
            'memory_mb' => 64,
            'node_version' => 'v20.0.0',
            'pid' => 9999,
            'started_at' => null,
        ], 200),
        '127.0.0.1:3001/api/restart' => Http::response([
            'success' => true,
            'message' => 'Restarting WhatsApp client...',
        ], 200),
    ]);

    $monitor = app(ServiceMonitor::class);
    $result = $monitor->restartBridge();

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Bridge restart initiated');

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:3001/api/restart'
            && $request->hasHeader('X-Bridge-Secret');
    });
});

it('restart bridge returns offline message when bridge is down', function () {
    Http::fake(function ($request) {
        throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
    });

    $monitor = app(ServiceMonitor::class);
    $result = $monitor->restartBridge();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('offline');
});
