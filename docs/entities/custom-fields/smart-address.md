# smart_address

[← Поля и группы полей](../custom-fields.md)

Составной адрес. Компоненты: `address_line_1`, `address_line_2`, `city`, `state`, `zip`, `country`
(см. [`SmartAddressEnum`](../../../src/Enums/CustomFields/SmartAddressEnum.php)).

```php
use Ufee\AmoV4\Enums\CustomFields\SmartAddressEnum;

$addr = $contact->cf('Адрес');

// удобные сеттеры (upsert по компоненту)
$addr->setAddressLine1('Николоямская улица 28/60')
    ->setCity('Москва')
    ->setState('Москва')
    ->setZip('109004')
    ->setCountry('RU');

echo $addr->getCity();       // Москва
print_r($addr->toArray());   // [address_line_1 => …, city => …, …]

// карта компонентов
$addr->setValues([
    SmartAddressEnum::ADDRESS_LINE_1 => 'Тверская 1',
    SmartAddressEnum::CITY => 'Москва',
    SmartAddressEnum::COUNTRY => 'RU',
]);

// или формат API
$addr->setValues([
    ['value' => 'Москва', 'enum_code' => SmartAddressEnum::CITY],
    (object) ['value' => '109004', 'enum_id' => 5],
]);

// setValue с enum (в т.ч. пустая строка)
$addr->setValue('СПб', SmartAddressEnum::CITY);
$addr->setValue('', SmartAddressEnum::ZIP);

// сброс всего поля
$addr->reset();

$contact->save();
```
