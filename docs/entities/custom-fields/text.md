# text / textarea / tracking_data

[← Поля и группы полей](../custom-fields.md)

Формат API — одна строка в `value`. Типы `textarea` и `tracking_data` мапятся на `TextField`.

```php
$lead->cf('Комментарий')->setValue('Текст');
$lead->cf('Описание')->setValue("Строка1\nСтрока2"); // type = textarea
$lead->cf('UTM')->setValue('campaign');              // type = tracking_data

echo $lead->cf('Комментарий')->getValue();
$lead->cf('Комментарий')->reset();
$lead->save();
```
