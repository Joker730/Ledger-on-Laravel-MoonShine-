<?php

namespace App\MoonShine\Handlers;

use Maatwebsite\Excel\Facades\Excel;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Handlers\Handler;
use MoonShine\UI\Components\ActionButton;
use App\MoonShine\Actions\TransactionsExport;

class TransactionExportHandler extends Handler
{
    public function handle(): \Symfony\Component\HttpFoundation\Response
    {
        return Excel::download(
            new TransactionsExport(),
            'transactions_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function getButton(): ActionButton
    {
        return ActionButton::make($this->getLabel(), $this->getUrl());
    }
}