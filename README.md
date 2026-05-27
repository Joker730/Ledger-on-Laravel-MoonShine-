# Ledger — Бухгалтерская книга (двойная запись)

Laravel 12 + MoonShine 4.x + PostgreSQL + Docker

---

## Стек технологий

| Технология | Версия |
|---|---|
| PHP | 8.3 |
| Laravel | 12.x |
| MoonShine | 4.x |
| PostgreSQL | 15 |
| Docker + Docker Compose | — |
| maatwebsite/excel | 3.x |
| PHPUnit | 11.x |

---

## Быстрый старт

### 1. Клонирование репозитория

```bash
git clone https://github.com/Joker730/Ledger-on-Laravel-MoonShine-.git
cd Ledger-on-Laravel-MoonShine-
```

### 2. Настройка окружения

```bash
cp .env.example .env
```

Открой `.env` и добавь в конец:

```
WWWUSER=1000
WWWGROUP=1000
```

### 3. Установка зависимостей

```bash
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install --ignore-platform-reqs
```

**На Windows (PowerShell):**

```powershell
docker run --rm -v "C:/path/to/project:/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install --ignore-platform-reqs
```

### 4. Запуск контейнеров

```bash
docker compose up -d
```

### 5. Генерация ключа

```bash
docker compose exec laravel.test php artisan key:generate
```

### 6. Миграции и тестовые данные

```bash
docker compose exec laravel.test php artisan migrate --seed
```

### 7. Установка MoonShine

```bash
docker compose exec laravel.test php artisan moonshine:install
```

При установке введи данные администратора:
- Email: `admin@ledger.test`
- Password: `password`
- Name: `Admin`

### 8. Настройка прав

```bash
docker compose exec laravel.test bash -c "chmod -R 777 storage bootstrap/cache"
```

---

## Доступ

| Адрес | Описание |
|---|---|
| http://localhost/admin | Административная панель MoonShine |
| http://localhost/api/accounts | REST API — список счетов |
| http://localhost/api/transactions | REST API — список транзакций |

---

## Структура проекта

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AccountController.php
│   │   │   └── TransactionController.php
│   │   └── TransactionExportController.php
│   └── Requests/
│       └── Api/
│           └── StoreTransactionRequest.php
├── Models/
│   ├── Account.php
│   ├── Transaction.php
│   └── JournalEntry.php
├── MoonShine/
│   ├── Handlers/
│   │   └── TransactionExportHandler.php
│   ├── Layouts/
│   │   └── MoonShineLayout.php
│   ├── Pages/
│   │   └── TrialBalancePage.php
│   └── Resources/
│       ├── AccountResource.php
│       ├── TransactionResource.php
│       └── JournalEntryResource.php
├── Repositories/
│   └── TransactionRepository.php
└── Services/
    └── LedgerService.php
database/
├── migrations/
└── seeders/
tests/
└── Unit/
    └── LedgerServiceTest.php
```

---

## Сущности

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
| is_posted | boolean | Проведена (защищена от изменений) |

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
- `SUM(debit) == SUM(credit)` — проверяется в `LedgerService`
- Проведённую транзакцию (`is_posted = true`) **нельзя** редактировать или удалять
- Остаток счёта рассчитывается с учётом нормального сальдо

---

## REST API

### Аутентификация

HTTP Basic Auth:

```
Email: admin@ledger.test
Password: password
```

### Эндпоинты

#### GET /api/accounts
Список активных счетов.

```bash
curl -u admin@ledger.test:password http://localhost/api/accounts
```

#### GET /api/accounts/{id}/balance
Остаток по счёту за период.

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

#### GET /api/transactions
Список транзакций с пагинацией.

```bash
curl -u admin@ledger.test:password http://localhost/api/transactions
```

#### POST /api/transactions
Создание транзакции.

```bash
curl -u admin@ledger.test:password \
  -X POST http://localhost/api/transactions \
  -H "Content-Type: application/json" \
  -d '{
    "date": "2024-06-15",
    "description": "Поступление средств",
    "entries": [
      { "account_id": 1, "type": "debit",  "amount": 100000 },
      { "account_id": 7, "type": "credit", "amount": 100000 }
    ]
  }'
```

#### GET /api/transactions/{id}
Детали транзакции с проводками.

---

## Запуск тестов

```bash
docker compose exec laravel.test php artisan test --testsuite=Unit
```

Ожидаемый результат: **12 тестов пройдено**

---

## Функциональность MoonShine

| Раздел | Возможности |
|---|---|
| Счета | CRUD, фильтр по типу и активности |
| Транзакции | CRUD, динамические проводки, фильтры, экспорт Excel |
| ОСВ | Оборотно-сальдовая ведомость за выбранный период |

---

## Остановка контейнеров

```bash
docker compose down
```

---

## Лицензия

MIT
