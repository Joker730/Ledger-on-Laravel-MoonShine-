# Ledger — Бухгалтерская книга (двойная запись)

Laravel 12 + MoonShine + PostgreSQL + Docker (Laravel Sail)

---

## Стек технологий

| Технология | Версия |
|---|---|
| PHP | 8.3 |
| Laravel | 12.x |
| MoonShine | 3.x |
| PostgreSQL | 15 |
| Laravel Sail | Docker-окружение |
| maatwebsite/excel | 3.x |
| PHPUnit | 11.x |

---

## Быстрый старт

### 1. Клонирование репозитория

```bash
git clone https://github.com/your-username/ledger.git
cd ledger
```

### 2. Настройка окружения

```bash
cp .env.example .env
```

> Настройки по умолчанию уже подходят для Sail. При необходимости отредактируйте `.env`.

### 3. Установка зависимостей (через временный Docker-контейнер)

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Запуск контейнеров

```bash
./vendor/bin/sail up -d
```

### 5. Генерация ключа приложения

```bash
./vendor/bin/sail artisan key:generate
```

### 6. Миграции и сидеры

```bash
./vendor/bin/sail artisan migrate --seed
```

### 7. Установка MoonShine (публикация ресурсов)

```bash
./vendor/bin/sail artisan moonshine:install
```

### 8. Создание администратора MoonShine

```bash
./vendor/bin/sail artisan moonshine:user
```

Или использовать тестовые данные из сидера:

| Поле | Значение |
|---|---|
| Email | admin@ledger.test |
| Пароль | password |

---

## Доступ

| Адрес | Описание |
|---|---|
| http://localhost/admin | Административная панель MoonShine |
| http://localhost/api | REST API |

---

## Структура проекта

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AccountController.php     # GET /api/accounts, /balance
│   │   │   └── TransactionController.php # CRUD транзакций
│   │   └── TransactionExportController.php
│   └── Requests/
│       └── Api/
│           └── StoreTransactionRequest.php
├── Models/
│   ├── Account.php        # Счёт
│   ├── Transaction.php    # Транзакция
│   └── JournalEntry.php   # Проводка
├── MoonShine/
│   ├── Resources/
│   │   ├── AccountResource.php     # CRUD счетов
│   │   ├── TransactionResource.php # CRUD транзакций
│   │   └── JournalEntryResource.php
│   ├── Pages/
│   │   └── TrialBalancePage.php    # ОСВ отчёт
│   └── Actions/
│       └── TransactionsExport.php  # Excel экспорт
├── Repositories/
│   └── TransactionRepository.php
├── Services/
│   └── LedgerService.php           # Бизнес-логика
└── Providers/
    ├── AppServiceProvider.php
    └── MoonShineServiceProvider.php
database/
├── migrations/
│   ├── ..._create_accounts_table.php
│   ├── ..._create_transactions_table.php
│   └── ..._create_journal_entries_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── UserSeeder.php
    ├── AccountSeeder.php
    └── TransactionSeeder.php
tests/
└── Unit/
    └── LedgerServiceTest.php
```

---

## Сущности (модели)

### Account (Счёт)

| Поле | Тип | Описание |
|---|---|---|
| id | bigint | PK |
| name | string | Название |
| code | string(20) | Уникальный код |
| type | enum | asset / liability / equity / revenue / expense |
| is_active | boolean | Активен |

### Transaction (Транзакция)

| Поле | Тип | Описание |
|---|---|---|
| id | bigint | PK |
| date | date | Дата |
| description | string | Описание |
| is_posted | boolean | Проведена (неизменяемая) |

### JournalEntry (Проводка)

| Поле | Тип | Описание |
|---|---|---|
| id | bigint | PK |
| transaction_id | FK | Транзакция |
| account_id | FK | Счёт |
| amount | decimal(15,2) | Сумма |
| type | enum | debit / credit |

---

## Бизнес-правила

- Одна транзакция — минимум 2 проводки (дебет + кредит)
- `SUM(debit) == SUM(credit)` — проверяется в `LedgerService::validateEntries()`
- Проведённую транзакцию (`is_posted = true`) **нельзя** редактировать или удалять
- Остаток счёта рассчитывается с учётом нормального сальдо (debit-normal для asset/expense, credit-normal для остальных)

---

## REST API

### Аутентификация

HTTP Basic Auth. Использовать данные пользователя из сидера:

```
Email: admin@ledger.test
Password: password
```

Пример:
```bash
curl -u admin@ledger.test:password http://localhost/api/accounts
```

---

### Эндпоинты

#### GET /api/accounts
Список активных счетов.

**Ответ:**
```json
[
  {
    "id": 1,
    "name": "Касса",
    "code": "1010",
    "type": "asset",
    "is_active": true
  }
]
```

---

#### GET /api/accounts/{id}/balance
Остаток по счёту за период.

**Query параметры:**

| Параметр | Тип | Описание |
|---|---|---|
| date_from | date | Начало периода (необязательно) |
| date_to | date | Конец периода (необязательно) |

**Пример:**
```bash
curl -u admin@ledger.test:password \
  "http://localhost/api/accounts/1/balance?date_from=2024-01-01&date_to=2024-12-31"
```

**Ответ:**
```json
{
  "account": { "id": 1, "name": "Касса", "code": "1010", "type": "asset" },
  "date_from": "2024-01-01",
  "date_to": "2024-12-31",
  "balance": 50000.00
}
```

---

#### GET /api/transactions
Список транзакций с пагинацией.

**Query параметры:**

| Параметр | Тип | Описание |
|---|---|---|
| date_from | date | Фильтр от даты |
| date_to | date | Фильтр до даты |
| account_id | int | Фильтр по счёту |
| page | int | Страница (default: 1) |

---

#### POST /api/transactions
Создание транзакции.

**Body (JSON):**
```json
{
  "date": "2024-06-15",
  "description": "Поступление средств от учредителей",
  "entries": [
    { "account_id": 1, "type": "debit",  "amount": 100000 },
    { "account_id": 7, "type": "credit", "amount": 100000 }
  ]
}
```

**Ответ (201):**
```json
{
  "id": 4,
  "date": "2024-06-15",
  "description": "Поступление средств от учредителей",
  "is_posted": false,
  "journal_entries": [...]
}
```

**Ошибки (422):**
```json
{
  "errors": {
    "entries": ["Сумма дебета (100000) должна равняться сумме кредита (50000)."]
  }
}
```

---

#### GET /api/transactions/{id}
Детали транзакции с проводками.

---

## Запуск тестов

```bash
# Создать тестовую БД
./vendor/bin/sail psql -c "CREATE DATABASE ledger_test;"

# Запустить тесты
./vendor/bin/sail artisan test --testsuite=Unit
```

Или через phpunit напрямую:

```bash
./vendor/bin/sail php ./vendor/bin/phpunit tests/Unit
```

---

## MoonShine Adminка

### Функциональность

| Раздел | Возможности |
|---|---|
| Счета | CRUD, фильтр по типу/активности |
| Транзакции | CRUD, фильтры по дате и счёту, экспорт в Excel |
| ОСВ | Оборотно-сальдовая ведомость за период |

### Экспорт транзакций

Кнопка **«Экспорт в Excel»** на странице списка транзакций. Применяет текущие фильтры.

---

## Остановка контейнеров

```bash
./vendor/bin/sail down
```

---

## Лицензия

MIT
