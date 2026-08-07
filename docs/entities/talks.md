# Беседы

[← README](../../README.md) · [Сущности](../entities.md)

```php
// список бесед
$talks = $api->talks()->get();

// фильтр
$paginate = $api->talks()->filter([
    'contact_id' => $contact_id,
    'only_in_work' => true,
]);
$paginate = $api->talks()->filter([
    'entity_id' => $lead_id,
    'entity_type' => \Ufee\AmoV4\Services\Talks::ENTITY_LEAD,
]);

// получение по id
$talk = $api->talks()->find($talk_id);
$talks = $api->talks()->find([$talk_id1, $talk_id2]); // filter[talk_id]

echo $talk->talk_id;
echo $talk->chat_id;
echo $talk->status; // in_work|closed|nps_scheduled|nps_in_progress|with_error
echo $talk->origin; // telegram, viber, ...
$talk->isInWork();
$talk->isRead();

// закрытие беседы (запуск NPS-бота, если доступен)
$is_closed = $api->talks()->close($talk_id);
// или принудительно без NPS
$is_closed = $api->talks()->close($talk_id, true);
// через модель
$is_closed = $talk->close();
$is_closed = $talk->close(true);

// сообщения беседы (Kommo: scope External chat history)
$messages = $api->talks()->messages($talk_id)->get();
$paginate = $api->talks()->messages($talk_id)->filter([
    'created_at' => [
        'from' => time() - 86400,
        'to' => time(),
    ],
]);
// через модель
$messages = $talk->getMessages();
$messages = $talk->getMessages([
    'created_at' => ['from' => time() - 86400],
]);

foreach ($messages as $message) {
    echo $message->text;
    $message->isIncoming();
    $message->isOutgoing();
    $message->hasAttachment();
}
```

Константы статусов и типов сущности: `Talks::statusValues()`, `Talks::entityTypeValues()`.  
Константы сообщений: `TalkMessages::typeValues()`, `messageTypeValues()`, `authorTypeValues()`, `deliveryStatusValues()`.

См. [Get conversation messages](https://developers.kommo.com/reference/get-conversation-messages).
