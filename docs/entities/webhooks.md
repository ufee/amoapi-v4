# Вебхуки

[← README](../../README.md) · [Сущности](../entities.md)

```php
use Ufee\AmoV4\Services\Webhooks;

// получение всех вебхуков
$webhooks = $api->webhooks()->get();
// или конкретных по url
$webhooks = $api->webhooks()->get($some_url);

foreach ($webhooks as $webhook) {
    // отписка
    $webhook->unsubscribe();
}

// подписка на вебхук
$webhook = $api->webhooks()->subscribe($some_url, [
    Webhooks::EVENT_ADD_LEAD,
    Webhooks::EVENT_UPDATE_LEAD,
    Webhooks::EVENT_ADD_CONTACT,
    Webhooks::EVENT_UPDATE_CONTACT,
    Webhooks::EVENT_ADD_TALK,
    Webhooks::EVENT_UPDATE_TALK,
    Webhooks::EVENT_ADD_MESSAGE,
    Webhooks::EVENT_NOTE_LEAD,
]);

// отписка
$result = $api->webhooks()->unsubscribe($some_url);
```

Константы событий: `Webhooks::EVENT_*`, полный список — `Webhooks::eventValues()`.

См. [Вебхуки](https://www.amocrm.ru/developers/content/crm_platform/webhooks-api#webhooks-list).
