# Bots

[← README](../../README.md) · [Сущности](../entities.md)

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
