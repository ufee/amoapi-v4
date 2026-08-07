# streetaddress

[← Поля и группы полей](../custom-fields.md)

Короткий адрес — однострочное текстовое значение.

```php
$contact->cf('Адрес')->setValue('Москва, Тверская 1');
echo $contact->cf('Адрес')->getValue();
$contact->save();
```
