<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'                    => ['required', 'date'],
            'description'             => ['required', 'string', 'max:500'],
            'entries'                 => ['required', 'array', 'min:2'],
            'entries.*.account_id'    => ['required', 'integer', 'exists:accounts,id'],
            'entries.*.type'          => ['required', 'in:debit,credit'],
            'entries.*.amount'        => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.min'                  => 'Необходимо минимум 2 проводки.',
            'entries.*.account_id.exists'  => 'Счёт не найден.',
            'entries.*.type.in'            => 'Тип проводки должен быть debit или credit.',
            'entries.*.amount.min'         => 'Сумма должна быть больше 0.',
        ];
    }
}
