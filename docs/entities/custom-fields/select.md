# select / radiobutton / category

[← Поля и группы полей](../custom-fields.md)

Можно задать значение текстом, через `enum_id` или `enum_code`.  
`category` (каталоги) мапится на `SelectField`.

```php
$lead->cf('Источник')->setValue('Реклама');
$lead->cf('Источник')->setEnum(546710);

echo $lead->cf('Источник')->getValue();
echo $lead->cf('Источник')->getEnum(); // enum_id

// category (элемент каталога)
$element->cf('Категория')->setValue('Корневая');
$element->cf('Категория')->setEnum(17);

$lead->save();
```
