# multitext (PHONE / EMAIL и др.)

[← Поля и группы полей](../custom-fields.md)

Класс `MultiTextField`. Обычно поля контакта с кодами `PHONE`, `EMAIL`. В каждом значении можно указать `enum_id` или `enum_code`.

Для `PHONE` и `EMAIL`, если enum не передан, по умолчанию ставится `WORK`.  
`enum_code` проверяется по `PhoneEnum::values()` / `EmailEnum::values()`; неизвестный код — `InvalidArgumentException`. `enum_id` не валидируется.

Константы типов: `PhoneEnum` (`WORK`, `WORKDD`, `MOB`, `FAX`, `HOME`, `OTHER`) и `EmailEnum` (`WORK`, `PRIV`, `OTHER`).

```php
use Ufee\AmoV4\Enums\CustomFields\EmailEnum;
use Ufee\AmoV4\Enums\CustomFields\PhoneEnum;

// без enum → WORK
$contact->cf()->byCode(PhoneEnum::CODE)->setValue('+79001234567');
$contact->cf()->byCode(EmailEnum::CODE)->setValue('user@example.com');

// значение + тип (enum_code или enum_id)
$contact->cf('Телефон')->setValue('+79001234567', PhoneEnum::MOB);
$contact->cf()->byCode(PhoneEnum::CODE)->setValue('+79001234567', 48224);
$contact->cf()->byCode(EmailEnum::CODE)->setValue('user@example.com', EmailEnum::WORK);

// несколько значений
$contact->cf()->byCode(PhoneEnum::CODE)->setValues([
    ['value' => '+79121234567', 'enum_code' => PhoneEnum::MOB],
    ['value' => '+74991234567', 'enum_code' => PhoneEnum::WORK],
]);

// добавить без затирания остальных
$contact->cf()->byCode(PhoneEnum::CODE)->addValue('+74951234567', PhoneEnum::HOME);

echo $contact->cf()->byCode(PhoneEnum::CODE)->getValue();
echo $contact->cf()->byCode(PhoneEnum::CODE)->getEnumCode(); // MOB
print_r($contact->cf()->byCode(PhoneEnum::CODE)->getValues());
print_r($contact->cf()->byCode(PhoneEnum::CODE)->getEnums());
print_r($contact->cf()->byCode(PhoneEnum::CODE)->getEnumCodes());

$contact->cf()->byCode(PhoneEnum::CODE)->reset();
$contact->cf()->byCode(EmailEnum::CODE)->reset();
$contact->save();
```
