# Конфигурация

[← README](../README.md)

## Определение клиента

```php
$api = \Ufee\AmoV4\ApiClient::setInstance([
    'domain'        => 'yourdomain',           // домен (без .amocrm.ru)
    'client_id'     => '8a8135d4-31ca-47...', // ID интеграции
    'client_secret' => 'zMZFNnho8FozhrDzxrbA9xuR9...',
    'redirect_uri'  => 'https://yoursite.com/auth/callback',
    'zone'          => 'ru', // или 'com' для Kommo
]);
```

## Мультиаккаунтность

Экземпляр клиента определяется парой `client_id` и `domain`: одна интеграция может обслуживать много аккаунтов.

```php
$apiA = \Ufee\AmoV4\ApiClient::setInstance([...]); // client_id = X, domain = a
$apiB = \Ufee\AmoV4\ApiClient::setInstance([...]); // client_id = X, domain = b

$apiA = \Ufee\AmoV4\ApiClient::getInstance(\Ufee\AmoV4\ApiClient::instanceKey($client_id, 'a'));
$exists = \Ufee\AmoV4\ApiClient::hasInstance($apiA->getKey());
\Ufee\AmoV4\ApiClient::removeInstance($apiA->getKey());
```

Голый `client_id` в `getInstance` разрешается, только если под ним один живой аккаунт, иначе исключение о неоднозначности.

Реестр не владеет клиентами: клиент живёт, пока на него есть ссылка у приложения. Сильно держится только последний зарегистрированный клиент каждого `client_id`. Сервисы, модели и запросы ссылаются на своего клиента напрямую, поэтому клиент, выпавший из реестра, продолжает работать у владельца. `domain` и `client_id` после создания не меняются: для другого аккаунта вызывайте `setInstance`.

## Настройка параметров (опционально)

```php
$api->setParam('query_delay', 0.15);   // задержка между запросами (сек)
$api->setParam('query_retries', 3);    // кол-во попыток при ошибках 429
$api->setParam('lang', 'ru');          // язык аккаунта
$api->setParam('timezone', 'UTC');     // таймзона аккаунта, по умолчанию null
```

Таймзона по умолчанию определяется автоматически из настроек аккаунта
(`_embedded.datetime_settings.timezone` в `/api/v4/account?with=datetime_settings`)
и берётся из кэша аккаунта, так что дополнительных запросов не делает.
Она используется при работе с полями [date / birthday](entities/custom-fields/date.md)
и [date_time](entities/custom-fields/date-time.md).
Явно заданный `timezone` переопределяет настройки аккаунта, а если аккаунт
недоступен — используется `Europe/Moscow`.

```php
$tz = $api->timezone(); // \DateTimeZone
```

## OAuth 2.0

### Хранилище токенов

#### Файловое хранение

Используется по умолчанию: `/src/Temp/{domain}/{client_id}.json`

```php
$api->oauth->setStorageFiles('/path/to/oauth/storage');
```

#### Долгосрочный токен

```php
$api->oauth->setLongToken($long_token);
```

#### Redis

