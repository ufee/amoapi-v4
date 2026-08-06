# Покупатели

[← README](../../README.md) · [Сущности](../entities.md)

```php
$customers = $api->customers()->get();
$customers = $api->customers;
$customers = $api->customers()->withAll()->get();
$customers = $api->customers()->with([
    \Ufee\AmoV4\Services\Customers::CATALOG_ELEMENTS,
    \Ufee\AmoV4\Services\Customers::CONTACTS,
    \Ufee\AmoV4\Services\Customers::COMPANIES,
])->get();
$customers = $api->customers()->searchByName('Иван', 1);

$customer = $api->customers()->find($customer_id);
$customer = $api->customers()->create(['name' => 'Иван']);

$customer->cf('Город')->setValue('Москва');
$customer->setSegments([$segment_id]);
$customer->save();

$user = $customer->responsibleUser();

$customer->attachTag('VIP');
$customer->attachContact($contact_id);
$customer->attachCompany($company_id);

// главный контакт
$main = $customer->getMainContact();

// элементы списков
$customer->attachCatalogElement($element);
$elements = $customer->catalogElements($catalog_id);

$paginate = $customer->getTasks($filter = []);
$task = $customer->createTask($type = 1);
$note = $customer->createNote($type = 'common');

// заметки: sugar $api->customers()->notes() отсутствует — используйте сервис notes
$notes = $api->notes('customers', $customer_id)->get();

// подписчики: только через сервис (у модели Customer нет getSubscriptions)
$subscriptions = $api->subscriptions('customers', $customer_id)->get();
```

См. также: [сегменты](customer-segments.md), [элементы списков](catalog-elements.md#привязка-к-сделкам-и-покупателям), [подписчики](subscriptions.md), [заметки](notes.md).
