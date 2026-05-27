<?php

namespace App\MoonShine\Actions;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export transactions to Excel/CSV via MoonShine Action Button
 */
class TransactionExportAction
{
    public static function make(): ActionButton
    {
        return ActionButton::make('Экспорт в Excel')
            ->method('exportTransactions')
            ->icon('table-cells');
    }
}

/**
 * Laravel Excel export class
 */
class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $query = Transaction::with('journalEntries.account')
            ->orderByDesc('date');

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereDate('date', '<=', $this->filters['date_to']);
        }
        if (! empty($this->filters['account_id'])) {
            $query->whereHas('journalEntries', fn($q) => $q->where('account_id', $this->filters['account_id']));
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Дата',
            'Описание',
            'Статус',
            'Счёт (дебет)',
            'Сумма дебета',
            'Счёт (кредит)',
            'Сумма кредита',
        ];
    }

    public function map($transaction): array
    {
        $rows   = [];
        $entries = $transaction->journalEntries;
        $debits  = $entries->where('type', 'debit');
        $credits = $entries->where('type', 'credit');

        $maxRows = max($debits->count(), $credits->count());

        $debitsArr  = $debits->values();
        $creditsArr = $credits->values();

        for ($i = 0; $i < $maxRows; $i++) {
            $rows[] = [
                $i === 0 ? $transaction->id          : '',
                $i === 0 ? $transaction->date->format('d.m.Y') : '',
                $i === 0 ? $transaction->description  : '',
                $i === 0 ? ($transaction->is_posted ? 'Проведена' : 'Черновик') : '',
                $debitsArr[$i]?->account?->name ?? '',
                $debitsArr[$i]?->amount          ?? '',
                $creditsArr[$i]?->account?->name ?? '',
                $creditsArr[$i]?->amount         ?? '',
            ];
        }

        return $rows;
    }
}
