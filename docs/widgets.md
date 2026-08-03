# Виджеты

[← README](../README.md)

```php
$paginate = $api->widgets()->paginate();
$paginate->maxRows(100)->maxPages(10);

// пагинация на основе next link
foreach($paginate as $page_num=>$widgets) {
    echo "\nPage ".$page_num." loaded ".$widgets->count()."\n\n";
    print_r($widgets); // collection
}
// принудительная пагинация на основании наличия данных
while (
    $paginate->valid() && ($widgets = $paginate->fetchPage()) && $widgets->count()
) {
    echo "\nPage ".$paginate->page." loaded ".$widgets->count()."\n\n";
    $paginate->setPageNum($paginate->page+1);
}

// получение всех страниц в виде одной коллекции
$widgets = $api->widgets()->get();
$widgets = $api->widgets()->paginate()->fetchAll($max_pages);

$widget = $widgets->where('id', 972)->first();
// или получение отдельным запросом по коду
$widget = $api->widgets()->find('amo_asterisk');

// установка виджета, возвращает модель
$installed = $api->widgets()->install('amo_asterisk', $settings);
// или через модель
$installed = $widget->install([
    'login' => 'example',
    'password' => 'eXaMp1E',
    'phones' => [
        1234 => '8927047',
        5678 => '8906000',
    ],
    'script_path'=> 'https://site.ru/'
]);

// удаление установки виджета
$bool = $api->widgets()->uninstall('amo_asterisk');
// или через модель
$bool = $widget->uninstall();
```
