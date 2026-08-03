# Кастомные поля

[← README](../README.md)

## Кастомные поля аккаунта

```php
$service = $api->customFields('contacts');
$service = $api->customFields('catalogs', $catalog_id);
$service->maxPageRows(10);
$service->orderBy('sort', 'desc');

// получение коллекции
$cfields = $service->get();
// или из кеша
$cfields = $api->cache->customFields('contacts');
$cfields = $api->cache->customFields('catalogs', $catalog_id);
```

### Создание поля

```php
$service = $api->customFields('contacts');
$cf = $service->create(['name' => 'Варианты оплаты']);
$cf->type = 'multiselect';
$cf->enums = [
    ['value' => 'Онлайн', 'sort' => 0],
    ['value' => 'При получении', 'sort' => 1],
    ['value' => 'СБП', 'sort' => 2]
];
$cf->save();
```

## Кастомные поля сущности

```php
$lead = $api->leads()->find($lead_id);

$cf = $lead->cf('Варианты оплаты');
$cf->reset();
$cf->setValues(['Онлайн','При получении']);
$cf->setEnums([845234,945431]);
$values = $cf->getValues();

// поле по названию
$cf = $lead->cf('Город');
// поле по id
$cf = $lead->cf(3745829);

$cf->setValue('Москва');
$cf->setEnum(546710);
$value = $cf->getValue();

$enum_id = $cf->getEnum(); // select
$enum_ids = $cf->getEnums(); // multiselect

$field = $cf->field;
$enum_values = $field->getEnums();
$enum_ids = $field->getEnumIds();
$values = $field->getValues();
$bool = $field->hasEnum(568345);
$bool = $field->hasValue('Чебоксары');

$cf = $lead->cf()->byName('Город');
$cf = $lead->cf()->byId(3745829);
$cf = $lead->cf()->byCode('PHONE');
$cf = $lead->cf()->byType('radiobutton');

$cfields = $lead->cf()->all();
foreach($cfs as $cf) {
    print_r($cf->getValue());
    echo "\n";
}
```
