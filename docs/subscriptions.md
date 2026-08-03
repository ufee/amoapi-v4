# Подписчики сущности (Subscriptions API)

[← README](../README.md)

```php
// список подписчиков сделки
$subscriptions = $api->subscriptions('leads', $lead_id)->get();

foreach ($subscriptions as $subscription) {
    echo $subscription->subscriber_id . ' (' . $subscription->type . ')' . PHP_EOL;
}

// список подписчиков покупателя
$customer_subscriptions = $api->subscriptions('customers', $customer_id)->get();

// получение через модель сделки
$lead = $api->leads()->find($lead_id);
$subscriptions = $lead->getSubscriptions();
```
