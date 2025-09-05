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
$api->setParam('query_retries', 3);    // кол-во попыток при ошибках 429
$api->setParam('lang', 'ru');          // язык аккаунта
```

### Хранилище Oauth 
Файловое хранение OAuth-токенов  
Используется по умолчанию: /src/Temp/{domain}/{client_id}.json
```php
$api->oauth->setStorageFiles('/path/to/oauth/storage');
```
**Долгосрочный токен**  
```php
$api->oauth->setLongToken($long_token);
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
**Mongodb**  
Поддерживается библиотека [mongo-php-library](https://github.com/mongodb/mongo-php-library)
```php
$mongo = new \MongoDB\Client('mongodb://127.0.0.1');
$collection = $mongo->selectCollection('amo', 'oauth');

$api->oauth->setStorageMongo($mongo);
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
```
Файловое хранение OAuth-токенов  
Используется по умолчанию: /src/Temp/{domain}/{client_id}.{key}.cache
```php
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

### 🔔 События (Callbacks)  
Мониторинг запросов, логирование, обработка ошибок, контроль  
```php
$api->callbacks->on($event, function($payload) {
   // подписка на события
});
```
```php
$api->callbacks->off($event, function($payload) {
   // отписка от событий
});
```
Примеры событий  
```php
$api->callbacks->off('query.delay')->on('query.delay', function($query) {
    // кастомная логика задержек между запросами
});

$api->callbacks->on('query.response.before', function($query) {
    // вызывается перед выполнением запроса
});

$api->callbacks->on('query.response.code', function($code, $query) {
    // вызывается после выполнения запроса
});

$api->callbacks->on('query.response.fail', function($query, $code) {
    // вызывается после неудачного выполнения запроса
});

$api->callbacks->on('query.response.after', function($query, $code) {
    // вызывается после выполнения запроса
});

$api->callbacks->on('oauth.token.fetch', function($oauth) {
    // вызывается после извлечения токена
});

$api->callbacks->on('oauth.token.refresh', function($oauth) {
    // вызывается после обновления токена
});

$api->callbacks->on('oauth.token.refresh.error', function($oauth) {
    // вызывается после неудачного обновления токена
});
```
### 🔐 Первичное получение OAuth-токена 
```php
$oauth = $api->oauth->fetchToken($code); // сохранится в выбранном storage
```
### 📥 Работа с сущностями  
Производится через сервисы:
```php
$service = $this->crm->account();
$service = $this->crm->users();
$service = $this->crm->customFields($entity_type);
$service = $this->crm->leads();
$service = $this->crm->contacts();
$service = $this->crm->companies();
$service = $this->crm->links();
$service = $this->crm->tasks();
$service = $this->crm->notes();
$service = $this->crm->events();
$service = $this->crm->webhooks();
```
#### Получение сущностей по ID  
```php
$lead = $api->leads()->find(30013961);
$contact = $api->contacts()->find(45968927);
$company = $api->companies()->find(55968943);

$leads = $api->leads()->find([30013961,30013962,30013963]);
```
#### Постраничное получение 
```php
$paginate = $api->leads()->paginate();
$paginate->maxPages(10); // максимальное кол-во страниц
$paginate->maxRows(100); // максимальное кол-во сущностей на странице

do {
    echo "\nPage ".$paginate->page."\n";
    $leads = $paginate->fetchPage();
    print_r($leads);
} while(
    $paginate->next()
);

// или так
foreach($paginate as $page_num=>$leads) {
    echo "\nPage ".$page_num."\n";
	print_r($leads);
}
```


