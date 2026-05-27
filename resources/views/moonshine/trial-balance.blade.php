<div style="padding: 20px;">
    <form method="GET" style="display:flex; gap:16px; margin-bottom:24px; align-items:flex-end;">
        <div>
            <label style="display:block; font-size:13px; margin-bottom:4px;">Начало периода</label>
            <input type="date" name="date_from" value="{{ $from->toDateString() }}"
                style="padding:8px 12px; border-radius:6px; border:1px solid #444; background:#1e1e2e; color:#fff;">
        </div>
        <div>
            <label style="display:block; font-size:13px; margin-bottom:4px;">Конец периода</label>
            <input type="date" name="date_to" value="{{ $to->toDateString() }}"
                style="padding:8px 12px; border-radius:6px; border:1px solid #444; background:#1e1e2e; color:#fff;">
        </div>
        <button type="submit"
            style="padding:8px 20px; background:#7c3aed; color:#fff; border:none; border-radius:6px; cursor:pointer;">
            Показать
        </button>
    </form>

    @if(empty($rows))
        <p style="color:#888;">Нет данных за выбранный период.</p>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#2a2a3e;">
                    <th style="border:1px solid #444; padding:8px; text-align:left;">Код</th>
                    <th style="border:1px solid #444; padding:8px; text-align:left;">Счёт</th>
                    <th style="border:1px solid #444; padding:8px; text-align:left;">Тип</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;" colspan="2">Остаток на начало</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;" colspan="2">Обороты за период</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;" colspan="2">Остаток на конец</th>
                </tr>
                <tr style="background:#2a2a3e;">
                    <th style="border:1px solid #444; padding:8px;"></th>
                    <th style="border:1px solid #444; padding:8px;"></th>
                    <th style="border:1px solid #444; padding:8px;"></th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;">Дебет</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;">Кредит</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;">Дебет</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;">Кредит</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;">Дебет</th>
                    <th style="border:1px solid #444; padding:8px; text-align:right;">Кредит</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr style="border-bottom:1px solid #333;">
                    <td style="border:1px solid #444; padding:8px; font-family:monospace;">{{ $row['account']->code }}</td>
                    <td style="border:1px solid #444; padding:8px;">{{ $row['account']->name }}</td>
                    <td style="border:1px solid #444; padding:8px;">{{ \App\Models\Account::TYPES[$row['account']->type] ?? $row['account']->type }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($row['openingDebits'], 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($row['openingCredits'], 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($row['periodDebits'], 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($row['periodCredits'], 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace; font-weight:bold;">{{ number_format($row['closingDebits'], 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace; font-weight:bold;">{{ number_format($row['closingCredits'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#2a2a3e; font-weight:bold;">
                    <td colspan="3" style="border:1px solid #444; padding:8px;">ИТОГО</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($totalOpeningDebit, 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($totalOpeningCredit, 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($totalPeriodDebits, 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($totalPeriodCredits, 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($totalClosingDebit, 2) }}</td>
                    <td style="border:1px solid #444; padding:8px; text-align:right; font-family:monospace;">{{ number_format($totalClosingCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>