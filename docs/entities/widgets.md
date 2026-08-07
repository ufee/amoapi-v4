# Виджеты

[← README](../../README.md) · [Сущности](../entities.md)

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

// подтверждение блока виджета в Salesbot / Marketingbot
// из webhook удобнее через return_url
$is_continued = $api->widgets()->continueFromUrl(
    $hook['return_url'], // https://.../api/v4/{salesbot|marketingbot}/{bot_id}/continue/{continue_id}
    ['status' => 'success'],
    [ // execute_handlers (опционально, макс. 10)
        [
            'handler' => \Ufee\AmoV4\Services\Widgets::HANDLER_SHOW,
            'params' => [
                'type' => 'text',
                'value' => 'Готово',
            ],
        ],
    ]
);

// или вручную: разобрать URL / вызвать continueBot
[$bot_type, $bot_id, $continue_id] = \Ufee\AmoV4\Services\Widgets::parseContinueUrl($hook['return_url']);
// ['marketingbot', 321, 123]
$is_continued = $api->widgets()->continueBot($bot_type, $bot_id, $continue_id, ['status' => 'success']);
```

Константы: `Widgets::botTypeValues()`, `Widgets::handlerValues()`.
