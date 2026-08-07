# multiselect

[← Поля и группы полей](../custom-fields.md)

```php
$lead->cf('Варианты оплаты')->setValues(['Онлайн', 'СБП']);
$lead->cf('Варианты оплаты')->setEnums([845234, 945431]);

print_r($lead->cf('Варианты оплаты')->getValues());
print_r($lead->cf('Варианты оплаты')->getEnums());
$lead->cf('Варианты оплаты')->reset();
$lead->save();
```
