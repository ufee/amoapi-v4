# События

[← README](../../README.md) · [Сущности](../entities.md)

```php
$paginate = $api->events()->filter(['type' => 'lead_added']);
foreach ($paginate as $page_num => $events) {
    print_r($events);
}

$events = $api->events()->withAll()->get();
$events = $api->events()->with([
    \Ufee\AmoV4\Services\Events::CONTACT_NAME,
    \Ufee\AmoV4\Services\Events::LEAD_NAME,
    \Ufee\AmoV4\Services\Events::COMPANY_NAME,
])->get();

$event = $api->events()->find($event_id);
$type = $event->type(); // тип из кеша

// типы событий
$types = $api->events()->types('ru');
$types = $api->cache->eventTypes(); // текущий язык по умолчанию
$types = $api->cache->eventTypes('en');

$api->cache->clear('eventTypes');
```
