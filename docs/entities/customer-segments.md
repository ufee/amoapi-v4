# Сегменты покупателей

[← README](../../README.md) · [Сущности](../entities.md)

```php
$segments = $api->customerSegments()->get();
// или через сервис покупателей
$segments = $api->customers()->segments()->get();

$segment = $api->customerSegments()->find($segment_id);
$segment = $api->customerSegments()->create(['name' => 'VIP']);
$segment->save();

// кастомные поля сегментов
$cfields = $api->customerSegments()->customFields()->get();

// назначение сегментов покупателю
$customer = $api->customers()->find($customer_id);
$customer->setSegments([$segment_id]);
$customer->save();
```

См. также: [покупатели](customers.md).
