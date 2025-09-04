# 🚀 amoCRM PHP API (v4) Client

Поддерживает OAuth 2.0, кэширование, пагинацию, события, автоматическое обновление токенов, обработку ошибок и работу с любыми сущностями (сделки, контакты, компании, задачи, заметки и др.).

---

## ✅ Возможности

- ✅ Пддержка [amoCRM API v4](https://www.amocrm.ru/developers/content/crm_platform/platform-abilities)
- ✅ OAuth 2.0 с автообновлением токенов
- ✅ Хранилище токенов: файлы, Redis, MongoDB
- ✅ Кэширование справочников в файлах и Redis: пользователи, поля, типы задач и т.д.
- ✅ Пагинация с `foreach` и `do-while`
- ✅ Обработка событий через `callbacks`: отладка, контроль
- ✅ Ограничение частоты запросов
- ✅ Поддержка массовых операций

---

## 📦 Установка

```bash
composer require ufee/amoapi-v4
```

## ⚙️ Быстрый старт

### Определение клиента  
```php
$api = \Ufee\AmoV4\ApiClient::setInstance([
    'domain'        => 'yourdomain',           // домен (без .amocrm.ru)
    'client_id'     => '8a8135d4-31ca-47...', // ID интеграции
    'client_secret' => 'zMZFNnho8FozhrDzxrbA9xuR9...',
    'redirect_uri'  => 'https://yoursite.com/auth/callback',
    'zone'          => 'ru', // или 'com' для Kommo
]);
```

### Настройка параметров (опционально)  
```php
$api->setParam('query_delay', 0.15);   // задержка между запросами (сек)
$api->setParam('query_retries', 3);    // кол-во попыток при ошибках
$api->setParam('lang', 'ru');          // язык аккаунта
```

### Хранилище Oauth  
```php
// Файловое хранение OAuth-токенов (по умолчанию: /src/Temp/{domain}/{client_id}.json)
$api->oauth->setStorageFiles('/path/to/oauth/storage');
```
**Redis**  
Поддерживается библиотека [phpredis](https://github.com/phpredis/phpredis)
```php
$redis = new \Redis();
redis->connect('127.0.0.1');
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP); // или \Redis::SERIALIZER_IGBINARY
$redis->select(4);

$api->oauth->setStorageRedis($redis);
```

### Кеширование данных  
Поддерживается кеширование справочников и общих данных аккаунта  
Время жизни для кэша / по умолчанию
```php
$api->cache->setTtl([
    'account'      => 60, // 3600
    'users'        => 60, // 1800
    'userGroups'   => 60, // 3600
    'customFields' => 60, // 1800
    'taskTypes'    => 60, // 3600
    'eventTypes'   => 60  // 86400
]);

// Файловое кэширование (по умолчанию: /src/Temp/{domain}/{client_id}.{key}.cache)
$api->cache->setStorageFiles('/path/to/cache/storage', [
    'serialize'   => 'igbinary_serialize', // рекомендуется вместо serialize
    'unserialize' => 'igbinary_unserialize' // рекомендуется вместо unserialize
]);
```
**Redis**  
Поддерживается библиотека [phpredis](https://github.com/phpredis/phpredis)
```php
$redis = new \Redis();
redis->connect('127.0.0.1');
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP); // или \Redis::SERIALIZER_IGBINARY
$redis->select(4);

$api->cache->setStorageRedis($redis);
```

