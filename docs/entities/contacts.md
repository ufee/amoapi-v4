# Контакты

[← README](../../README.md) · [Сущности](../entities.md)

```php
$contacts = $api->contacts()->get();
$contacts = $api->contacts;
$contacts = $api->contacts()->with(['leads', 'customers', 'catalog_elements'])->get();

$contacts = $api->contacts()->searchByName('Иван', 1);
$contacts = $api->contacts()->searchByPhone('+79001234567', 1);
$contacts = $api->contacts()->searchByEmail('a@b.ru', 1);
$contacts = $api->contacts()->searchByCustomField('Москва', 'Город', 1);

$contact = $api->contacts()->find($contact_id);
$contact = $api->contacts()->create(['name' => 'Иван']);

$contact->cf('Email')->setValue('a@b.ru');
$contact->cf('Phone')->setValue('+79001234567');
$contact->save();

$contact->attachTag('VIP');
$contact->attachLead($lead_id);
$contact->attachCompany($company_id);

$paginate = $contact->getTasks($filter = []);
$task = $contact->createTask($type = 1);
$note = $contact->createNote($type = 'common');
```

См. также: [кастомные поля](custom-fields.md), [связи](links.md), [задачи](tasks.md), [заметки](notes.md).
