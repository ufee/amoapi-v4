# date_time

[← Поля и группы полей](../custom-fields.md)

```php
$lead->cf('Встреча')->setValue(strtotime('2024-06-01 15:30:00'));
echo $lead->cf('Встреча')->format(); // Y-m-d H:i:s по умолчанию
echo $lead->cf('Встреча')->format('d.m.Y H:i');
$lead->save();
```
