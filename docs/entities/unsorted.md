# Неразобранное

[← README](../../README.md) · [Сущности](../entities.md)

```php
// список неразобранного
$items = $api->unsorted()->get();

// фильтр по UID / категории / воронке
$items = $api->unsorted()->filter([
    'uid' => '98fb033cefde74f5de1a5d3980a2a2d108037',
    // 'uid' => ['uid1', 'uid2'],
    // 'category' => ['sip', 'forms', 'chats', 'mail'],
    // 'pipeline_id' => 2194576,
])->fetchAll();

// сортировка
$items = $api->unsorted()->orderBy('created_at', 'desc')->get();

// получение по UID
$item = $api->unsorted()->find($uid);

// добавление неразобранного типа звонок (sip)
$created = $api->unsorted()->addSip([
    'request_id' => '123',
    'source_name' => 'ОАО Коспромсервис',
    'source_uid' => 'a1fee7c0fc436088e64ba2e8822ba2b3',
    'pipeline_id' => 2194576,
    'created_at' => time(),
    'metadata' => [
        'is_call_event_needed' => true,
        'uniq' => 'a1fe231cc88e64ba2e8822ba2b3ewrw',
        'duration' => 54,
        'service_code' => 'CkAvbEwPam6sad',
        'link' => 'https://example.com',
        'phone' => 79998888888,
        'called_at' => time(),
        'from' => 'onlinePBX',
    ],
    '_embedded' => [
        'leads' => [
            ['name' => 'Тех обслуживание', 'price' => 5000],
        ],
        'contacts' => [
            ['name' => 'Контакт для примера'],
        ],
    ],
]);

// пакетное добавление форм
$created_rows = $api->unsorted()->addForms([
    [
        'source_name' => 'Форма с сайта',
        'source_uid' => 'a1fee7c0fc436088e64ba2e8822ba2b3',
        'pipeline_id' => 2194576,
        'metadata' => [
            'form_id' => 'feedback',
            'form_name' => 'Обратная связь',
            'form_page' => 'https://example.com/form',
            'form_sent_at' => time(),
            'ip' => '192.0.2.0',
            'referer' => 'https://google.com',
        ],
        '_embedded' => [
            'leads' => [
                ['name' => 'Заявка с формы'],
            ],
            'contacts' => [
                [
                    'name' => 'Иван',
                    'custom_fields_values' => [
                        [
                            'field_code' => 'PHONE',
                            'values' => [['value' => '+7912321323']],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);

// принятие
$accepted = $api->unsorted()->accept($uid, [
    'user_id' => 123123,
    'status_id' => 30846280,
]);
// или через модель
$accepted = $item->accept(['status_id' => 30846280]);

// отклонение
$declined = $api->unsorted()->decline($uid, ['user_id' => 123123]);
// или через модель
$declined = $item->decline();

// привязка (только категория chats)
$linked = $api->unsorted()->link($uid, [
    'entity_id' => 93144801,
    'entity_type' => 'leads', // leads|customers
]);
// или через модель
$linked = $item->link([
    'entity_id' => 93144801,
    'entity_type' => 'customers',
], $user_id = 123123);

// сводная информация
$summary = $api->unsorted()->summary([
    'pipeline_id' => 2194576,
    'created_at' => [
        'from' => 1589176500,
        'to' => 1589176560,
    ],
]);
// $summary->total, $summary->accepted, $summary->declined, $summary->average_sort_time, $summary->categories
```
