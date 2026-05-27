<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Account;
use App\Models\JournalEntry;
use Carbon\Carbon;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;

class TrialBalancePage extends Page
{
    protected string $title = 'Оборотно-сальдовая ведомость';

    public function components(): array
    {
        $from = request('date_from')
            ? Carbon::parse(request('date_from'))
            : now()->startOfMonth();

        $to = request('date_to')
            ? Carbon::parse(request('date_to'))
            : now()->endOfMonth();

        $accounts = Account::where('is_active', true)->orderBy('code')->get();

        $rows = [];
        $totalOpeningDebit = 0;
        $totalOpeningCredit = 0;
        $totalPeriodDebits = 0;
        $totalPeriodCredits = 0;
        $totalClosingDebit = 0;
        $totalClosingCredit = 0;

        foreach ($accounts as $account) {
            $beforeQuery = JournalEntry::whereHas('transaction', fn($q) =>
                $q->whereDate('date', '<', $from)
            )->where('account_id', $account->id);

            $openingDebits  = (clone $beforeQuery)->where('type', 'debit')->sum('amount');
            $openingCredits = (clone $beforeQuery)->where('type', 'credit')->sum('amount');

            $periodQuery = JournalEntry::whereHas('transaction', fn($q) =>
                $q->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            )->where('account_id', $account->id);

            $periodDebits  = (clone $periodQuery)->where('type', 'debit')->sum('amount');
            $periodCredits = (clone $periodQuery)->where('type', 'credit')->sum('amount');

            $closingDebits  = $openingDebits + $periodDebits;
            $closingCredits = $openingCredits + $periodCredits;

            if ($openingDebits == 0 && $openingCredits == 0 && $periodDebits == 0 && $periodCredits == 0) {
                continue;
            }

            $rows[] = compact(
                'account',
                'openingDebits', 'openingCredits',
                'periodDebits', 'periodCredits',
                'closingDebits', 'closingCredits'
            );

            $totalOpeningDebit  += $openingDebits;
            $totalOpeningCredit += $openingCredits;
            $totalPeriodDebits  += $periodDebits;
            $totalPeriodCredits += $periodCredits;
            $totalClosingDebit  += $closingDebits;
            $totalClosingCredit += $closingCredits;
        }

        $html = view('moonshine.trial-balance', compact(
            'rows', 'from', 'to',
            'totalOpeningDebit', 'totalOpeningCredit',
            'totalPeriodDebits', 'totalPeriodCredits',
            'totalClosingDebit', 'totalClosingCredit'
        ))->render();

        return [
            FlexibleRender::make($html),
        ];
    }
}