<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function __construct(
        protected TransactionRepository $transactionRepository
    ) {}

    /**
     * Create a transaction with its journal entries.
     * Validates that debits == credits and minimum 2 entries.
     *
     * @param  array  $data  ['date', 'description', 'entries' => [['account_id', 'type', 'amount']]]
     */
    public function createTransaction(array $data): Transaction
    {
        $this->validateEntries($data['entries'] ?? []);

        return DB::transaction(function () use ($data) {
            $transaction = $this->transactionRepository->create([
                'date'        => $data['date'],
                'description' => $data['description'],
                'is_posted'   => $data['is_posted'] ?? false,
            ]);

            foreach ($data['entries'] as $entry) {
                $transaction->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'type'       => $entry['type'],
                    'amount'     => $entry['amount'],
                ]);
            }

            return $transaction->load('journalEntries.account');
        });
    }

    /**
     * Update a transaction. Forbidden if transaction is posted.
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        if ($transaction->is_posted) {
            throw ValidationException::withMessages([
                'is_posted' => ['Проведённую транзакцию нельзя редактировать.'],
            ]);
        }

        $this->validateEntries($data['entries'] ?? []);

        return DB::transaction(function () use ($transaction, $data) {
            $this->transactionRepository->update($transaction, [
                'date'        => $data['date'],
                'description' => $data['description'],
            ]);

            // Replace all entries
            $transaction->journalEntries()->delete();

            foreach ($data['entries'] as $entry) {
                $transaction->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'type'       => $entry['type'],
                    'amount'     => $entry['amount'],
                ]);
            }

            return $transaction->fresh('journalEntries.account');
        });
    }

    /**
     * Delete a transaction. Forbidden if posted.
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        if ($transaction->is_posted) {
            throw ValidationException::withMessages([
                'is_posted' => ['Проведённую транзакцию нельзя удалить.'],
            ]);
        }

        DB::transaction(function () use ($transaction) {
            $transaction->journalEntries()->delete();
            $transaction->delete();
        });
    }

    /**
     * Post (провести) a transaction, making it immutable.
     */
    public function postTransaction(Transaction $transaction): Transaction
    {
        if (! $transaction->isBalanced()) {
            throw ValidationException::withMessages([
                'entries' => ['Транзакция не сбалансирована: сумма дебета должна равняться сумме кредита.'],
            ]);
        }

        $transaction->update(['is_posted' => true]);

        return $transaction;
    }

    /**
     * Get trial balance (оборотно-сальдовая ведомость) for a period.
     * Returns: account, opening balance, period debits, period credits, closing balance.
     */
    public function getTrialBalance(Carbon $from, Carbon $to): array
    {
        $accounts = Account::where('is_active', true)->orderBy('code')->get();

        $result = [];

        foreach ($accounts as $account) {
            // Opening balance — everything before $from
            $openingBalance = $account->getBalance(null, $from->copy()->subDay());

            // Period turnover
            $periodQuery = JournalEntry::whereHas('transaction', function ($q) use ($from, $to) {
                $q->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
            })->where('account_id', $account->id);

            $periodDebits  = (clone $periodQuery)->where('type', 'debit')->sum('amount');
            $periodCredits = (clone $periodQuery)->where('type', 'credit')->sum('amount');

            // Closing balance
            $closingBalance = $account->getBalance(null, $to);

            $result[] = [
                'account'         => $account,
                'opening_balance' => $openingBalance,
                'period_debits'   => (float) $periodDebits,
                'period_credits'  => (float) $periodCredits,
                'closing_balance' => $closingBalance,
            ];
        }

        return $result;
    }

    /**
     * Validate journal entries:
     *  - minimum 2 entries
     *  - at least one debit and one credit
     *  - sum(debits) == sum(credits)
     */
    public function validateEntries(array $entries): void
    {
        if (count($entries) < 2) {
            throw ValidationException::withMessages([
                'entries' => ['Транзакция должна содержать минимум 2 проводки.'],
            ]);
        }

        $debits  = collect($entries)->where('type', 'debit')->sum('amount');
        $credits = collect($entries)->where('type', 'credit')->sum('amount');

        if (abs($debits - $credits) > 0.001) {
            throw ValidationException::withMessages([
                'entries' => [
                    "Сумма дебета ({$debits}) должна равняться сумме кредита ({$credits}).",
                ],
            ]);
        }

        if ($debits <= 0) {
            throw ValidationException::withMessages([
                'entries' => ['Необходима хотя бы одна дебетовая проводка с суммой > 0.'],
            ]);
        }
    }
}
