# Вебхуки

[← README](../../README.md) · [Сущности](../entities.md)

```php
// получение всех вебхуков
$webhooks = $api->webhooks()->get();
// или конкретных по url
$webhooks = $api->webhooks()->get($some_url);

foreach($webhooks as $webhook) {
    // отписка
    $webhook->unsubscribe();
}
// подписка на вебхук
$webhook = $api->webhooks()->subscribe($some_url, ['note_lead','note_contact','...']);
// отписка
$result = $api->webhooks()->unsubscribe($some_url);
```
