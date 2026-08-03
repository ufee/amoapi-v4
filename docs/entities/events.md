# События

[← README](../../README.md) · [Сущности](../entities.md)

```php
$paginate = $api->events()->filter(['type' => 'lead_added'])->paginate();
foreach ($paginate as $page_num => $events) {
    print_r($events);
}

$event = $api->events()->find($event_id);
$type = $event->type(); // тип из кеша

// типы событий
$types = $api->events()->types('ru');
$types = $api->cache->eventTypes(); // текущий язык по умолчанию
$types = $api->cache->eventTypes('en');

$api->cache->clear('eventTypes');
```
