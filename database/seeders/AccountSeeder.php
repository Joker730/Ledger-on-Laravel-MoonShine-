<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1010', 'name' => 'Касса',                  'type' => 'asset'],
            ['code' => '1210', 'name' => 'Расчётный счёт',         'type' => 'asset'],
            ['code' => '1310', 'name' => 'Товары',                  'type' => 'asset'],
            ['code' => '1410', 'name' => 'Дебиторская задолженность','type' => 'asset'],

            // Liabilities
            ['code' => '3110', 'name' => 'Кредиторская задолженность', 'type' => 'liability'],
            ['code' => '3310', 'name' => 'Кредит банка',              'type' => 'liability'],

            // Equity
            ['code' => '5010', 'name' => 'Уставный капитал',       'type' => 'equity'],
            ['code' => '5110', 'name' => 'Нераспределённая прибыль','type' => 'equity'],

            // Revenue
            ['code' => '6010', 'name' => 'Выручка от реализации',  'type' => 'revenue'],
            ['code' => '6110', 'name' => 'Прочие доходы',          'type' => 'revenue'],

            // Expenses
            ['code' => '7010', 'name' => 'Себестоимость продаж',   'type' => 'expense'],
            ['code' => '7110', 'name' => 'Операционные расходы',   'type' => 'expense'],
            ['code' => '7210', 'name' => 'Административные расходы','type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                array_merge($account, ['is_active' => true])
            );
        }
    }
}
