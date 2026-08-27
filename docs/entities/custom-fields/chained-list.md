# chained_list

[← Поля и группы полей](../custom-fields.md)

Каталоги и списки (платная опция Супер-поля). Класс `ChainedListField`.  
Значение — пара `catalog_id` + `catalog_element_id` **без** ключа `value`. Поле принимает до 5 элементов.

Настройки связанных списков — в метаданных поля аккаунта: `$cf->field->chained_lists`.

```php
$field = $api->customFields('leads')->get()->find('type', 'chained_list')->first();
$catalogId = (int) $field->chained_lists[0]->catalog_id;
$element = $api->catalogElements($catalogId)->find($element_id);

$cf = $lead->cf((int) $field->id);

// один элемент
$cf->setValue([
    'catalog_id' => $catalogId,
    'catalog_element_id' => (int) $element->id,
]);
// или модель элемента списка
$cf->setValue($element);

// несколько (разные списки цепочки)
$cf->setValues([
    ['catalog_id' => 1001, 'catalog_element_id' => 12235],
    ['catalog_id' => 1007, 'catalog_element_id' => 12243],
]);
// или модели элементов списка
$mark = $api->catalogElements(1001)->find(12235);
$model = $api->catalogElements(1007)->find(12243);
$trim = $api->catalogElements(1013)->find(12251);
$cf->setValues([$mark, $model, $trim]);
// добавить к уже выбранным, не затирая список
$cf->addValue($element);
$lead->save();

// чтение
print_r($cf->getValue());   // object {catalog_id, catalog_element_id}
print_r($cf->getValues());
print_r($cf->toArray());

$cf->reset();
$lead->save();
```
