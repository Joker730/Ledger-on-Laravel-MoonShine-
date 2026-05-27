<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\AccountResource;
use App\MoonShine\Resources\TransactionResource;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\Laravel\Resources\MoonShineUserResource;
use MoonShine\Laravel\Resources\MoonShineUserRoleResource;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
{
    return [
        MenuGroup::make('System', [
            MenuItem::make('/admin/resource/moon-shine-user-resource/moon-shine-user-index-page', 'Admins'),
            MenuItem::make('/admin/resource/moon-shine-user-role-resource/moon-shine-user-role-index-page', 'Roles'),
        ]),
        MenuGroup::make('Бухгалтерия', [
            MenuItem::make('/admin/resource/account-resource/index-page', 'Счета'),
            MenuItem::make('/admin/resource/transaction-resource/index-page', 'Транзакции'),
            MenuItem::make('/admin/page/trial-balance-page', 'ОСВ'),
        ]),
    ];
}

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
