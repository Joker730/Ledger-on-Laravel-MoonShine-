<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction;
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    public function findWithEntries(int $id): ?Transaction
    {
        return Transaction::with('journalEntries.account')->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Transaction::with('journalEntries.account')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['account_id'])) {
            $query->whereHas('journalEntries', function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            });
        }

        return $query->paginate($perPage);
    }

    public function allWithEntries(array $filters = []): Collection
    {
        $query = Transaction::with('journalEntries.account')
            ->orderByDesc('date');

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['account_id'])) {
            $query->whereHas('journalEntries', function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            });
        }

        return $query->get();
    }
}
