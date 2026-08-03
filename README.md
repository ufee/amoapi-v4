# 🚀 amoCRM/Kommo PHP API (v4) Client

Поддерживает OAuth 2.0, кэширование, пагинацию, события, автоматическое обновление токенов, обработку ошибок и работу с любыми сущностями (сделки, контакты, компании, задачи, заметки и др.).

---

## ✅ Возможности

- ✅ Пддержка [amoCRM/Kommo API v4](https://www.amocrm.ru/developers/content/crm_platform/platform-abilities)
- ✅ OAuth 2.0 с автообновлением токенов
- ✅ Хранилище токенов: файлы, Redis, MongoDB + долгосрочные токены
- ✅ Кэширование справочников в файлах и Redis: пользователи, поля, типы задач и т.д.
- ✅ Постраничное извлечение сущностей через `foreach` и `do-while`
- ✅ Обработка событий через `callbacks`: отладка, контроль
- ✅ Ограничение частоты запросов
- ✅ Поддержка массовых операций
- ✅ Мультиаккаунтность

---

## 📦 Установка

```bash
composer require ufee/amoapi-v4
```

## ⚙️ Быстрый старт

```php
$api = \Ufee\AmoV4\ApiClient::setInstance([...]);
$leads = $api->leads()->get();
foreach ($leads as $lead) {
    echo $lead->name . "\n";
}
```

---

## 📚 Документация

| Раздел | Описание |
|--------|----------|
| [Конфигурация](docs/configuration.md) | Клиент, OAuth, кеш, callbacks, произвольные запросы |
| [Работа с сущностями](docs/entities.md) | Сервисы, модели, коллекции, пагинация, фильтры, поиск |

### Сущности

| Сущность | Файл |
|----------|------|
| [Аккаунт](docs/entities/account.md) | account |
| [Пользователи](docs/entities/users.md) | users |
| [Воронки и этапы](docs/entities/pipelines.md) | pipelines, statuses |
| [Списки](docs/entities/catalogs.md) | catalogs |
| [Элементы списков](docs/entities/catalog-elements.md) | catalogElements |
| [Кастомные поля](docs/entities/custom-fields.md) | customFields |
| [Сделки](docs/entities/leads.md) | leads |
| [Контакты](docs/entities/contacts.md) | contacts |
| [Компании](docs/entities/companies.md) | companies |
| [Покупатели](docs/entities/customers.md) | customers |
| [Сегменты](docs/entities/customer-segments.md) | customerSegments |
| [Причины отказа](docs/entities/loss-reasons.md) | lossReasons |
| [Задачи](docs/entities/tasks.md) | tasks |
| [Заметки](docs/entities/notes.md) | notes |
| [События](docs/entities/events.md) | events |
| [Связи](docs/entities/links.md) | links |
| [Виджеты](docs/entities/widgets.md) | widgets |
| [Вебхуки](docs/entities/webhooks.md) | webhooks |
| [Bots](docs/entities/bots.md) | bots |
| [Источники](docs/entities/sources.md) | sources |
| [Подписчики](docs/entities/subscriptions.md) | subscriptions |
