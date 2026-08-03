# Подписчики сущности

[← README](../../README.md) · [Сущности](../entities.md)

Сервис только для чтения списка подписчиков. Доступен для `leads` и `customers`.

```php
// список подписчиков сделки
$subscriptions = $api->subscriptions('leads', $lead_id)->get();

foreach ($subscriptions as $subscription) {
    echo $subscription->subscriber_id . ' (' . $subscription->type . ')' . PHP_EOL;
}

// список подписчиков покупателя
$customer_subscriptions = $api->subscriptions('customers', $customer_id)->get();

// через модель — только у сделки (у Customer метода getSubscriptions нет)
$lead = $api->leads()->find($lead_id);
$subscriptions = $lead->getSubscriptions();
```

См. также: [сделки](leads.md), [покупатели](customers.md).
