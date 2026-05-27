<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Account types
     */
    const TYPES = [
        'asset'     => 'Актив',
        'liability' => 'Пассив',
        'equity'    => 'Капитал',
        'revenue'   => 'Доход',
        'expense'   => 'Расход',
    ];

    /**
     * Normal balance side for each account type.
     * asset/expense => debit increases balance
     * liability/equity/revenue => credit increases balance
     */
    const NORMAL_BALANCE = [
        'asset'     => 'debit',
        'expense'   => 'debit',
        'liability' => 'credit',
        'equity'    => 'credit',
        'revenue'   => 'credit',
    ];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Calculate balance for the account within a date range.
     * Returns balance considering normal balance side.
     */
    public function getBalance(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): float
    {
        $query = $this->journalEntries()
            ->whereHas('transaction', function ($q) use ($from, $to) {
                if ($from) {
                    $q->whereDate('date', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('date', '<=', $to);
                }
            });

        $debits  = (clone $query)->where('type', 'debit')->sum('amount');
        $credits = (clone $query)->where('type', 'credit')->sum('amount');

        $normalBalance = self::NORMAL_BALANCE[$this->type] ?? 'debit';

        if ($normalBalance === 'debit') {
            return (float) ($debits - $credits);
        }

        return (float) ($credits - $debits);
    }
}
