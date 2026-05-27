<?php

namespace App\Providers;

use App\Repositories\TransactionRepository;
use App\Services\LedgerService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LedgerService::class, function ($app) {
            return new LedgerService($app->make(TransactionRepository::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
