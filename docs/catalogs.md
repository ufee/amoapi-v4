# Списки и элементы

[← README](../README.md)

## Списки (Catalogs API)

```php
$catalogs = $api->catalogs()->get();
$catalog = $api->catalogs()->find($catalog_id);
// или из кеша
$catalogs = $api->cache->catalogs();
$catalog = $api->cache->catalog($catalog_id);

// создание списка
$catalog = $api->catalogs()->create(['name' => 'Договоры']);
$catalog->type = \Ufee\AmoV4\Services\Catalogs::TYPE_REGULAR; // regular|invoices|products
$catalog->can_add_elements = true;
$catalog->can_link_multiple = false;
$catalog->save();
// системный список Catalogs::TYPE_SUPPLIERS ('suppliers') создается аккаунтом вместе со счетами,
// он доступен только на чтение и не может быть создан через API

// обновление списка
$catalog->name = 'Новое имя списка';
$catalog->save();

// очистка кеша
$api->cache->clear('catalogs');
```

## Элементы списков

```php
$elements = $api->catalogElements($catalog_id)->get();
// или через модель списка
$elements = $catalog->elements()->get();

$paginate = $catalog->elements()->paginate();
$paginate = $catalog->elements()->filter(['id' => [525439, 525440]]);
$elements = $catalog->elements()->searchByName('Телефон', 1);

$element = $catalog->elements()->find($element_id);
// или сразу по паре id
$element = $api->catalogElement($catalog_id, $element_id);
// элемент списка счетов со ссылкой на печатную форму
$element = $api->catalogElement($catalog_id, $element_id, ['invoice_link']);

// создание элемента
$element = $catalog->createElement(['name' => 'Телефон']);
$element->cf('Артикул')->setValue('34N4124');
$element->save();

// поля списка
$cfields = $catalog->customFields()->get();
// или из кеша
$cfields = $api->cache->customFields('catalogs', $catalog_id);
```

## Элементы списков в сделках и покупателях

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
