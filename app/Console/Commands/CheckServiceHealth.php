<?php

namespace App\Console\Commands;

use App\Services\ServiceMonitor;
use Illuminate\Console\Command;

class CheckServiceHealth extends Command
{
    protected $signature = 'services:check {--json : Output as JSON} {--restart : Auto-restart unhealthy services}';

    protected $description = 'Check health of all system services';

    public function handle(ServiceMonitor $monitor): int
    {
        $bridge = $monitor->getBridgeHealth();
        $whatsapp = $monitor->getWhatsAppStatus();
        $overall = $monitor->getOverallStatus();

        if ($this->option('json')) {
            $this->line(json_encode([
                'overall' => $overall,
                'bridge' => $bridge,
                'whatsapp' => $whatsapp,
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Service Health Check');
        $this->line(str_repeat('-', 50));

        $statusIcon = match ($overall) {
            'healthy' => '<fg=green>●</>',
            'degraded' => '<fg=yellow>●</>',
            default => '<fg=red>●</>',
        };

        $this->line("Overall: {$statusIcon} {$overall}");
        $this->newLine();

        $bridgeIcon = match ($bridge['status'] ?? 'offline') {
            'online' => '<fg=green>●</>',
            default => '<fg=red>●</>',
        };

        $this->line("WhatsApp Bridge: {$bridgeIcon} {$bridge['bridge_status'] ?? 'offline'}");

        if ($bridge['status'] === 'online') {
            $this->line("  Phone: {$bridge['phone']}");
            $this->line("  Uptime: {$bridge['uptime_human']}");
            $this->line("  Memory: {$bridge['memory_mb']} MB");
            $this->line("  Messages: {$bridge['messages_processed']}");
        } else {
            $this->line("  Error: {$bridge['error']}");
        }

        $this->newLine();

        if ($this->option('restart') && ($bridge['status'] === 'offline' || ($bridge['bridge_status'] ?? '') !== 'connected')) {
            $this->warn('Auto-restarting WhatsApp bridge...');
            $result = $monitor->restartBridge();

            if ($result['success']) {
                $this->info("  {$result['message']}");
            } else {
                $this->error("  {$result['message']}");
            }
        }

        return self::SUCCESS;
    }
}
