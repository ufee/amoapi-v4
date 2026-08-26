# Компании

[← README](../../README.md) · [Сущности](../entities.md)

```php
$companies = $api->companies()->get();
$companies = $api->companies;
$companies = $api->companies()->with([
    \Ufee\AmoV4\Services\Companies::CONTACTS,
    \Ufee\AmoV4\Services\Companies::LEADS,
    \Ufee\AmoV4\Services\Companies::CUSTOMERS,
    \Ufee\AmoV4\Services\Companies::CATALOG_ELEMENTS,
])->get();
$companies = $api->companies()->withAll()->get();
$companies = $api->companies()->with(\Ufee\AmoV4\Services\Companies::withValues())->get();

$companies = $api->companies()->searchByName('Ромашка', 1);
$companies = $api->companies()->searchByPhone('+79001234567', 1);
$companies = $api->companies()->searchByEmail('info@example.com', 1);
$companies = $api->companies()->searchByCustomField('Москва', 'Город', 1);

$company = $api->companies()->find($company_id);
$company = $api->companies()->create(['name' => 'ООО Ромашка']);

$company->cf('ИНН')->setValue('7707083893');
$company->save();

$user = $company->responsibleUser();

$company->attachTag('Партнёр');
$company->attachLead($lead_id);
$company->attachContact($contact_id);
$company->attachContacts([$contact_id1, $contact_id2]);

// создание связанной сделки/контакта (поля задаются после create*)
$lead = $company->createLead();
$lead->name = 'Новая сделка';
$lead->save();

$contact = $company->createContact();
$contact->name = 'Иван';
$contact->save();

$contacts = $company->contacts();
$paginate = $company->getTasks($filter = []);
$task = $company->createTask($type = 1);
$note = $company->createNote($type = 'common');

// заметки через сервис
$notes = $api->companies()->notes()->get();
```

См. также: [кастомные поля](custom-fields.md), [связи](links.md), [задачи](tasks.md), [заметки](notes.md).
