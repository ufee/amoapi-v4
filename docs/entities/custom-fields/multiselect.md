# multiselect

[← Поля и группы полей](../custom-fields.md)

Класс `MultiSelectField`. `setValue` / `setValues` / `setEnum` / `setEnums` полностью заменяют выбранные опции.  
Чтобы не затирать уже выбранное — `addValue` / `addValues` / `addEnum` / `addEnums` (дубликаты пропускаются). Снять опцию: `removeValue` / `removeValues` / `removeEnum` / `removeEnums`.

```php
$cf = $lead->cf('Варианты оплаты');

// заменить все
$cf->setValues(['Онлайн', 'СБП']);
$cf->setEnums([845234, 945431]);

// добавить к уже выбранным
$cf->addValue('Карта');
$cf->addValues(['Рассрочка', 'СБП']); // СБП уже есть — не дублируется
$cf->addEnum(845234);
$cf->addEnums([845234, 945431]);

// снять опции
$cf->removeValue('Онлайн');
$cf->removeEnum(945431);

print_r($cf->getValues());
print_r($cf->getEnums());
$cf->reset();
$lead->save();
```
