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
| [Сущности](docs/entities.md) | Сервисы, модели, коллекции, пагинация, фильтры, поиск |
| [Аккаунт и воронки](docs/account.md) | Аккаунт, пользователи, воронки, этапы |
| [Списки](docs/catalogs.md) | Catalogs API, элементы, привязка к сделкам |
| [Кастомные поля](docs/custom-fields.md) | Поля аккаунта и сущности |
| [Сделки](docs/leads.md) | Leads API, теги, задачи, заметки, причины отказа |
| [Виджеты](docs/widgets.md) | Установка и удаление виджетов |
| [Вебхуки](docs/webhooks.md) | Подписка и отписка |
| [Bots](docs/bots.md) | Запуск и остановка ботов |
| [Источники](docs/sources.md) | Sources API |
| [Подписчики](docs/subscriptions.md) | Subscriptions API |
