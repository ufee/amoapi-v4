# Тесты

[← README](../README.md)

В проекте есть **unit** и **integration** тесты (PHPUnit 9).

## Запуск

```bash
composer install

# unit (без сети)
composer test:unit
# короткий вывод точками
composer test:unit:compact

# integration (нужен .env с доступом к аккаунту)
cp .env.example .env
# заполните AMO_DOMAIN, AMO_CLIENT_ID, AMO_LONG_TOKEN
composer test:integration

# всё сразу
composer test
```

## Переменные окружения

Шаблон — [`.env.example`](../.env.example).  
Файл `.env` в git не коммитится.

| Переменная | Назначение |
|------------|------------|
| `AMO_DOMAIN` | домен аккаунта (без `.amocrm.ru`) |
| `AMO_CLIENT_ID` | ID интеграции |
| `AMO_CLIENT_SECRET` | секрет (для long token можно заглушку) |
| `AMO_REDIRECT_URI` | redirect URI интеграции |
| `AMO_LONG_TOKEN` | долгосрочный токен |
| `AMO_ZONE` | `ru` или `com` (по умолчанию `ru`) |
| `AMO_LANG` | язык аккаунта (по умолчанию `ru`) |
| `AMO_CATALOG_ID` | опционально: каталог для тестов элементов |
| `AMO_BOT_ID` | опционально: salesbot для run/stop |
| `AMO_WIDGET_CODE` | опционально: код виджета для install/uninstall |
| `AMO_WIDGET_SETTINGS` | опционально: JSON настроек виджета |
| `AMO_AMMA_MCP_URL` | опционально: HTTPS URL MCP-сервера для создания агента Аммы |

## Coverage

Нужен `pcov` или `phpdbg`:

```bash
phpdbg -qrr vendor/bin/phpunit --testsuite Unit --coverage-text
```

## Структура

```
tests/
  Unit/           # без сети
  Integration/    # живой API (группа integration)
  Support/        # фикстуры, StubQuery и т.п.
```
