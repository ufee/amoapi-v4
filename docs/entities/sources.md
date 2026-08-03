# Источники (Sources)

[← README](../../README.md) · [Сущности](../entities.md)

```php
// список источников интеграции
$sources = $api->sources()->get();
$sources = $api->cache->sources(); // из кеша

// получение по id
$source = $api->sources()->find($source_id);
$source = $api->cache->source($source_id); // из кеша

// фильтр по внешнему коду
$filtered = $api->sources()->filter([
    'external_id' => '65bd500b-fd52-4599-ab58-943ce3dd058c'
])->fetchAll();

// создание источника
$source = $api->sources()->create([
    'name' => 'Номер отдела продаж',
    'pipeline_id' => 1300,
    'external_id' => '+17 912 100 00 00',
    'default' => true,
    'services' => [
        [
            'type' => 'whatsapp',
            'params' => [
                'waba' => true,
                'is_supports_list_message' => true
            ],
            'pages' => [
                [
                    'id' => '9121234565',
                    'name' => 'WhatsApp +9121234567',
                    'link' => '+9121234567'
                ]
            ]
        ]
    ]
]);
$source->save();

// обновление источника
$source->name = 'Новый отдел продаж';
$source->save();

// удаление источника
$is_deleted = $api->sources()->remove($source_id); // один
$is_deleted_batch = $api->sources()->remove([123, 456]); // пакетно
// или через модель
$is_deleted = $source->delete();
```
