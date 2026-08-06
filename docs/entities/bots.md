# Salesbot

[← README](../../README.md) · [Сущности](../entities.md)

```php
$bots = $api->bots()->get();
$bots = $api->bots()->withAll()->get();
$bots = $api->bots()->with([\Ufee\AmoV4\Services\Bots::FAVORITE])->get();
$bots = $api->bots()->filter([
    'type_functionality' => [\Ufee\AmoV4\Services\Bots::TYPE_REGULAR],
])->get();
$bot = $api->bots()->find($bot_id);
$bot = $api->bots()->find($bot_id, [\Ufee\AmoV4\Services\Bots::FAVORITE]);

// запуск одного бота: POST /api/v4/bots/{id}/run
$is_started = $api->bots()->run(
    $bot_id = 565,
    $entity_id = 76687686,
    $entity_type = 'contacts' // leads|contacts|customers
);

// групповой запуск (до 100 задач): POST /api/v4/bots/run
$is_started = $api->bots()->run([
    [
        'bot_id' => 565,
        'entity_id' => 76687686,
        'entity_type' => 'leads', // leads|contacts|customers
    ],
]);

// остановка бота для сделки: POST /api/v4/bots/{id}/stop
$is_stopped = $api->bots()->stop(
    $bot_id = 565,
    $entity_id = 23890022,
    $entity_type = 'leads' // только leads
);
```
