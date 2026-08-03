# Компании

[← README](../../README.md) · [Сущности](../entities.md)

```php
$companies = $api->companies()->get();
$companies = $api->companies;
$companies = $api->companies()->with(['contacts', 'leads', 'customers', 'catalog_elements'])->get();

$companies = $api->companies()->searchByName('Ромашка', 1);
$companies = $api->companies()->searchByCustomField('Москва', 'Город', 1);

$company = $api->companies()->find($company_id);
$company = $api->companies()->create(['name' => 'ООО Ромашка']);

$company->cf('ИНН')->setValue('7707083893');
$company->save();

$company->attachTag('Партнёр');
$company->attachLead($lead_id);
$company->attachContact($contact_id);
$company->attachContacts([$contact_id1, $contact_id2]);

$contacts = $company->contacts();
$paginate = $company->getTasks($filter = []);
$task = $company->createTask($type = 1);
$note = $company->createNote($type = 'common');
```

См. также: [кастомные поля](custom-fields.md), [связи](links.md), [задачи](tasks.md), [заметки](notes.md).
