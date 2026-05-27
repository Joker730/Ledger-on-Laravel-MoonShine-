<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTransactionRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(protected LedgerService $ledgerService) {}

    /**
     * GET /api/transactions
     * List transactions with optional filters: date_from, date_to, account_id
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'account_id']);

        $transactions = Transaction::with('journalEntries.account')
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['date_to']   ?? null, fn($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filters['account_id'] ?? null, fn($q, $v) => $q->whereHas('journalEntries', fn($q2) => $q2->where('account_id', $v)))
            ->orderByDesc('date')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * POST /api/transactions
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->ledgerService->createTransaction($request->validated());
            return response()->json($transaction->load('journalEntries.account'), 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * GET /api/transactions/{id}
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json($transaction->load('journalEntries.account'));
    }
}
