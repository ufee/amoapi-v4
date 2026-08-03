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

## Примеры по типам полей

Во всех примерах после установки значения вызывайте `save()` у сущности.

### text

```php
$lead->cf('Комментарий')->setValue('Текст');
echo $lead->cf('Комментарий')->getValue();
$lead->cf('Комментарий')->reset();
$lead->save();
```

### numeric / price

```php
$lead->cf('Площадь')->setValue(42.5);
$lead->cf('Бюджет')->setValue(150000); // type = price
echo $lead->cf('Площадь')->getValue();
$lead->save();
```

### checkbox

```php
$lead->cf('Согласие')->setValue(true);
$lead->cf('Согласие')->setValue(false);
var_dump((bool) $lead->cf('Согласие')->getValue());
$lead->save();
```

### select / radiobutton

Можно задать значение текстом или через `enum_id`.

```php
$lead->cf('Источник')->setValue('Реклама');
$lead->cf('Источник')->setEnum(546710);

echo $lead->cf('Источник')->getValue();
echo $lead->cf('Источник')->getEnum(); // enum_id
$lead->save();
```

### multiselect

```php
$lead->cf('Варианты оплаты')->setValues(['Онлайн', 'СБП']);
$lead->cf('Варианты оплаты')->setEnums([845234, 945431]);

print_r($lead->cf('Варианты оплаты')->getValues());
print_r($lead->cf('Варианты оплаты')->getEnums());
$lead->cf('Варианты оплаты')->reset();
$lead->save();
```

### date / birthday

Значение — unix timestamp. Есть хелперы `getDateTime()` и `format()`.

```php
$contact->cf('Дата договора')->setValue(strtotime('2024-01-15'));
$contact->cf('День рождения')->setValue(strtotime('1990-05-01')); // type = birthday

$dt = $contact->cf('Дата договора')->getDateTime(); // DateTime в timezone клиента
echo $contact->cf('Дата договора')->format('Y-m-d');
$contact->save();
```

### date_time

```php
$lead->cf('Встреча')->setValue(strtotime('2024-06-01 15:30:00'));
echo $lead->cf('Встреча')->format(); // Y-m-d H:i:s по умолчанию
echo $lead->cf('Встреча')->format('d.m.Y H:i');
$lead->save();
```

### url

```php
$lead->cf('Сайт')->setValue('https://example.com');
echo $lead->cf('Сайт')->getValue();
$lead->save();
```

### streetaddress

```php
$contact->cf('Адрес')->setValue('Москва, Тверская 1');
echo $contact->cf('Адрес')->getValue();
$contact->save();
```

### smart_address

Составной адрес: несколько значений (как в API amoCRM).

```php
$contact->cf('Адрес (smart)')->setValues([
    (object) ['value' => 'RU', 'enum_code' => 'country'],
    (object) ['value' => 'Москва', 'enum_code' => 'city'],
    (object) ['value' => 'Тверская', 'enum_code' => 'street'],
    (object) ['value' => '1', 'enum_code' => 'house'],
]);
print_r($contact->cf('Адрес (smart)')->getValues());
$contact->save();
```

### legal_entity

Юр. лицо — объект в `value`.

```php
$company->cf('Реквизиты')->setValue((object) [
    'name' => 'ООО Ромашка',
    'entity_type' => 'OOO',
    'vat_id' => '7701234567',
    'tax_registration_reason_code' => '770101001',
    'address' => 'г. Москва, ул. Примерная, д. 1',
    'kpp' => '770101001',
]);
print_r($company->cf('Реквизиты')->getValue());
$company->save();
```

### file

Связь кастомного поля с файлом из [Drive API](files.md). Класс `FileField`.

```php
// 1) загрузить файл в Drive
$file = $api->files()->upload('/path/to/contract.pdf');

// 2) записать в поле типа file
$lead->cf('Договор')->setFile($file);
// или
$lead->cf('Договор')->setFile([
    'file_uuid' => $file->uuid,
    'file_name' => $file->name,
    'file_size' => $file->size,
]);
$lead->save();

// чтение
$cf = $lead->cf('Договор');
if ($cf->hasFile()) {
    echo $cf->getUuid();
    echo $cf->getFileName();
    echo $cf->getFileSize();
    $driveFile = $cf->getFile(); // модель File (запрос в Drive)
    echo $driveFile->getDownloadUrl();
}

// очистка поля (API требует values с null, пустой массив не принимается)
$lead->cf('Договор')->reset();
$lead->save();
```

Это **не** то же самое, что прикрепление файла к сущности (`$lead->attachFiles()`). См. [Файлы](files.md).

### multitext (PHONE / EMAIL и др.)

Отдельного класса нет — используется базовый `EntityField`. Обычно поля контакта/компании с кодами `PHONE`, `EMAIL`.

```php
$contact->cf()->byCode('EMAIL')->setValue('user@example.com');
$contact->cf()->byCode('PHONE')->setValue('+79001234567');
// или по названию
$contact->cf('Email')->setValue('user@example.com');
$contact->save();

echo $contact->cf()->byCode('EMAIL')->getValue();
```
