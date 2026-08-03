# Работа с сущностями

[← README](../README.md)

Общие паттерны для всех сервисов. Документация по конкретным сущностям — ниже и в [entities/](entities/).

| Сущность | Файл |
|----------|------|
| [Аккаунт](entities/account.md) | account |
| [Пользователи](entities/users.md) | users |
| [Воронки и этапы](entities/pipelines.md) | pipelines, pipelineStatuses |
| [Списки](entities/catalogs.md) | catalogs |
| [Элементы списков](entities/catalog-elements.md) | catalogElements |
| [Кастомные поля](entities/custom-fields.md) | customFields |
| [Сделки](entities/leads.md) | leads |
| [Контакты](entities/contacts.md) | contacts |
| [Компании](entities/companies.md) | companies |
| [Покупатели](entities/customers.md) | customers |
| [Сегменты](entities/customer-segments.md) | customerSegments |
| [Причины отказа](entities/loss-reasons.md) | lossReasons |
| [Задачи](entities/tasks.md) | tasks |
| [Заметки](entities/notes.md) | notes |
| [События](entities/events.md) | events |
| [Связи](entities/links.md) | links |
| [Виджеты](entities/widgets.md) | widgets |
| [Вебхуки](entities/webhooks.md) | webhooks |
| [Боты](entities/bots.md) | bots |
| [Источники](entities/sources.md) | sources |
| [Подписчики](entities/subscriptions.md) | subscriptions |

## Сервисы

```php
$service = $api->account();
$api->users();
$api->customFields($entity_type);
$api->pipelines();
$api->pipelineStatuses($pipeline_id);
$api->lossReasons();
$api->catalogs();
$api->catalogElements($catalog_id);
$api->leads();
$api->contacts();
$api->companies();
$api->customers();
$api->customerSegments();
$api->links();
$api->tasks();
$api->notes($entity_type);
$api->events();
$api->widgets();
$api->webhooks();
$api->bots();
$api->sources();
$api->subscriptions($entity_type, $entity_id); // entity_type: leads|customers
```

## Матрица возможностей

| | leads | contacts | companies | customers |
|---|:---:|:---:|:---:|:---:|
| Tags | ✓ | ✓ | ✓ | ✓ |
| Tasks (модель) | ✓ | ✓ | ✓ | ✓ |
| Notes (модель) | ✓ | ✓ | ✓ | ✓ |
| Notes sugar `$service->notes()` | ✓ | ✓ | ✓ | — |
| Links | ✓ | ✓ | ✓ | ✓ |
| Catalog elements | ✓ | — | — | ✓ |
| Subscriptions (сервис) | ✓ | — | — | ✓ |
| `getSubscriptions()` на модели | ✓ | — | — | — |
| `searchByName` | ✓ | ✓ | ✓ | ✓ |
| `searchByCustomField` | ✓ | ✓ | ✓ | — |
| `searchByPhone` / `searchByEmail` | — | ✓ | ✓ | — |
| Custom fields (`cf`) | ✓ | ✓ | ✓ | ✓ |

## Установка параметров

```php
$service->maxPageRows($value);
$service->orderBy($field, $direction = 'asc');
$service->with($values);
$service->setQueryArg($key, $value);
$service->setQueryArgs($args = []);
```

## Получение сущностей

```php
$model = $service->find($elem_id, $with = []);
$collection = $service->get($with = null);
$paginate = $service->paginate($with = null);
$paginate = $service->filter($conditions, $with = []);
$paginate = $service->search($phrase, $with = []);
```

`filter()` и `search()` уже возвращают `Paginate` — дополнительно вызывать `->paginate()` не нужно.

## Создание/обновление через сырые данные

`$raw_data` может быть объектом или массивом объектов.
На практике не применяется, так как действие производится через модель.

```php
$raw_response = $service->add($raw_data);
$raw_response = $service->update($elem_id, $raw_data); // одна сущность
$raw_response = $service->update($raw_data_array);     // массовое обновление
```

## Модель сущности

Поля моделей динамические, реализованы через геттеры и сеттеры.

```php
$model = $service->create(['field1' => 'value', 'field2' => 'value', ...]);
// или
$model = $service->find(123567);

$model->name = 'Name';
$model->price = 100;
$model->save(); // создание или обновление сущности под капотом
$model->toArray();
```

## Коллекция сущностей

```php
$models = $service->createCollection([
    ['field1' => 'value', 'field2' => 'value', ...],
    ['field1' => 'value', 'field2' => 'value', ...],
]);
// или
$models = $service->get();

foreach ($models as $model) {
    $model->attachTag('AmoV4');
}
$models->save(); // массовое создание или обновление сущностей под капотом
$models->toArray();
```

### Полезные методы коллекции

```php
$leads = $api->leads()->get();

$leads->count();
$leads->first();
$leads->last();
$leads->where('status_id', 142);
$leads->find('id', 30013961);      // коллекция совпадений
$leads->filter(fn ($lead) => $lead->price > 1000);
$leads->map(fn ($lead) => $lead->name);
$leads->sortBy('updated_at', 'DESC');
$leads->groupBy('pipeline_id');
$leads->fieldValues('id');
$leads->each(fn ($lead) => $lead->attachTag('VIP'));
```

## Получение сущностей по ID

```php
$lead = $api->leads()->find(30013961);
$contact = $api->contacts()->find(45968927);
$company = $api->companies()->find(55968943);

$leads = $api->leads()->find([30013961, 30013962, 30013963]);
```

## Постраничное получение

Осуществляется на основании `_links->next->href` из ответа.

```php
$paginate = $api->leads()->paginate();
$paginate->maxPages(10); // максимальное кол-во страниц
$paginate->maxRows(100); // максимальное кол-во сущностей на странице

do {
    $leads = $paginate->fetchPage();
    echo "\nPage ".$paginate->page."\n";
    print_r($leads); // collection
} while (
    $paginate->nextPage()
);

// или так
foreach ($paginate as $page_num => $leads) {
    echo "\nPage ".$page_num."\n";
    print_r($leads); // collection
}
```

В некоторых случаях `_links->next->href` отсутствует в ответе, но следующие страницы при этом существуют. В таком случае можно принудительно получить все страницы, как это делается в `$paginate->fetchAll();`

```php
$paginate->maxPages(10);
while (
    $paginate->valid() && ($leads = $paginate->fetchPage()) && $leads->count()
) {
    echo "\nPage ".$paginate->page."\n";
    print_r($leads); // collection
    $paginate->setPageNum($paginate->page + 1);
}

// или так
$leads = $paginate->fetchAll($max_pages);
```

## Фильтрация сущностей

```php
$paginate = $api->leads()->filter([
    'price' => ['from' => 0, 'to' => 100500]
]);
foreach ($paginate as $page_num => $leads) {
    echo "\nPage ".$page_num."\n";
    print_r($leads); // collection
}
```

## Поиск сущностей

Методы `searchBy*` доступны не у всех сервисов — см. [матрицу](#матрица-возможностей).

```php
$leads = $api->leads()->searchByCustomField('query', 'Город', $page_limit = 0, $with = []);
$contacts = $api->contacts()->searchByPhone('+79001234567', $page_limit = 0, $with = []);
$contacts = $api->contacts()->searchByEmail('a@b.ru', $page_limit = 0, $with = []);
$companies = $api->companies()->searchByName('Ромашка', $page_limit = 0, $with = []);

$paginate = $api->leads()->search('query', ['source_id', 'source']);
```
