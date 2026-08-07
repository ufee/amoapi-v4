# date / birthday

[← Поля и группы полей](../custom-fields.md)

Значение — unix timestamp. Есть хелперы `getDateTime()` и `format()`.

```php
$contact->cf('Дата договора')->setValue(strtotime('2024-01-15'));
$contact->cf('День рождения')->setValue(strtotime('1990-05-01')); // type = birthday

$dt = $contact->cf('Дата договора')->getDateTime(); // DateTime в timezone клиента
echo $contact->cf('Дата договора')->format('Y-m-d');
$contact->save();
```
