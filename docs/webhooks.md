# Вебхуки

[← README](../README.md)

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
