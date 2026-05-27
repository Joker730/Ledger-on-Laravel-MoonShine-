<?php

namespace App\Http\Controllers;

use App\MoonShine\Actions\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class TransactionExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'account_id']);

        return Excel::download(
            new TransactionsExport($filters),
            'transactions_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
