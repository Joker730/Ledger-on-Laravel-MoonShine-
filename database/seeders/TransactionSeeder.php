<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $cash        = Account::where('code', '1010')->first();
        $bank        = Account::where('code', '1210')->first();
        $equity      = Account::where('code', '5010')->first();
        $revenue     = Account::where('code', '6010')->first();
        $receivable  = Account::where('code', '1410')->first();
        $expenses    = Account::where('code', '7110')->first();

        // Transaction 1: Founders' contribution — cash and bank funded by equity
        $t1 = Transaction::create([
            'date'        => now()->subDays(30)->toDateString(),
            'description' => 'Вклад учредителей в уставный капитал',
            'is_posted'   => true,
        ]);
        JournalEntry::insert([
            ['transaction_id' => $t1->id, 'account_id' => $cash->id,   'type' => 'debit',  'amount' => 50000.00, 'created_at' => now(), 'updated_at' => now()],
            ['transaction_id' => $t1->id, 'account_id' => $bank->id,   'type' => 'debit',  'amount' => 200000.00,'created_at' => now(), 'updated_at' => now()],
            ['transaction_id' => $t1->id, 'account_id' => $equity->id, 'type' => 'credit', 'amount' => 250000.00,'created_at' => now(), 'updated_at' => now()],
        ]);

        // Transaction 2: Sale on credit
        $t2 = Transaction::create([
            'date'        => now()->subDays(15)->toDateString(),
            'description' => 'Реализация товаров покупателю',
            'is_posted'   => true,
        ]);
        JournalEntry::insert([
            ['transaction_id' => $t2->id, 'account_id' => $receivable->id, 'type' => 'debit',  'amount' => 75000.00, 'created_at' => now(), 'updated_at' => now()],
            ['transaction_id' => $t2->id, 'account_id' => $revenue->id,    'type' => 'credit', 'amount' => 75000.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Transaction 3: Operating expenses (not posted — draft)
        $t3 = Transaction::create([
            'date'        => now()->subDays(5)->toDateString(),
            'description' => 'Оплата аренды офиса',
            'is_posted'   => false,
        ]);
        JournalEntry::insert([
            ['transaction_id' => $t3->id, 'account_id' => $expenses->id, 'type' => 'debit',  'amount' => 12000.00, 'created_at' => now(), 'updated_at' => now()],
            ['transaction_id' => $t3->id, 'account_id' => $bank->id,     'type' => 'credit', 'amount' => 12000.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
