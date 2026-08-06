# Элементы списков

[← README](../../README.md) · [Сущности](../entities.md)

```php
$elements = $api->catalogElements($catalog_id)->get();
$elements = $api->catalogElements($catalog_id)->withAll()->get();
// или через модель списка
$elements = $catalog->elements()->get();
$elements = $catalog->elements()->withAll()->get();

$paginate = $catalog->elements()->paginate();
$paginate = $catalog->elements()->filter(['id' => [525439, 525440]]);
$elements = $catalog->elements()->searchByName('Телефон', 1);

$element = $catalog->elements()->find($element_id);
// или сразу по паре id
$element = $api->catalogElement($catalog_id, $element_id);
// элемент списка счетов со ссылкой на печатную форму
$element = $api->catalogElement($catalog_id, $element_id, ['invoice_link']);
$element = $api->catalogElement($catalog_id, $element_id, \Ufee\AmoV4\Services\CatalogElements::withValues());

// создание элемента
$element = $catalog->createElement(['name' => 'Телефон']);
$element->cf('Артикул')->setValue('34N4124');
$element->save();

// поля списка
$cfields = $catalog->customFields()->get();
// или из кеша
$cfields = $api->cache->customFields('catalogs', $catalog_id);
```

## Привязка к сделкам и покупателям

Доступно в моделях сделок и покупателей через трейт `LinkedCatalogElements`.
`catalog_id` определяется автоматически, если передана модель элемента.

```php
$lead = $api->leads()->find($lead_id);

// привязка элемента
$link = $lead->attachCatalogElement($element);
$link = $lead->attachCatalogElement($element_id, $catalog_id, $quantity = 3);

// привязка нескольких элементов одного списка
$links = $lead->attachCatalogElements($elements);
$links = $lead->attachCatalogElements([525439, 525440], $catalog_id);

// отвязка
$lead->detachCatalogElement($element);
$lead->detachCatalogElements([525439, 525440], $catalog_id);

// привязанные элементы, коллекция или false
$elements = $lead->catalogElements();
$elements = $lead->catalogElements($catalog_id); // только из одного списка
```

См. также: [списки](catalogs.md), [сделки](leads.md), [покупатели](customers.md).
