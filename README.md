# CRM Stage Engine (Joomla Component)

Прототип реализует Stage Engine для CRM как Joomla компонент `com_stageengine` с event-driven логикой переходов между стадиями.

## 1. Что реализовано

- Docker окружение: Joomla + MariaDB.
- Компонент `com_stageengine`:
  - карточка компании,
  - текущая стадия,
  - доступный переход,
  - блок инструкции для менеджера,
  - история событий.
- Централизованная логика в `StageTransitionService`:
  - `canTransition(Company $company, string $target): bool`
  - `getAvailableActions(Company $company): array`
  - `transition(Company $company, string $target): void`
- Event-driven подход:
  - события записываются append-only в `company_events`,
  - стадия вычисляется через `StageResolver::resolve(company_id)` по событиям `stage_transitioned`,
  - `companies.stage`/`companies.cached_stage` используется как кэш-снимок.

## 2. Архитектура (clean architecture)

### Слои

- Domain:
  - `Stage` (`joomla-component/com_stageengine/site/lib/StageEngine/Domain/Stage.php`)
  - `Company` (`joomla-component/com_stageengine/site/lib/StageEngine/Domain/Company.php`)
- Application:
  - контракты репозиториев (`.../Application/Contracts/*.php`)
  - `StageResolver` (`.../Application/StageResolver.php`)
  - `StageTransitionService` (`.../Application/StageTransitionService.php`)
- Infrastructure:
  - Joomla DB реализации репозиториев (`.../Infrastructure/Joomla/*.php`)
- Presentation:
  - `joomla-component/com_stageengine/site/stageengine.php`

Ключевая идея: доменная логика не зависит от Joomla и тестируется отдельно.

## 3. Модель данных и индексы

SQL установки: `joomla-component/com_stageengine/sql/install.mysql.utf8.sql`

Таблицы:
- `#__companies` (`id`, `name`, `stage`, `cached_stage`, `created_at`)
  - индекс: `stage`
- `#__company_events` (`id`, `company_id`, `event_type`, `payload`, `created_at`)
  - индексы: `company_id`, `event_type`, `(company_id, event_type)`, `created_at`
- `#__invoices` (`id`, `company_id`, `amount`, `status`, `created_at`)
  - индекс: `(company_id, status)`
- `#__payments` (`id`, `invoice_id`, `paid_at`)
  - индекс: `invoice_id`
- `#__certificates` (`id`, `company_id`, `issued_at`)
  - индекс: `company_id`

## 4. Правила переходов

Последовательность стадий:
`C0 -> C1 -> C2 -> W1 -> W2 -> W3 -> H1 -> H2 -> A1`

Переход разрешён только на следующую стадию и только при выполнении обязательного условия:
- `C1`: есть `decision_maker_call`
- `C2`: есть `discovery_completed`
- `W1`: есть `discovery_completed`
- `W2`: есть `demo_planned`
- `W3`: есть `demo_completed` за последние 60 дней
- `H1`: есть счёт (`invoices`)
- `H2`: есть оплата (`payments`)
- `A1`: есть удостоверение (`certificates`)

## 5. Масштабирование до ~10k компаний/день

- append-only event log (`company_events` только INSERT);
- основной read-path по индексам (`company_id`, `event_type`, composite index);
- кэш стадии (`companies.stage`, `companies.cached_stage`) обновляется отдельно;
- минимизация блокировок: write-heavy операции идут через INSERT, UPDATE только для кэша стадии;
- горизонтальное масштабирование: hash/range partitioning по `company_id` для event таблицы;
- вынос аналитики в асинхронный контур (CDC/worker/materialized projections).

## 6. Тестирование

Unit-тесты: `tests/StageTransitionServiceTest.php`

Покрытые проверки (негативные сценарии):
- нельзя перейти в Aware без discovery;
- нельзя перейти в demo_planned без события планирования (даты/времени демо);
- нельзя перейти в Committed без счёта;
- нельзя перейти в Customer без оплаты;
- нельзя перейти в Activated без удостоверения.

Запуск:

```bash
composer install
composer test
```

## 7. Bug -> fix -> green cycle

- Найден баг: без строгой последовательности стадий можно было бы перескочить через этап.
- Исправление: в `StageTransitionService::canTransition` добавлена проверка только на `Stage::next($current)`.
- Результат: unit-тесты зелёные, переходы работают только step-by-step.

## 8. AI workflow

- Инструмент: Codex (GPT-5 coding agent).
- Процесс:
  - декомпозиция на инфраструктуру, домен, БД, UI, тесты;
  - генерация каркаса + ручная валидация бизнес-правил;
  - тесты как guardrail перед финализацией.
- Контроль качества:
  - логика правил изолирована и протестирована без Joomla;
  - минимизирован риск галлюцинаций фреймворка за счёт простого интеграционного слоя;
  - внешние зависимости только стандартные для PHP/Joomla.
- Где AI дал выигрыш:
  - быстрое создание каркаса clean architecture;
  - ускоренная подготовка unit-тестов и документации.

## 9. Развёртывание (Docker + Joomla)

1. Запуск контейнеров:

```bash
docker compose up -d --build
```

2. Открыть `http://localhost:8080` и завершить стандартный web installer Joomla.
   - DB Host: `db`
   - DB User: `joomla`
   - DB Password: `joomla`
   - DB Name: `joomla`

3. Собрать ZIP компонента:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/package-component.ps1
```

4. В админке Joomla:
   - `System -> Install -> Extensions`
   - загрузить `dist/com_stageengine.zip`

5. Открыть компонент:
   - frontend (без SEF): `http://localhost:8080/index.php?option=com_stageengine`
   - если включён SEF и страница 404, создайте пункт меню:
     - `Menus -> Main Menu -> New`
     - `Menu Item Type -> Select -> Stage Engine`
     - сохраните и откройте URL пункта меню
   - admin page: `http://localhost:8080/administrator/index.php?option=com_stageengine`
