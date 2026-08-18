# legal_entity

[← Поля и группы полей](../custom-fields.md)

Юр. лицо — объект в `value`. Класс `LegalEntityField`. Обязательное поле — `name` (сначала `setName()`, затем остальные сеттеры).  
`entity_type`: `1` — частное, `2` — юридическое (`LegalEntityTypeEnum`).  
Поле может хранить несколько юрлиц: геттеры и fluent-сеттеры работают с первым, остальные — через `setValues()` / `addValue()` / `getValues()` / `toArray()`.

Доступные ключи: `name`, `entity_type`, `vat_id`, `tax_registration_reason_code`, `kpp`, `address`, `real_address`, `bank_code`, `bank_account_number`, `director`, `external_uid`, а также `unp` (BY), `bin` (KZ), `egrpou` / `mfo` (UA/UZ), `oked` (UZ).

Схема записи зависит от страны аккаунта: GET часто возвращает пустые ключи других стран, PATCH их отклоняет (`FieldNotExpected`). SDK при сохранении отправляет только заполненные ключи.

```php
use Ufee\AmoV4\Enums\CustomFields\LegalEntityTypeEnum;

$legal = $company->cf('Реквизиты');

// целиком через setValue
$legal->setValue([
    'name' => 'ООО Ромашка',
    'entity_type' => LegalEntityTypeEnum::LEGAL,
    'vat_id' => '7701234567',
    'tax_registration_reason_code' => '1027700132195',
    'address' => 'г. Москва, ул. Примерная, д. 1',
    'real_address' => 'г. Москва, ул. Фактическая, д. 2',
    'kpp' => '770101001',
    'bank_code' => '044525225',
    'bank_account_number' => '40702810900000000001',
    'director' => 'Иванов И.И.',
    'external_uid' => 'ext-1',
]);

// fluent (сначала name)
$legal->setName('ООО Ромашка')
    ->setEntityType(LegalEntityTypeEnum::LEGAL)
    ->setVatId('7701234567')
    ->setKpp('770101001')
    ->setAddress('г. Москва, ул. Примерная, д. 1')
    ->setRealAddress('г. Москва, ул. Фактическая, д. 2')
    ->setBankCode('044525225')
    ->setBankAccountNumber('40702810900000000001')
    ->setDirector('Иванов И.И.')
    ->setExternalUid('ext-1');

// частное лицо / ИП
$legal->setName('ИП Петров П.П.')
    ->setEntityType(LegalEntityTypeEnum::INDIVIDUAL)
    ->setVatId('770123456789')
    ->setAddress('г. Казань, ул. Баумана, д. 10');

// Беларусь (УНП)
$legal->setName('ООО БелТорг')
    ->setEntityType(LegalEntityTypeEnum::LEGAL)
    ->setUnp('190123456')
    ->setAddress('г. Минск, пр. Независимости, д. 1');

// Казахстан (БИН)
$legal->setName('ТОО АстанаТрейд')
    ->setEntityType(LegalEntityTypeEnum::LEGAL)
    ->setBin('123456789012')
    ->setAddress('г. Алматы, ул. Абая, д. 5');

// Украина (ЕГРПОУ / МФО)
$legal->setName('ТОВ КиївПром')
    ->setEntityType(LegalEntityTypeEnum::LEGAL)
    ->setEgrpou('12345678')
    ->setMfo('305299')
    ->setBankAccountNumber('UA123456789012345678901234567');

// Узбекистан (ОКЭД, МФО)
$legal->setName('ООО ТашкентСервис')
    ->setEntityType(LegalEntityTypeEnum::LEGAL)
    ->setOked('62010')
    ->setMfo('00444')
    ->setAddress('г. Ташкент, ул. Навои, д. 3');

// чтение
echo $legal->getName();
echo $legal->getVatId();
echo $legal->getEntityType(); // 1 или 2
echo $legal->getKpp();
echo $legal->getDirector();
echo $legal->getUnp();
print_r($legal->toArray());
print_r($legal->getValue());

// несколько юрлиц
$legal->setValues([
    [
        'name' => 'ООО Ромашка',
        'entity_type' => LegalEntityTypeEnum::LEGAL,
        'vat_id' => '7701234567',
    ],
    [
        'name' => 'ИП Петров П.П.',
        'entity_type' => LegalEntityTypeEnum::INDIVIDUAL,
        'vat_id' => '770123456789',
    ],
]);
$legal->addValue([
    'name' => 'ООО Лютик',
    'entity_type' => LegalEntityTypeEnum::LEGAL,
    'vat_id' => '7707654321',
]);
print_r($legal->getValues());
print_r($legal->toArray());

// частичное обновление: fluent всегда пишет в первое юрлицо (индекс 0)
$legal->setDirector('Сидоров С.С.')
    ->setBankAccountNumber('40702810900000000002');

// второе и далее — через setValues()
$items = $legal->toArray();
$items[1]['director'] = 'Петров П.П.';
$legal->setValues($items);

// сброс
$legal->reset();
$company->save();
```
