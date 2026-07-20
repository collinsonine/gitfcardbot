<?php

namespace App\Livewire\Admin;

use App\Services\ServiceMonitor;
use Livewire\Component;

class ServiceManager extends Component
{
    public array $bridgeHealth = [];

    public array $whatsappStatus = [];

    public array $queueStatus = [];

    public array $schedulerStatus = [];

    public string $overallStatus = 'unknown';

    public bool $checking = false;

    public bool $restarting = false;

    public ?string $lastCheck = null;

    public string $restartMessage = '';

    protected ServiceMonitor $monitor;

    public function boot(): void
    {
        $this->monitor = app(ServiceMonitor::class);
    }

    public function mount(): void
    {
        $this->checkAll();
    }

    public function checkAll(): void
    {
        $this->checking = true;

        $this->bridgeHealth = $this->monitor->getBridgeHealth();
        $this->whatsappStatus = $this->monitor->getWhatsAppStatus();
        $this->queueStatus = $this->monitor->getQueueStatus();
        $this->schedulerStatus = $this->monitor->getSchedulerStatus();
        $this->overallStatus = $this->monitor->getOverallStatus();
        $this->lastCheck = now()->format('H:i:s');
        $this->checking = false;
    }

    public function restartBridge(): void
    {
        $this->restarting = true;
        $this->restartMessage = '';

        $result = $this->monitor->restartBridge();

        $this->restartMessage = $result['message'];

        if ($result['success']) {
            $this->dispatch('bridge-restarted');
        }

        $this->restarting = false;
    }

    public function render()
    {
        return view('livewire.admin.service-manager')
            ->layout('layouts.admin', ['title' => 'Services']);
    }
}
