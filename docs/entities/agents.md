# Агенты Аммы

[← README](../../README.md) · [Сущности](../entities.md)

Работа с [API агентов Аммы](https://www.amocrm.ru/developers/content/amma/amma-agents-api). У интеграции должен быть scope «Амма». Если Амма недоступна в аккаунте, методы вернут ошибку `402`.

Лимиты API: не более 2 агентов от одной интеграции и не более 10 агентов в аккаунте суммарно. Выключенные агенты учитываются в лимитах. Массовое обновление и удаление не поддерживаются — агенты изменяются и удаляются по UUID.

```php
$agents = $api->agents();
```

## Список агентов

Метод возвращает агентов текущей интеграции. В списке нет `system_prompt`, `ai_instructions` и `created_by` — их можно получить через `find()`.

```php
$agents = $api->agents()->get();
$agents = $api->agents()->maxPageRows(50)->paginate();

foreach ($agents as $agent) {
    echo $agent->id . ' ' . $agent->name . PHP_EOL;
    echo $agent->mcp->url . PHP_EOL;
    echo $agent->mcp->has_headers ? 'headers set' : 'no headers';
}
```

`limit` — от 1 до 50 (по умолчанию 50). Фильтрация и поиск API не поддерживает.

## Получение агента по UUID

```php
$agent = $api->agents()->find('b1f2c3d4-0000-4a5b-8c9d-000000000001');
echo $agent->system_prompt;
echo $agent->ai_instructions;
echo $agent->created_by;
```

## Создание агента

Обязательные поля: `name`, `description`, `system_prompt`, `mcp.url`. Адрес MCP только с схемой `https`.

```php
$agent = $api->agents()->create([
    'name' => 'Помощник по записям',
    'description' => 'Проверяет записи клиентов и подсказывает свободные слоты',
    'ai_instructions' => 'Передавай этому агенту вопросы о записи клиента, расписании и свободных слотах',
    'system_prompt' => 'Ты — ассистент по онлайн-записи. Помогаешь проверить запись клиента и найти свободное время.',
    'avatar' => 'https://cdn.partner.com/agents/booking.png',
    'model_size' => \Ufee\AmoV4\Services\Agents::MODEL_SIZE_M, // S|M|L, по умолчанию M
    'mcp' => [
        'url' => 'https://mcp.partner.com/booking',
        'transport' => \Ufee\AmoV4\Services\Agents::TRANSPORT_STREAMABLE_HTTP, // streamable-http|sse
        'headers' => [
            'X-Partner-Key' => 'ваш ключ',
        ],
    ],
    'is_active' => true,
]);
$agent->save();

echo $agent->id; // UUID
echo $agent->avatar; // ссылка на файл в amoCRM, не исходный URL
```

Или через хелпер MCP:

```php
$agent = $api->agents()->create([
    'name' => 'Помощник по записям',
    'description' => 'Проверяет записи клиентов',
    'system_prompt' => 'Ты — ассистент по онлайн-записи.',
]);
$agent->setMcp(
    'https://mcp.partner.com/booking',
    \Ufee\AmoV4\Services\Agents::TRANSPORT_STREAMABLE_HTTP,
    ['X-Partner-Key' => 'ваш ключ']
);
$agent->save();
```

Пакетное создание — через коллекцию (не более 2 агентов от интеграции):

```php
$agents = $api->agents()->createCollection([
    [
        'name' => 'Агент 1',
        'description' => 'Описание',
        'system_prompt' => 'Промпт',
        'mcp' => ['url' => 'https://mcp.partner.com/one'],
    ],
    [
        'name' => 'Агент 2',
        'description' => 'Описание',
        'system_prompt' => 'Промпт',
        'mcp' => ['url' => 'https://mcp.partner.com/two'],
    ],
]);
$agents->save();
```

Если хотя бы один агент не прошёл валидацию, ни один не будет создан.

## Редактирование агента

Передаются только изменённые поля. `mcp` заменяется целиком: если передать `mcp` без `headers`, ранее заданные заголовки будут удалены. Значения заголовков API не возвращает, только `mcp.has_headers`.

```php
$agent = $api->agents()->find($agent_id);
$agent->model_size = \Ufee\AmoV4\Services\Agents::MODEL_SIZE_L;
$agent->system_prompt = 'Обновлённый системный промпт агента.';
$agent->is_active = false;
$agent->save();

// очистить инструкции и аватар — пустая строка
$agent->ai_instructions = '';
$agent->avatar = '';
$agent->save();
```

Новые параметры вступят в силу при следующем подключении агента.

## Удаление агента

Удалить агента может только интеграция, которая его создала.

```php
$is_deleted = $api->agents()->remove($agent_id);
// или через модель
$is_deleted = $agent->delete();
```
