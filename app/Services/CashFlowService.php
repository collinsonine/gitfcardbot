<?php

namespace App\Services;

use App\Enums\CashFlowType;
use App\Models\CashFlowLog;
use App\Models\Trade;

class CashFlowService
{
    public function recordTradeApproval(Trade $trade): CashFlowLog
    {
        return CashFlowLog::create([
            'trade_id' => $trade->id,
            'type' => CashFlowType::CashOut,
            'amount' => $trade->customer_payout,
            'description' => "Trade #{$trade->id} approved - {$trade->card_type} \${$trade->amount_usd}",
        ]);
    }

    public function recordRevenue(Trade $trade): ?CashFlowLog
    {
        if ($trade->estimated_profit <= 0) {
            return null;
        }

        return CashFlowLog::create([
            'trade_id' => $trade->id,
            'type' => CashFlowType::Revenue,
            'amount' => $trade->estimated_profit,
            'description' => "Profit from Trade #{$trade->id} - {$trade->card_type}",
        ]);
    }

    public function recordCapitalInjection(float $amount, string $description): CashFlowLog
    {
        return CashFlowLog::create([
            'trade_id' => null,
            'type' => CashFlowType::CapitalInjection,
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    public function recordExpense(float $amount, string $description): CashFlowLog
    {
        return CashFlowLog::create([
            'trade_id' => null,
            'type' => CashFlowType::Expense,
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    public function getTotalCashOut(): float
    {
        return (float) CashFlowLog::where('type', CashFlowType::CashOut)->sum('amount');
    }

    public function getTotalRevenue(): float
    {
        return (float) CashFlowLog::where('type', CashFlowType::Revenue)->sum('amount');
    }

    public function getTotalCapitalIn(): float
    {
        return (float) CashFlowLog::where('type', CashFlowType::CapitalInjection)->sum('amount');
    }

    public function getTotalExpenses(): float
    {
        return (float) CashFlowLog::where('type', CashFlowType::Expense)->sum('amount');
    }

    public function getAvailableFloat(): float
    {
        return $this->getTotalCapitalIn()
            - $this->getTotalCashOut()
            - $this->getTotalExpenses();
    }

    public function getCashOutToday(): float
    {
        return (float) CashFlowLog::where('type', CashFlowType::CashOut)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');
    }

    public function getRevenueToday(): float
    {
        return (float) CashFlowLog::where('type', CashFlowType::Revenue)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');
    }

    public function onTradeApproved(Trade $trade): void
    {
        $this->recordTradeApproval($trade);
        $this->recordRevenue($trade);
    }
}
