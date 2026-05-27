<?php

namespace App\MoonShine\Resources;

use App\Models\JournalEntry;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class JournalEntryResource extends ModelResource
{
    protected string $model = JournalEntry::class;
    protected string $uriKey = 'journal-entry-resource';
    protected string $title = 'Проводки';
    protected string $column = 'id';

    public function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Счёт', 'account.name'),
            Text::make('Код', 'account.code'),
            Select::make('Тип', 'type')
                ->options(['debit' => 'Дебет', 'credit' => 'Кредит']),
            Number::make('Сумма', 'amount'),
        ];
    }

    public function formFields(): array
    {
        return $this->indexFields();
    }
}