<?php
declare(strict_types=1);
namespace App\Providers;
use App\MoonShine\Resources\AccountResource;
use App\MoonShine\Resources\TransactionResource;
use App\MoonShine\Pages\TrialBalancePage;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Resources\MoonShineUserResource;
use MoonShine\Laravel\Resources\MoonShineUserRoleResource;
class MoonShineServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                AccountResource::class,
                TransactionResource::class,
            ])
            ->pages([
                TrialBalancePage::class,
                ...$core->getConfig()->getPages(),
            ]);
    }
}