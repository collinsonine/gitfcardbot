<?php

namespace App\Livewire\Admin;

use App\Enums\CashFlowType;
use App\Models\CashFlowLog;
use App\Models\Trade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionTable extends Component
{
    use WithPagination;

    public string $context = 'ledger';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $userId = null;

    public int $perPage = 15;

    public function mount(
        string $context = 'ledger',
        ?int $userId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): void {
        $this->context = $context;
        $this->userId = $userId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    #[On('refresh-transactions')]
    public function refreshTable(): void
    {
        $this->resetPage();
    }

    public function deleteTrade(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);

        CashFlowLog::where('trade_id', $trade->id)->delete();
        $trade->delete();

        $this->resetPage();
        $this->dispatch('refresh-transactions');
    }

    public function deleteEntry(int $entryId): void
    {
        $entry = CashFlowLog::findOrFail($entryId);

        if ($entry->trade_id) {
            $trade = $entry->trade;
            CashFlowLog::where('trade_id', $trade->id)->delete();
            $trade->delete();
        } else {
            $entry->delete();
        }

        $this->resetPage();
        $this->dispatch('refresh-transactions');
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function setQuickPeriod(string $period): void
    {
        $now = now();
        [$this->dateFrom, $this->dateTo] = match ($period) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'week' => [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()],
            'month' => [$now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()],
            'all' => [null, null],
            default => [null, null],
        };
    }

    public function clearFilters(): void
    {
        $this->typeFilter = '';
        $this->statusFilter = '';
        $this->search = '';
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->userId = null;
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->normalizeResults($this->buildQuery()->get());

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="transactions.csv"',
        ];

        $callback = function () use ($rows) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'ID', 'Date', 'Type', 'Status', 'Customer', 'Phone',
                'Card', 'Source', 'Amount (USD)', 'Rate', 'Payout (₦)',
                'Profit (₦)', 'Description',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('Y-m-d H:i') : $row['date'],
                    $row['type_label'],
                    $row['status'] ?? '',
                    $row['customer_name'] ?? '',
                    $row['customer_phone'] ?? '',
                    $row['card_type'] ?? '',
                    $row['source'] ?? '',
                    $row['amount_usd'] !== null ? number_format($row['amount_usd'], 2) : '',
                    $row['rate'] !== null ? number_format($row['rate'], 2) : '',
                    $row['payout'] !== null ? number_format($row['payout'], 2) : '',
                    $row['profit'] !== null ? number_format($row['profit'], 2) : '',
                    $row['description'] ?? '',
                ]);
            }

            fclose($output);
        };

        return Response::streamDownload($callback, 'transactions.csv', $headers);
    }

    public function exportPdf(): \Illuminate\Http\Response
    {
        $rows = $this->normalizeResults($this->buildQuery()->get());
        $aggregates = $this->computeAggregates();

        $pdf = Pdf::loadView('exports.ledger-pdf', [
            'rows' => $rows,
            'aggregates' => $aggregates,
            'context' => $this->context,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        $filename = $this->context === 'trades' ? 'trades.pdf' : 'ledger.pdf';

        return $pdf->download($filename);
    }

    public function render()
    {
        $paginator = $this->buildQuery()->paginate($this->perPage);
        $rows = $this->normalizeResults($paginator->getCollection());
        $aggregates = $this->computeAggregates();

        $normalizedPaginator = new LengthAwarePaginator(
            $rows,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return view('livewire.admin.transaction-table', [
            'rows' => $normalizedPaginator,
            'aggregates' => $aggregates,
        ]);
    }

    private function buildQuery(): Builder
    {
        return match ($this->context) {
            'trades' => $this->buildTradeQuery(),
            default => $this->buildLedgerQuery(),
        };
    }

    private function buildLedgerQuery(): Builder
    {
        $query = CashFlowLog::query()
            ->with('trade.user')
            ->when($this->userId, function ($q) {
                $q->whereHas('trade', fn ($q) => $q->where('user_id', $this->userId));
            })
            ->when($this->typeFilter === 'trade', fn ($q) => $q->whereNotNull('trade_id'))
            ->when($this->typeFilter && $this->typeFilter !== 'trade', function ($q) {
                $typeMap = [
                    'cash_out' => CashFlowType::CashOut,
                    'revenue' => CashFlowType::Revenue,
                    'capital_injection' => CashFlowType::CapitalInjection,
                    'expense' => CashFlowType::Expense,
                ];
                $q->where('type', $typeMap[$this->typeFilter]);
            })
            ->when($this->statusFilter, function ($q) {
                $q->whereHas('trade', fn ($q) => $q->where('status', $this->statusFilter));
            })
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('cash_flow_logs.description', 'like', "%{$this->search}%")
                        ->orWhereHas('trade', function ($q) {
                            $q->where('card_type', 'like', "%{$this->search}%")
                                ->orWhereHas('user', function ($q) {
                                    $q->where('name', 'like', "%{$this->search}%")
                                        ->orWhere('phone_number', 'like', "%{$this->search}%");
                                });
                        });
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('cash_flow_logs.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('cash_flow_logs.created_at', '<=', $this->dateTo));

        $sortColumn = match ($this->sortField) {
            'amount', 'amount_usd' => 'cash_flow_logs.amount',
            default => 'cash_flow_logs.created_at',
        };

        return $query->orderBy($sortColumn, $this->sortDirection);
    }

    private function buildTradeQuery(): Builder
    {
        $query = Trade::query()
            ->with('user')
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('card_type', 'like', "%{$this->search}%")
                        ->orWhere('source', 'like', "%{$this->search}%")
                        ->orWhere('admin_notes', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'like', "%{$this->search}%")
                                ->orWhere('phone_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('trades.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('trades.created_at', '<=', $this->dateTo));

        $sortColumn = match ($this->sortField) {
            'amount', 'amount_usd' => 'trades.amount_usd',
            default => 'trades.created_at',
        };

        return $query->orderBy($sortColumn, $this->sortDirection);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $results
     * @return array<int, array<string, mixed>>
     */
    private function normalizeResults(Collection $results): array
    {
        return match ($this->context) {
            'trades' => $this->normalizeTrades($results),
            default => $this->normalizeLedgerEntries($results),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Trade>  $trades
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTrades(Collection $trades): array
    {
        return $trades->map(function (Trade $trade) {
            $flow = $trade->cashFlowLogs->first();

            return [
                'id' => $trade->id,
                'date' => $trade->created_at,
                'type' => 'trade',
                'type_label' => 'Trade',
                'status' => $trade->status->value,
                'customer_name' => $trade->user?->name,
                'customer_phone' => $trade->user?->phone_number,
                'customer_id' => $trade->user_id,
                'card_type' => $trade->card_type,
                'source' => $trade->source,
                'amount_usd' => (float) $trade->amount_usd,
                'rate' => (float) $trade->rate_paid,
                'payout' => (float) $trade->customer_payout,
                'profit' => (float) $trade->estimated_profit,
                'description' => $trade->admin_notes ?? ($flow?->description ?? ''),
                'trade_id' => $trade->id,
                'flow_id' => $flow?->id,
            ];
        })->toArray();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CashFlowLog>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLedgerEntries(Collection $entries): array
    {
        return $entries->map(function (CashFlowLog $entry) {
            $trade = $entry->trade;
            $user = $trade?->user;

            return [
                'id' => $entry->id,
                'date' => $entry->created_at,
                'type' => $entry->type->value,
                'type_label' => match ($entry->type) {
                    CashFlowType::CashOut => 'Cash Out',
                    CashFlowType::Revenue => 'Revenue',
                    CashFlowType::CapitalInjection => 'Capital In',
                    CashFlowType::Expense => 'Expense',
                },
                'status' => $trade?->status?->value,
                'customer_name' => $user?->name,
                'customer_phone' => $user?->phone_number,
                'customer_id' => $user?->id,
                'card_type' => $trade?->card_type,
                'source' => $trade?->source,
                'amount_usd' => $trade ? (float) $trade->amount_usd : null,
                'rate' => $trade ? (float) $trade->rate_paid : null,
                'payout' => in_array($entry->type, [CashFlowType::CashOut, CashFlowType::Expense])
                    ? (float) $entry->amount
                    : null,
                'profit' => $entry->type === CashFlowType::Revenue
                    ? (float) $entry->amount
                    : null,
                'description' => $entry->description,
                'trade_id' => $trade?->id,
                'flow_id' => $entry->id,
            ];
        })->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function computeAggregates(): array
    {
        return match ($this->context) {
            'trades' => $this->computeTradeAggregates(),
            default => $this->computeLedgerAggregates(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function computeTradeAggregates(): array
    {
        $base = Trade::query()
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        return [
            'total_volume' => (clone $base)->sum('amount_usd'),
            'total_profit' => (clone $base)->sum('estimated_profit'),
            'approved_volume' => (clone $base)->clone()->where('status', 'approved')->sum('amount_usd'),
            'approved_profit' => (clone $base)->clone()->where('status', 'approved')->sum('estimated_profit'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function computeLedgerAggregates(): array
    {
        $base = CashFlowLog::query()
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        return [
            'total_revenue' => (clone $base)->where('type', CashFlowType::Revenue)->sum('amount'),
            'total_cash_out' => (clone $base)->where('type', CashFlowType::CashOut)->sum('amount'),
            'total_capital' => (clone $base)->where('type', CashFlowType::CapitalInjection)->sum('amount'),
            'total_expenses' => (clone $base)->where('type', CashFlowType::Expense)->sum('amount'),
        ];
    }
}
