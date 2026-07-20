<?php

namespace App\Enums;

enum CashFlowType: string
{
    case CashOut = 'cash_out';
    case Revenue = 'revenue';
    case CapitalInjection = 'capital_injection';
    case Expense = 'expense';
}
