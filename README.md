# 🚀 amoCRM/Kommo PHP API (v4) Client

Поддерживает OAuth 2.0, кеширование, пагинацию, события, автоматическое обновление токенов, обработку ошибок и работу с любыми сущностями (сделки, контакты, компании, задачи, примечания и др.).

---

## ✅ Возможности

- ✅ Поддержка [amoCRM/Kommo API v4](https://www.amocrm.ru/developers/content/crm_platform/platform-abilities)
- ✅ OAuth 2.0 с автообновлением токенов
- ✅ Хранилище токенов: файлы, Redis, MongoDB + долгосрочные токены
- ✅ Кеширование справочников в файлах и Redis: пользователи, поля, типы задач и т.д.
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
$api = \Ufee\AmoV4\ApiClient::setInstance([
    'domain'        => 'yourdomain',
    'client_id'     => '8a8135d4-31ca-47...',
    'client_secret' => 'zMZFNnho8FozhrDzxrbA9xuR9...',
    'redirect_uri'  => 'https://yoursite.com/auth/callback',
    'zone'          => 'ru', // или 'com' для Kommo
]);

// первичная авторизация (один раз)
// $url = $api->oauth->getUrl();
// $api->oauth->fetchToken($_GET['code']);

// или долгосрочный токен
// $api->oauth->setLongToken($long_token);

$leads = $api->leads()->get();
foreach ($leads as $lead) {
    echo $lead->name . "\n";
}
```

Подробнее: [Конфигурация](docs/configuration.md).

Тесты: [docs/tests.md](docs/tests.md).

---

## 📚 Документация

| Раздел | Описание |
|--------|----------|
| [Конфигурация](docs/configuration.md) | Клиент, OAuth, кеш, callbacks, мультиаккаунт, произвольные запросы |
| [Работа с сущностями](docs/entities.md) | Сервисы, модели, коллекции, пагинация, фильтры, поиск |
| [Тесты](docs/tests.md) | Unit / integration, `.env`, coverage |

### Сущности

| Сущность |  |
|----------|------|
| [Параметры аккаунта](docs/entities/account.md) | account |
| [Сделки](docs/entities/leads.md) | leads |
| [Неразобранное](docs/entities/unsorted.md) | unsorted |
| [Воронки и этапы сделок](docs/entities/pipelines.md) | pipelines, pipelineStatuses |
| [Причины отказа](docs/entities/loss-reasons.md) | lossReasons |
| [Контакты](docs/entities/contacts.md) | contacts |
| [Компании](docs/entities/companies.md) | companies |
| [Списки](docs/entities/catalogs.md) | catalogs |
| [Элементы списков](docs/entities/catalog-elements.md) | catalogElements |
| [Связи сущностей](docs/entities/links.md) | links |
| [Задачи](docs/entities/tasks.md) | tasks |
| [Поля и группы полей](docs/entities/custom-fields.md) | customFields |
| [События](docs/entities/events.md) | events |
| [Примечания](docs/entities/notes.md) | notes |
| [Покупатели](docs/entities/customers.md) | customers |
| [Статусы и сегменты покупателей](docs/entities/customer-segments.md) | customerSegments |
| [Пользователи](docs/entities/users.md) | users |
| [Вебхуки](docs/entities/webhooks.md) | webhooks |
| [Виджеты](docs/entities/widgets.md) | widgets |
| [Salesbot](docs/entities/bots.md) | bots |
| [Агенты Аммы](docs/entities/agents.md) | agents |
| [Подписчики сущности](docs/entities/subscriptions.md) | subscriptions |
| [Источники](docs/entities/sources.md) | sources |
| [Беседы](docs/entities/talks.md) | talks |
| [Файлы (Drive API)](docs/entities/files.md) | files |
