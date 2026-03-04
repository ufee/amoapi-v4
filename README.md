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
Используется по умолчанию: `/src/Temp/{domain}/{client_id}.json`
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
$redis->connect('127.0.0.1');
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
    'pipelines'    => 60, // 3600
    'userGroups'   => 60, // 3600
    'customFields' => 60, // 1800
    'taskTypes'    => 60, // 3600
    'lossReasons'  => 60, // 3600
    'eventTypes'   => 60  // 86400
]);
```
Файловое хранение кэша
Используется по умолчанию: `/src/Temp/{domain}/{client_id}.{key}.cache`
```php
$api->cache->setStorageFiles('/path/to/cache/storage', [
    'serialize'   => 'igbinary_serialize', // рекомендуется вместо serialize
    'unserialize' => 'igbinary_unserialize' // рекомендуется вместо unserialize
]);
```
Очистка кеша
```php
$api->cache->clear('account');
$api->cache->clear('customFields');
$api->cache->clear('taskTypes');
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
### Произвольные API запросы
```php
$query = $api->query('GET','/api/v4/leads/'.$lead_id);
$query->execute();
$raw = $query->response->validated();
print_r($raw); // object of lead
echo $raw->name;
```
### 📥 Работа с сущностями
Производится через сервисы:
```php
// получение экземпляра сервиса
$service = $api->account();
$api->users();
$api->customFields($entity_type);
$api->pipelines();
$api->pipelineStatuses($pipeline_id);
$api->lossReasons();
$api->leads();
$api->contacts();
$api->companies();
$api->links();
$api->tasks();
$api->notes($entity_type);
$api->events();
$api->widgets();
$api->webhooks();
$api->bots();
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
Создание/обновление сущностей через сырые данные
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
Осуществляется на основании `_links->next->href` из ответа
```php
$paginate = $api->leads()->paginate();
$paginate->maxPages(10); // максимальное кол-во страниц
$paginate->maxRows(100); // максимальное кол-во сущностей на странице

do {
    $leads = $paginate->fetchPage();
    echo "\nPage ".$paginate->page."\n";
    print_r($leads); // collection
} while(
    $paginate->nextPage();
);

// или так
foreach($paginate as $page_num=>$leads) {
    echo "\nPage ".$page_num."\n";
    print_r($leads); // collection
}
```
В некоторых случаях `_links->next->href` отсутствует в ответе, но следующие страницы при этом существуют, в таком случае можно принудительно получить все страницы, как это делается в `$paginate->fetchAll();`
```php
$paginate->maxPages(10);
while (
    $paginate->valid() && ($leads = $paginate->fetchPage()) && $leads->count()
) {
    echo "\nPage ".$paginate->page."\n";
    print_r($leads); // collection
    $paginate->setPageNum($paginate->page+1);
}

// или так
$leads = $paginate->fetchAll($max_pages);
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

// очистка кеша
$api->cache->clear('account');
```
#### Пользователи аккаунта
```php
$users = $api->users()->get();
// или из кеша
$users = $api->cache->users();
// пользователь по id
$user = $api->cache->user(128737);
```
#### Воронки сделок
```php
$pipelines = $api->pipelines()->get();
$pipeline = $api->pipelines()->find($pipeline_id);
// или из кеша
$pipelines = $api->cache->pipelines();
$pipeline = $api->cache->pipeline($pipeline_id);
// или новая воронка
$pipeline = $api->pipelines()->create(['name' => 'Рекламации']);

$pipeline->sort = 20;
$pipeline->is_main = false;
$pipeline->is_unsorted_on = false;
$pipeline->_embedded = [];
$pipeline->save();

// этапы воронки
$statuses = $pipeline->statuses(); // collection
// удаление воронки
$pipeline->delete();
```
#### Этапы воронок
```php
$statuses = $api->pipelineStatuses($pipeline_id)->with(['descriptions'])->get();
$status = $api->pipelineStatuses($pipeline_id)->find($status_id, ['descriptions']);
// или новый этап
$status = $api->pipelineStatuses($pipeline_id)->create(['name' => 'Договор подписан']);

$status->sort = 50;
$status->save();

// удаление этапа
$status->delete();
```
#### Кастомные поля аккаунта
```php
$service = $api->customFields('contacts');
$service = $api->customFields('catalogs', $catalog_id);
$service->maxPageRows(10);
$service->orderBy('sort', 'desc');

