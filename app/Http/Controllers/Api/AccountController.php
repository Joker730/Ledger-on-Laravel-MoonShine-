<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * GET /api/accounts
     */
    public function index(): JsonResponse
    {
        return response()->json(Account::where('is_active', true)->orderBy('code')->get());
    }

    /**
     * GET /api/accounts/{id}/balance
     * Query params: date_from, date_to
     */
    public function balance(Request $request, Account $account): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $from = $request->date_from ? Carbon::parse($request->date_from) : null;
        $to   = $request->date_to   ? Carbon::parse($request->date_to)   : null;

        $balance = $account->getBalance($from, $to);

        return response()->json([
            'account'   => $account,
            'date_from' => $from?->toDateString(),
            'date_to'   => $to?->toDateString(),
            'balance'   => $balance,
        ]);
    }
}
