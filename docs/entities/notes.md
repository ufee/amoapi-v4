# Заметки

[← README](../../README.md) · [Сущности](../entities.md)

```php
// все заметки по типу сущности
$notes = $api->notes('leads')->get();
$notes = $api->notes('contacts')->get();
$notes = $api->notes('customers')->get();

// заметки конкретной сущности
$notes = $api->notes('leads', $lead_id)->get();

// sugar-методы есть у leads / contacts / companies (у customers — нет)
$notes = $api->leads()->notes()->get();
$notes = $api->contacts()->notes()->get();
$notes = $api->companies()->notes()->get();

$note = $api->notes('leads', $lead_id)->create(['note_type' => 'common']);
$note->setParams(['text' => 'Текст заметки']);
$note->save();

// закрепление
$api->notes('leads')->pin($note->id);
$api->notes('leads')->unpin($note->id);
// или через модель
$note->pin();
$note->unpin();
$bool = $note->isPinned();
```

Создание через модель сущности (в т.ч. у покупателей):

```php
$note = $lead->createNote($type = 'common');
$note = $customer->createNote($type = 'common');
$notes = $lead->getNotes($filter = [])->fetchAll();
$note = $lead->findNote($note_id);
```

См. также: [сделки](leads.md), [контакты](contacts.md), [компании](companies.md), [покупатели](customers.md).
