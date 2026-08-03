# Причины отказа

[← README](../../README.md) · [Сущности](../entities.md)

```php
// через sugar-метод сервиса сделок
$lossReasons = $api->leads()->lossReasons()->get();
$lossReason = $api->leads()->lossReasons()->find($loss_reason_id);

// или напрямую через отдельный сервис
$lossReasons = $api->lossReasons()->get();
$lossReason = $api->lossReasons()->find($loss_reason_id);

// или из кеша
$lossReasons = $api->cache->lossReasons();
$lossReason = $api->cache->lossReason($loss_reason_id);

// очистка кеша
$api->cache->clear('lossReasons');
```

См. также: [сделки](leads.md).
