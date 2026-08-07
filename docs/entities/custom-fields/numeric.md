# numeric / price / monetary

[← Поля и группы полей](../custom-fields.md)

Формат API — строка/число в `value`. Типы `price` и `monetary` мапятся на `NumericField`.

```php
$lead->cf('Площадь')->setValue(42.5);
$lead->cf('Бюджет')->setValue(150000);   // type = price (каталоги)
$lead->cf('Сумма')->setValue('100.50');  // type = monetary

echo $lead->cf('Площадь')->getValue();
$lead->save();
```
