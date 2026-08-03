# Задачи

[← README](../../README.md) · [Сущности](../entities.md)

```php
$tasks = $api->tasks()->get();
$paginate = $api->tasks()->filter([
    'entity_id' => $lead_id,
    'entity_type' => 'leads',
])->paginate();

$task = $api->tasks()->create([
    'text' => 'Звонок',
    'complete_till' => time() + 3600,
    'entity_id' => $lead_id,
    'entity_type' => 'leads',
    'task_type_id' => 1,
]);
$task->save();

// завершение задачи
$task->setCompleted(true, 'Дозвонился');
$task->save();

// типы задач из кеша
$taskTypes = $api->cache->taskTypes();
$type = $task->type();
```

Создание через модель сущности:

```php
$task = $lead->createTask($type = 1);
$task = $contact->createTask(1);
$tasks = $lead->getTasks($filter = [])->fetchAll();
$task = $lead->findTask($task_id);
```

См. также: [сделки](leads.md), [контакты](contacts.md), [компании](companies.md), [покупатели](customers.md).
