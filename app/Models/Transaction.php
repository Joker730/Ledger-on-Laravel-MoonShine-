<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'is_posted',
    ];

    protected $casts = [
        'date'      => 'date',
        'is_posted' => 'boolean',
    ];
	protected function setEntriesJsonAttribute($value): void
{
    // игнорируем - обрабатывается в Resource
}
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Check if the transaction is balanced (sum of debits == sum of credits)
     */
    public function isBalanced(): bool
    {
        $debits  = $this->journalEntries()->where('type', 'debit')->sum('amount');
        $credits = $this->journalEntries()->where('type', 'credit')->sum('amount');

        return abs($debits - $credits) < 0.001;
    }

    /**
     * Scope: only posted transactions
     */
    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }
}
