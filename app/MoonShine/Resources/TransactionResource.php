<?php

namespace App\MoonShine\Resources;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;


class TransactionResource extends ModelResource
{
    protected string $model = Transaction::class;
    protected string $uriKey = 'transaction-resource';
    protected string $title = 'Транзакции';
    protected string $column = 'description';
    protected bool $createInModal = false;
    protected bool $editInModal = false;

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Date::make('Дата', 'date')->sortable()->format('d.m.Y'),
            Text::make('Описание', 'description'),
            Switcher::make('Проведена', 'is_posted')->disabled(),
        ];
    }

    public function formFields(): array
    {
        $accountOptions = Account::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn($a) => [$a->id => "[{$a->code}] {$a->name}"])
            ->toArray();

        return [
            Box::make([
                Grid::make([
                    Column::make([
                        Date::make('Дата', 'date')->required()->default(now()->toDateString()),
                    ])->columnSpan(4),
                    Column::make([
                        Switcher::make('Проведена', 'is_posted')->default(false),
                    ])->columnSpan(4),
                ]),

                Textarea::make('Описание', 'description')->required(),

                Divider::make('Проводки'),

                Json::make('Проводки', 'entries_json')
                    ->fields([
                        Select::make('Счёт', 'account_id')
                            ->options($accountOptions)
                            ->required(),
                        Select::make('Тип', 'type')
                            ->options(['debit' => 'Дебет', 'credit' => 'Кредит'])
                            ->required(),
                        Number::make('Сумма', 'amount')
                            ->min(0.01)
                            ->step(0.01)
                            ->required(),
                    ])
                    ->creatable()
                    ->removable()
                    ->default([
                        ['account_id' => '', 'type' => 'debit', 'amount' => ''],
                        ['account_id' => '', 'type' => 'credit', 'amount' => ''],
                    ]),
            ]),
        ];
    }

    public function detailFields(): array
    {
        return [
            ID::make(),
            Date::make('Дата', 'date')->format('d.m.Y'),
            Text::make('Описание', 'description'),
            Switcher::make('Проведена', 'is_posted')->disabled(),
            HasMany::make('Проводки', 'journalEntries', resource: app(JournalEntryResource::class)),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'date'        => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
        ];
    }

    public function filters(): array
{
    $accountOptions = Account::where('is_active', true)
        ->orderBy('code')
        ->get()
        ->mapWithKeys(fn($a) => [$a->id => "[{$a->code}] {$a->name}"])
        ->toArray();

    return [
        Date::make('Дата от', 'date'),
        Date::make('Дата до', 'date'),
        Select::make('Счёт', 'account_id')
            ->options($accountOptions)
            ->nullable(),
    ];
}
	protected function handlers(): \MoonShine\Support\ListOf
{
    return parent::handlers()->add(new \App\MoonShine\Handlers\TransactionExportHandler('Экспорт в Excel'));
}
    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        $entries = $this->getEntries();
        app(LedgerService::class)->validateEntries($entries);
        return $item;
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
    {
        $original = $item->getOriginal();
        if ($original->is_posted) {
            throw ValidationException::withMessages([
                'is_posted' => ['Проведённую транзакцию нельзя редактировать.'],
            ]);
        }
        $entries = $this->getEntries();
        if (!empty($entries)) {
            app(LedgerService::class)->validateEntries($entries);
        }
        return $item;
    }

    protected function beforeDeleting(DataWrapperContract $item): DataWrapperContract
    {
        $original = $item->getOriginal();
        if ($original->is_posted) {
            throw ValidationException::withMessages([
                'is_posted' => ['Проведённую транзакцию нельзя удалить.'],
            ]);
        }
        return $item;
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        $original = $item->getOriginal();
        $entries = $this->getEntries();
        foreach ($entries as $entry) {
            $original->journalEntries()->create([
                'account_id' => $entry['account_id'],
                'type'       => $entry['type'],
                'amount'     => $entry['amount'],
            ]);
        }
        return $item;
    }

    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        $original = $item->getOriginal();
        $entries = $this->getEntries();
        if (!empty($entries)) {
            $original->journalEntries()->delete();
            foreach ($entries as $entry) {
                $original->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'type'       => $entry['type'],
                    'amount'     => $entry['amount'],
                ]);
            }
        }
        return $item;
    }

    private function getEntries(): array
    {
        $entries = request()->input('entries_json', []);
        if (is_string($entries)) {
            $entries = json_decode($entries, true) ?? [];
        }
        return is_array($entries) ? $entries : [];
    }
}