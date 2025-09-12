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
Поддерживаемые события  
События по query выполняются в последовательности, указанной ниже
```php
$api->callbacks->off('query.delay')->on('query.delay', function($query) {
    // по умолчанию прописана логика задержек на основе $query->instance->getParam('query_delay')
    // пауза между запросами вычисляется автоматически
    sleep(1); // кастомная логика задержек между запросами
});

$api->callbacks->on('query.request.before', function($query) {
    // вызывается перед выполнением запроса
    echo '['.$query->method.'] '.$query->getUrl();
});

$api->callbacks->on('query.response.code', function($code, $query) {
    // вызывается после выполнения запроса
    // поумолчанию присутствует обработка кодов:
    // 429 - повторные попытки
    // 401 - повторная попытка с переполучением токена из хранилища
    // 502,504 - однократный повтор
    // return false; прерывает дальнейшую логику обработки
});

$api->callbacks->on('query.response.fail', function($query, $code) {
    // вызывается после неудачного выполнения запроса
    // все коды ответа кроме 200,204
    if ($code === 0) {
        echo 'Error: '.$query->response->getError()."\n\n";
    } else {
        echo "Response:\n".$query->endDate().' - ['.$code.'] '.$query->response->getData()."\n\n";
    }
});

$api->callbacks->on('query.response.after', function($query, $code) {
    // вызывается всегда после выполнения запроса
    if ($code === 0) {
        echo 'Error: '.$query->response->getError()."\n\n";
    } else {
        echo "Response:\n".$query->endDate().' - ['.$code.'] '.$query->response->getData()."\n\n";
    }
});

$api->callbacks->on('oauth.token.fetch', function($oauth, $query, $response) {
    // вызывается после извлечения токена
});

$api->callbacks->on('oauth.token.refresh', function($oauth, $query, $response) {
    // вызывается после обновления токена
});

$api->callbacks->on('oauth.token.refresh.error', function($exc, $query = null, $response = null) {
    // вызывается после неудачного обновления токена
});
```
### 🔐 Первичное получение OAuth-токена 
```php
$api->oauth->fetchToken($code); // токен сохранится в выбранном storage
```
### 📥 Работа с сущностями  
Производится через сервисы:
```php
// получение экземпляра сервиса
$service = $api->account();
$api->users();
$api->customFields($entity_type);
$api->leads();
$api->contacts();
$api->companies();
$api->links();
$api->tasks();
$api->notes($entity_type);
$api->events();
$api->webhooks();
```
Установка параметров
```php
$service->maxPageRows($value);
$service->orderBy($field, $direction = 'asc')
$service->with($values);
$service->setQueryArg($key, $value);
$service->setQueryArgs($args = []);

```
Получение сущностей
```php
$model = $service->find($elem_id, $with = []);
$collection = $service->get($with = null);
$paginate = $service->paginate($with = null);
$paginate = $service->filter($conditions, $with = []);
$paginate = $service->search($phrase, $with = []);

```
Создание/обновление сущнотей через сырые данные  
$raw_data может быть объектом или массивом объектов  
На практике не применяется, так как действие производится через модель
```php
$raw_response = $service->add($raw_data);
$raw_response = $service->update($raw_data);
```
#### Модель сущности  
Поля моделей динамические, реализованы через геттеры и сеттеры
```php
$model = $service->create(['field1' => 'value', 'field2' => 'value', ...]);
// или 
$model = $service->find(123567);

$model->name = 'Name';
$model->price = 100;
$model->save(); // создание или обновление сущности под капотом
$model->toArray(); 
```
#### Коллекция сущностей  
```php
$models = $service->createCollection([
    ['field1' => 'value', 'field2' => 'value', ...],
    ['field1' => 'value', 'field2' => 'value', ...],
]);
// или 
$models = $service->get();

foreach($models as $model) {
    $model->attachTag('AmoV4');
}
$models->save(); // массовое создание или обновление сущностей под капотом
$models->toArray(); 
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
    print_r($leads); // collection
} while(
    $paginate->next();
);

// или так
foreach($paginate as $page_num=>$leads) {
    echo "\nPage ".$page_num."\n";
    print_r($leads); // collection
}
```
#### Фильтрация сущностей
```php
$paginate = $api->leads()->filter([
    'price' => ['from' => 0, 'to' => 100500]
]);
foreach($paginate as $page_num=>$leads) {
    echo "\nPage ".$page_num."\n";
    print_r($leads); // collection
}
```
#### Поиск сущностей
```php
$leads = $service->searchByCustomField(string $query, string $field, int $page_limit = 0, array $with = []);
$contacts = $service->searchByPhone(string $phone, int $page_limit = 0, array $with = []);
$contacts = $service->searchByEmail(string $email, int $page_limit = 0, array $with = []);
$companies = $service->searchByName(string $name, int $page_limit = 0, array $with = []);

$paginate = $leads->search('query', ['source_id','source']);
```
#### Аккаунт
```php
$account = $api->account()->get();
// или
$account = $api->account;
// или из кеша
$account = $api->cache->account();

// модель аккаунта
echo $account->id;
echo $account->name;

$userGroups = $account->userGroups; // collection
$taskTypes = $account->taskTypes; // collection

// или из кеша
$userGroups = $api->cache->userGroups();
$taskTypes = $api->cache->taskTypes();
$eventTypes = $api->cache->eventTypes($lang = null); // текущий язык по умолчанию
```
#### Пользователи аккаунта
```php
$users = $api->users()->get();
// или из кеша
$users = $api->cache->users();

$user = $api->cache->user(2787376);
```
#### Кастомные поля аккаунта
```php
$service = $api->customFields('contacts');
$service = $api->customFields('catalogs', 6897);
$service->maxPageRows(10);
$service->orderBy('sort', 'desc');

// получение коллекции
$cfields = $service->get();
// или из кеша
$cfields = $api->cache->customFields('contacts');
$cfields = $api->cache->customFields('catalogs', 6897);
```
Создание поля
```php
$service = $api->customFields('contacts');
$cf = $service->create(['name' => 'Варианты оплаты']);
$cf->type = 'multiselect';
$cf->enums = [
    ['value' => 'Онлайн', 'sort' => 0],
    ['value' => 'При получении', 'sort' => 1],
    ['value' => 'СБП', 'sort' => 2]
];
$cf->save();
```
#### Сделки
```php
$leads = $api->leads()->get();
$leads = $api->leads()->filter([...]);
$leads = $api->leads;

...
```

