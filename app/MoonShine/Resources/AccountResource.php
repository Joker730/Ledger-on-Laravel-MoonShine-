<?php

namespace App\MoonShine\Resources;

use App\Models\Account;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;

class AccountResource extends ModelResource
{
    protected string $model = Account::class;
	protected string $uriKey = 'account-resource';

    protected string $title = 'Счета';

    protected string $column = 'name';

    protected bool $createInModal = false;
    protected bool $editInModal   = false;

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Код', 'code')->sortable(),
            Text::make('Название', 'name')->sortable(),
            Select::make('Тип', 'type')
                ->options(Account::TYPES)
                ->badge(fn($value) => match($value) {
                    'asset'     => 'info',
                    'liability' => 'warning',
                    'equity'    => 'success',
                    'revenue'   => 'success',
                    'expense'   => 'error',
                    default     => 'default',
                }),
            Switcher::make('Активен', 'is_active'),
        ];
    }

    public function formFields(): array
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название счёта', 'name')
                    ->required()
                    ->hint('Например: Касса, Расчётный счёт'),

                Text::make('Код счёта', 'code')
                    ->required()
                    ->hint('Уникальный код (например: 1010)'),

                Select::make('Тип счёта', 'type')
                    ->options(Account::TYPES)
                    ->required(),

                Switcher::make('Активен', 'is_active')
                    ->default(true),
            ]),
        ];
    }

    public function detailFields(): array
    {
        return $this->indexFields();
    }

    public function rules(mixed $item): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:20', 'unique:accounts,code,' . ($item->id ?? 'NULL')],
            'type'      => ['required', 'in:' . implode(',', array_keys(Account::TYPES))],
            'is_active' => ['boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            Select::make('Тип', 'type')->options(Account::TYPES)->nullable(),
            Switcher::make('Активен', 'is_active'),
        ];
    }
}
