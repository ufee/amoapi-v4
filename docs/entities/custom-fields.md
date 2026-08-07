# Поля и группы полей

[← README](../../README.md) · [Сущности](../entities.md)

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

Поле можно взять по названию, id, коду или типу. После изменений нужен `$entity->save()`.

```php
$lead = $api->leads()->find($lead_id);

$cf = $lead->cf('Город');           // по названию
$cf = $lead->cf(3745829);           // по id
$cf = $lead->cf()->byName('Город');
$cf = $lead->cf()->byId(3745829);
$cf = $lead->cf()->byCode('PHONE');
$cf = $lead->cf()->byType('radiobutton');

// метаданные поля аккаунта
$field = $cf->field;
$enum_values = $field->getEnums();
$enum_ids = $field->getEnumIds();
$bool = $field->hasEnum(568345);
$bool = $field->hasValue('Чебоксары');

foreach ($lead->cf()->all() as $cf) {
    print_r($cf->getValue());
}
```

Типы ниже соответствуют классам SDK (`EntityCustomFields::FIELD_CLASSES`). Неизвестный тип обрабатывается базовым `EntityField`.

## Доступные типы полей

| Тип | Название | Документация |
| --- | --- | --- |
| `text` | Текст | [text](custom-fields/text.md) |
| `numeric` | Число | [numeric](custom-fields/numeric.md) |
| `checkbox` | Флаг | [checkbox](custom-fields/checkbox.md) |
| `select` | Список | [select](custom-fields/select.md) |
| `multiselect` | Мультисписок | [multiselect](custom-fields/multiselect.md) |
| `date` | Дата | [date](custom-fields/date.md) |
| `url` | Ссылка | [url](custom-fields/url.md) |
| `multitext` | Мультитекст (`PHONE` / `EMAIL`) | [multitext](custom-fields/multitext.md) |
| `textarea` | Текстовая область | [text](custom-fields/text.md) |
| `radiobutton` | Переключатель | [select](custom-fields/select.md) |
| `streetaddress` | Короткий адрес | [streetaddress](custom-fields/streetaddress.md) |
| `smart_address` | Адрес | [smart_address](custom-fields/smart-address.md) |
| `birthday` | День рождения | [date](custom-fields/date.md) |
| `legal_entity` | Юр. лицо | [legal_entity](custom-fields/legal-entity.md) |
| `items` | Предметы (только в списке Счета-покупки) | — (`EntityField`) |
| `category` | Категория | [select](custom-fields/select.md) |
| `date_time` | Дата и время | [date_time](custom-fields/date-time.md) |
| `price` | Цена | [numeric](custom-fields/numeric.md) |
| `tracking_data` | Отслеживаемые данные | [text](custom-fields/text.md) |
| `linked_entity` | Связь с другим элементом | — (`EntityField`) |
| `monetary` | Денежное (платная опция Супер-поля) | [numeric](custom-fields/numeric.md) |
| `chained_list` | Каталоги и списки (платная опция Супер-поля) | — (`EntityField`) |
| `file` | Файл | [file](custom-fields/file.md) |
| `payer` | Плательщик (только в списке Счета-покупки) | — (`EntityField`) |
| `supplier` | Поставщик (только в списке Счета-покупки) | — (`EntityField`) |

Во всех примерах после установки значения вызывайте `save()` у сущности.
