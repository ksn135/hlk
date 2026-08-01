# Архитектура HLK (итерация 1)

Сайт личного кабинета контрагента: `lk.vodorodfilm.com` (локально — HLK Symfony 8).

## Модель доступа

- Учётка КА в общей таблице `contractor`: `login` (по умолчанию ИНН), `password` (хеш), `password_plain` (открытый для менеджеров), `allow_lk_access`.
- Внутри ЛК — **пакеты на согласование** (`review_package`).

## Сущности

| Таблица | Назначение |
|--------|------------|
| `contractor` | Slim-маппинг в HLK только для auth |
| `review_package` | Пакет: guid, contractor_id, doc_id, status, attributes (JSON) |
| `review_package_file` | Копии docx (`filename` относительно `HLK_FILES_DIR`) |
| `review_package_log` | События |

Статусы пакета: `active` → `submitted` (кнопка «Отправить») | `revoked` (из Vis, итерация 2).

`attributes` — JSON-массив `{key, label, value}` (номер, инициатор, дата, предмет, срок…).

## Файлы и OnlyOffice

- Копии при создании пакета: `HLK_FILES_DIR/review_packages/{guid}/…`
- `HLK_FILES_DIR` должен совпадать с файловым стораджем Vis (`public/files`).
- OnlyOffice: `/onlyoffice/editor/{id}/{mode}`, callback/download по JWT; DS по `/oo/` (прокси веб-сервера).

## SQL (вручную)

См. [`docs/sql/`](sql/):

1. `001_contractor_lk_fields.sql`
2. `002_review_package_tables.sql`

## Тестовые данные

```bash
php bin/console hlk:seed-test-package --contractor-id=2473 --password=testlk2473
```

Логин: ИНН контрагента (или заданный `login`), пароль из опции.

## Vis (hydrogen)

- Те же таблицы `review_package*`.
- Роль `ROLE_REVIEW_PACKAGE`: меню «Пакеты на согласование», CRUD (только просмотр), вкладка на карточке к/а.
- Создание пакета (сервис `ReviewPackageService`):

```bash
php bin/console app:review-package:create 'ДОГ-0001' --enable-lk --password=secret --replace
```

Копируются Word-слоты договора (`contract_form`, `primary_annex_form`, …) в `public/files/review_packages/{guid}/`.