Поддерживается библиотека [phpredis](https://github.com/phpredis/phpredis)

```php
$redis = new \Redis();
$redis->connect('127.0.0.1');
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP); // или \Redis::SERIALIZER_IGBINARY
$redis->select(4);

$api->oauth->setStorageRedis($redis);
```

#### MongoDB

Поддерживается библиотека [mongo-php-library](https://github.com/mongodb/mongo-php-library)

```php
$mongo = new \MongoDB\Client('mongodb://127.0.0.1');
$collection = $mongo->selectCollection('amo', 'oauth');

$api->oauth->setStorageMongo($collection);
```

### Первичная авторизация

```php
// 1. URL для авторизации пользователя
$url = $api->oauth->getUrl(['state' => 'custom-state']);
// редирект пользователя на $url

// 2. На callback-странице обменять code на токен (сохранится в storage)
$oauth = $api->oauth->fetchToken($_GET['code']);

// 3. Дальше клиент сам обновляет access_token при необходимости
```

### Работа с токеном вручную

```php
$oauth = $api->oauth->get();                 // весь набор данных
$access = $api->oauth->get('access_token');  // конкретное поле
$api->oauth->set($oauth);                   // записать вручную
$api->oauth->refreshToken();                // принудительное обновление
```

## Кеширование данных

Поддерживается кеширование справочников и общих данных аккаунта.

### Время жизни для кеша / по умолчанию

```php
$api->cache->setTtl([
    'account'      => 60, // 3600
    'users'        => 60, // 1800
    'pipelines'    => 60, // 3600
    'catalogs'     => 60, // 3600
    'sources'      => 60, // 3600
    'userGroups'   => 60, // 3600
    'customFields' => 60, // 1800
    'taskTypes'    => 60, // 3600
    'lossReasons'  => 60, // 3600
    'eventTypes'   => 60  // 86400
]);
```

### Файловое хранение кеша

Используется по умолчанию: `/src/Temp/{domain}/{client_id}-{key}.cache`

```php
$api->cache->setStorageFiles('/path/to/cache/storage', [
    'serialize'   => 'igbinary_serialize', // рекомендуется вместо serialize
    'unserialize' => 'igbinary_unserialize' // рекомендуется вместо unserialize
]);
```

### Очистка кеша

```php
$api->cache->clear();            // весь кеш текущих domain + client_id
$api->cache->clear('pipelines'); // конкретный ключ
```

Очистка затрагивает оба слоя: локальный кеш в памяти процесса и постоянное хранилище
(файлы `{path}/{domain}/{client_id}-{key}.cache` либо ключи Redis `{domain}:{client_id}:{key}`).

Ключи без параметров:

```php
$api->cache->clear('account');      // только account() без $with
$api->cache->clear('users');        // только users() без $with
$api->cache->clear('pipelines');
$api->cache->clear('catalogs');
$api->cache->clear('sources');
$api->cache->clear('userGroups');
$api->cache->clear('taskTypes');
$api->cache->clear('lossReasons');
```

Методы с аргументами кешируются под составным ключом, и его нужно указывать целиком:

```php
$api->cache->customFields('leads');     // ключ customFields-leads
$api->cache->clear('customFields-leads');

$api->cache->eventTypes('ru');          // ключ eventTypes-ru
$api->cache->clear('eventTypes-ru');

$api->cache->account(['task_types']);   // ключ account-task_types
$api->cache->clear('account-task_types');

$api->cache->users(['role']);           // ключ users-role
$api->cache->clear('users-role');
```

`clear('customFields')` или `clear('eventTypes')` в этом случае ничего не удалят —
для сброса всех вариантов используйте `clear()` без аргумента.

### Redis

Поддерживается библиотека [phpredis](https://github.com/phpredis/phpredis)

```php
$redis = new \Redis();
$redis->connect('127.0.0.1');
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP); // или \Redis::SERIALIZER_IGBINARY
$redis->select(4);

$api->cache->setStorageRedis($redis);
```

## События (Callbacks)

Мониторинг запросов, логирование, обработка ошибок, контроль.

```php
$api->callbacks->on($event, function ($payload) {
   // подписка на события
});

$api->callbacks->once($event, function ($payload) {
   // одноразовая подписка (после первого вызова снимается)
});

$api->callbacks->off($event); // снять все callbacks события

$api->callbacks->has($event); // bool
```

### Поддерживаемые события

События по query выполняются в последовательности, указанной ниже.

```php
$api->callbacks->off('query.delay')->on('query.delay', function ($query) {
    // по умолчанию прописана логика задержек на основе $query->instance->getParam('query_delay')
    // пауза между запросами вычисляется автоматически
    sleep(1); // кастомная логика задержек между запросами
});

$api->callbacks->on('query.request.before', function ($query) {
    // вызывается перед выполнением запроса
    echo '['.$query->method.'] '.$query->getUrl();
});

$api->callbacks->on('query.response.code', function ($code, $query) {
    // вызывается после выполнения запроса
    // по умолчанию присутствует обработка кодов:
    // 429 - повторные попытки
    // 401 - повторная попытка с переполучением токена из хранилища
    // 502,504 - однократный повтор
    // return false; прерывает дальнейшую логику обработки
});

$api->callbacks->on('query.response.fail', function ($query, $code) {
    // вызывается после неудачного выполнения запроса
    // все коды ответа кроме 200,204
    if ($code === 0) {
        echo 'Error: '.$query->response->getError()."\n\n";
    } else {
        echo "Response:\n".$query->endDate().' - ['.$code.'] '.$query->response->getData()."\n\n";
    }
});

$api->callbacks->on('query.response.after', function ($query, $code) {
    // вызывается всегда после выполнения запроса
    if ($code === 0) {
        echo 'Error: '.$query->response->getError()."\n\n";
    } else {
        echo "Response:\n".$query->endDate().' - ['.$code.'] '.$query->response->getData()."\n\n";
    }
});

$api->callbacks->on('oauth.token.fetch', function ($oauth, $query, $response) {
    // вызывается после извлечения токена
});

$api->callbacks->on('oauth.token.refresh', function ($oauth, $query, $response) {
    // вызывается после обновления токена
});

$api->callbacks->on('oauth.token.refresh.error', function ($exc, $query = null, $response = null) {
    // вызывается после неудачного обновления токена
});

// refresh token race condition solution
$redis = new \Redis();
$redis->connect('127.0.0.1');
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

$api->callbacks->on('oauth.token.refresh.lock', function ($domain, $client_id) use ($redis) {
    // необходимо вернуть true в случае успешной блокировки, иначе false
    return $redis->set('lock:'.$domain.':'.$client_id, 1, ['nx', 'ex' => 30]);
});

$api->callbacks->on('oauth.token.refresh.unlock', function ($domain, $client_id) use ($redis) {
    // снять блокировку
    $redis->del('lock:'.$domain.':'.$client_id);
});
```

## Произвольные API запросы

```php
$query = $api->query('GET', '/api/v4/leads/'.$lead_id);
$query->execute();
$raw = $query->response->validated();
print_r($raw); // object of lead
echo $raw->name;
```

## Запросы от имени пользователя

Если интеграции доступ выдал администратор аккаунта, запросы можно выполнять с правами конкретного пользователя — библиотека передаёт заголовок `X-Context-User-ID` ([документация amoCRM](https://www.amocrm.ru/developers/content/oauth/scopes)).

Для всех запросов клиента:

```php
$api->asUser(1234567);       // все следующие запросы уйдут с правами пользователя
$leads = $api->leads()->get();

$api->getContextUser();      // 1234567
$api->asUser(null);          // сброс, дальше запросы идут с правами интеграции
```

Для одного запроса (приоритетнее значения клиента):

```php
$query = $api->query('GET', '/api/v4/leads');
$query->setContextUser(1234567);
$query->execute();

// отключить пользователя клиента для конкретного запроса
$query = $api->query('GET', '/api/v4/account');
$query->setContextUser(null);
$query->execute();
```

Учтите:
- если передан несуществующий или бесплатный пользователь аккаунта — amoCRM ответит ошибкой авторизации (401);
- заголовок не влияет на получение и обновление токенов (`/oauth2/access_token`);
- в мультиаккаунтности значение хранится в конкретном экземпляре клиента, см. [Мультиаккаунтность](#мультиаккаунтность).
