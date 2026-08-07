# checkbox

[← Поля и группы полей](../custom-fields.md)

```php
$lead->cf('Согласие')->setValue(true);
$lead->cf('Согласие')->setValue(false);
var_dump((bool) $lead->cf('Согласие')->getValue());
$lead->save();
```
