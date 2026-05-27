<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LedgerService(new TransactionRepository());
    }

    // -----------------------------------------------------------------------
    // Helper: create accounts
    // -----------------------------------------------------------------------

    private function createAccount(string $type = 'asset'): Account
    {
        return Account::create([
            'name'      => 'Test ' . $type . ' ' . uniqid(),
            'code'      => (string) rand(1000, 9999),
            'type'      => $type,
            'is_active' => true,
        ]);
    }

    // -----------------------------------------------------------------------
    // Tests: createTransaction
    // -----------------------------------------------------------------------

    public function test_create_transaction_success(): void
    {
        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('equity');

        $transaction = $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Test transaction',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 1000],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 1000],
            ],
        ]);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('Test transaction', $transaction->description);
        $this->assertCount(2, $transaction->journalEntries);
    }

    public function test_create_transaction_stores_in_database(): void
    {
        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('liability');

        $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'DB check',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 500],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 500],
            ],
        ]);

        $this->assertDatabaseHas('transactions', ['description' => 'DB check']);
        $this->assertDatabaseHas('journal_entries', ['account_id' => $debitAccount->id, 'type' => 'debit', 'amount' => 500]);
    }

    public function test_create_transaction_with_multiple_entries(): void
    {
        $cash     = $this->createAccount('asset');
        $bank     = $this->createAccount('asset');
        $equity   = $this->createAccount('equity');

        $transaction = $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Multi-entry',
            'entries'     => [
                ['account_id' => $cash->id,   'type' => 'debit',  'amount' => 300],
                ['account_id' => $bank->id,   'type' => 'debit',  'amount' => 700],
                ['account_id' => $equity->id, 'type' => 'credit', 'amount' => 1000],
            ],
        ]);

        $this->assertCount(3, $transaction->journalEntries);
        $this->assertTrue($transaction->isBalanced());
    }

    // -----------------------------------------------------------------------
    // Tests: debit/credit validation
    // -----------------------------------------------------------------------

    public function test_fails_when_less_than_two_entries(): void
    {
        $this->expectException(ValidationException::class);

        $account = $this->createAccount('asset');

        $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'One entry only',
            'entries'     => [
                ['account_id' => $account->id, 'type' => 'debit', 'amount' => 100],
            ],
        ]);
    }

    public function test_fails_when_debits_not_equal_credits(): void
    {
        $this->expectException(ValidationException::class);

        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('equity');

        $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Unbalanced',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 1000],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 500],
            ],
        ]);
    }

    public function test_fails_when_debit_amount_is_zero(): void
    {
        $this->expectException(ValidationException::class);

        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('equity');

        $this->service->validateEntries([
            ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 0],
            ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 0],
        ]);
    }

    public function test_fails_when_empty_entries(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'No entries',
            'entries'     => [],
        ]);
    }

    // -----------------------------------------------------------------------
    // Tests: posted transaction protection
    // -----------------------------------------------------------------------

    public function test_cannot_update_posted_transaction(): void
    {
        $this->expectException(ValidationException::class);

        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('equity');

        $transaction = $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Will be posted',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 100],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 100],
            ],
        ]);

        $this->service->postTransaction($transaction);

        // This should throw
        $this->service->updateTransaction($transaction, [
            'date'        => '2024-06-02',
            'description' => 'Attempt to edit',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 200],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 200],
            ],
        ]);
    }

    public function test_cannot_delete_posted_transaction(): void
    {
        $this->expectException(ValidationException::class);

        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('equity');

        $transaction = $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Posted',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 100],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 100],
            ],
        ]);

        $this->service->postTransaction($transaction);
        $this->service->deleteTransaction($transaction);
    }

    public function test_can_delete_unposted_transaction(): void
    {
        $debitAccount  = $this->createAccount('asset');
        $creditAccount = $this->createAccount('equity');

        $transaction = $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Draft',
            'entries'     => [
                ['account_id' => $debitAccount->id,  'type' => 'debit',  'amount' => 100],
                ['account_id' => $creditAccount->id, 'type' => 'credit', 'amount' => 100],
            ],
        ]);

        $id = $transaction->id;
        $this->service->deleteTransaction($transaction);

        $this->assertDatabaseMissing('transactions', ['id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Tests: account balance
    // -----------------------------------------------------------------------

    public function test_account_balance_calculated_correctly(): void
    {
        $asset   = $this->createAccount('asset');   // normal: debit
        $revenue = $this->createAccount('revenue');  // normal: credit

        $this->service->createTransaction([
            'date'        => '2024-06-01',
            'description' => 'Balance test',
            'entries'     => [
                ['account_id' => $asset->id,   'type' => 'debit',  'amount' => 1500],
                ['account_id' => $revenue->id, 'type' => 'credit', 'amount' => 1500],
            ],
        ]);

        // Asset: debit increases, so balance = 1500
        $this->assertEquals(1500.0, $asset->fresh()->getBalance());

        // Revenue: credit increases, so balance = 1500
        $this->assertEquals(1500.0, $revenue->fresh()->getBalance());
    }
}