// получение коллекции
$cfields = $service->get();
// или из кеша
$cfields = $api->cache->customFields('contacts');
$cfields = $api->cache->customFields('catalogs', $catalog_id);
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
#### Кастомные поля сущности
```php
$lead = $api->leads()->find($lead_id);

$cf = $lead->cf('Варианты оплаты');
$cf->reset();
$cf->setValues(['Онлайн','При получении']);
$cf->setEnums([845234,945431]);
$values = $cf->getValues();

// поле по названию
$cf = $lead->cf('Город');
// поле по id
$cf = $lead->cf(3745829);

$cf->setValue('Москва');
$cf->setEnum(546710);
$value = $cf->getValue();

$field = $cf->field;
$enum_values = $field->getEnums();
$enum_ids = $field->getEnumIds();
$values = $field->getValues();
$bool = $field->hasEnum(568345);
$bool = $field->hasValue('Чебоксары');

$cf = $lead->cf()->byName('Город');
$cf = $lead->cf()->byId(3745829);
$cf = $lead->cf()->byCode('PHONE');
$cf = $lead->cf()->byType('radiobutton');

$cfields = $lead->cf()->all();
foreach($cfs as $cf) {
    print_r($cf->getValue());
    echo "\n";
}
```
#### Сделки
```php
$leads = $api->leads()->get();
$leads = $api->leads;
$leads = $api->leads()->with(['source_id','source']))->get();
$leads = $api->leads()->searchByCustomField('Москва', 'Город', 1); // 1 page (250 rows max)
$leads = $api->leads()->searchByName('Разработка ПО', 1, ['source_id','source']); // 1 page with source

$paginate = $api->leads()
                ->orderBy('updated_at', 'desc')
                ->with(['source_id','source','loss_reason'])
                ->paginate();

$paginate = $api->leads()->filter($conditions = [],  $with = []);
$paginate = $api->leads()->search('VIP');

$lead = $api->leads()->find($lead_id);
$lead = $api->leads()->find($lead_ids, ['source_id','source']);
$lead = $api->leads()->create(['name' => 'Новая сделка']);

$lead->price = 100;
$lead->status_id = 21776227;
$lead->cf('Приоритет')->setValue('Высокий'); // set value name by cf name
$lead->cf(123678)->setEnum(83565); // set enum id by cf id

$lead->attachTag('Tag1');
$lead->attachTag(['name' => 'Цветной', 'color' => 'FF8F92']);
$lead->detachTag('Tag3');
$tags = $lead->getTags();

// replace all existing tags
$lead->setTags($tags); // ids or names
$lead->resetTags(); // replace with none

$paginate = $lead->getTasks($filter = []);
$tasks = $lead->getTasks($filter = [])->fetchAll();
$task = $lead->findTask($task_id);
$task = $lead->createTask($type = 1);

$paginate = $lead->getNotes($filter = []);
$notes = $lead->getNotes($filter = [])->fetchAll();
$note = $lead->findTask($task_id);
$note = $lead->createNote($type = 'common');

```

#### Причины отказа сделок
```php
// через новый sugar-метод Leads сервиса
$lossReasons = $api->leads()->lossReasons()->get();
$lossReason = $api->leads()->lossReasons()->find($loss_reason_id);

// или напрямую через отдельный сервис
$lossReasons = $api->lossReasons()->get();
$lossReason = $api->lossReasons()->find($loss_reason_id);

// или из кеша
$lossReasons = $this->crm->cache->lossReasons();
$lossReason = $this->crm->cache->lossReason($loss_reason_id);

// очистка кеша
$this->crm->cache->clear('lossReasons');
```

#### Виджеты
```php
$paginate = $api->widgets()->paginate();
$paginate->maxRows(100)->maxPages(10);

// пагинация на основе next link
foreach($paginate as $page_num=>$widgets) {
    echo "\nPage ".$page_num." loaded ".$widgets->count()."\n\n";
    print_r($widgets); // collection
}
// принудительная пагинация на основании наличия данных
while (
    $paginate->valid() && ($widgets = $paginate->fetchPage()) && $widgets->count()
) {
    echo "\nPage ".$paginate->page." loaded ".$widgets->count()."\n\n";
    $paginate->setPageNum($paginate->page+1);
}

// получение всех страниц в виде одной коллекции
$widgets = $api->widgets()->get();
$widgets = $api->widgets()->paginate()->fetchAll($max_pages);

$widget = $widgets->where('id', 972)->first();
// или получение отдельным запросом по коду
$widget = $api->widgets()->find('amo_asterisk');

// установка виджета, возвращает модель
$installed = $api->widgets()->install('amo_asterisk', $settings);
// или через модель
$installed = $widget->install([
    'login' => 'example',
    'password' => 'eXaMp1E',
    'phones' => [
        1234 => '8927047',
        5678 => '8906000',
    ],
    'script_path'=> 'https://site.ru/'
]);

// удаление установки виджета
$bool = $api->widgets()->uninstall('amo_asterisk');
// или через модель
$bool = $widget->uninstall();
```

#### Вебхуки
```php
// получение всех вебхуков
$webhooks = $this->crm->webhooks()->get();
// или конкретных по url
$webhooks = $this->crm->webhooks()->get($some_url);

foreach($webhooks as $webhook) {
    // отписка
    $webhook->unsubscribe();
}
// подписка на вебхук
$webhook = $this->crm->webhooks()->subscribe($some_url, ['note_lead','note_contact','...']);
// отписка
$result = $this->crm->webhooks()->unsubscribe($some_url);
```

#### Bots
```php
// запуск Bot (до 100 задач за запрос)
// вариант 1: массив задач
$is_started = $api->bots()->run([
    [
        'bot_id' => 565,
        'entity_id' => 76687686,
        'entity_type' => 'leads', // leads|contacts|customers
    ],
]);

// вариант 2: 3 параметра (bot_id, entity_id, entity_type)
$is_started = $api->bots()->run(
    $bot_id = 565,
    $entity_id = 76687686,
    $entity_type = 'contacts' // leads|contacts|customers
);

// остановка Bot для сделки
$is_stopped = $api->bots()->stop(
    $bot_id = 565,
    $entity_id = 23890022,
    $entity_type = 'leads' // leads|customers
);
```
