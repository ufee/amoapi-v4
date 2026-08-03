# Вебхуки

[← README](../../README.md) · [Сущности](../entities.md)

```php
// получение всех вебхуков
$webhooks = $api->webhooks()->get();
// или конкретных по url
$webhooks = $api->webhooks()->get($some_url);

foreach ($webhooks as $webhook) {
    // отписка
    $webhook->unsubscribe();
}

// подписка на вебхук (список событий — по документации amoCRM/Kommo)
$webhook = $api->webhooks()->subscribe($some_url, [
    'add_lead',
    'update_lead',
    'add_contact',
    'update_contact',
    'add_company',
    'note_lead',
    'note_contact',
]);

// отписка
$result = $api->webhooks()->unsubscribe($some_url);
```
