# Контакты

[← README](../../README.md) · [Сущности](../entities.md)

```php
$contacts = $api->contacts()->get();
$contacts = $api->contacts;
$contacts = $api->contacts()->with([
    \Ufee\AmoV4\Services\Contacts::LEADS,
    \Ufee\AmoV4\Services\Contacts::CUSTOMERS,
    \Ufee\AmoV4\Services\Contacts::CATALOG_ELEMENTS,
])->get();
$contacts = $api->contacts()->withAll()->get();
$contacts = $api->contacts()->with(\Ufee\AmoV4\Services\Contacts::withValues())->get();

$contacts = $api->contacts()->searchByName('Иван', 1);
$contacts = $api->contacts()->searchByPhone('+79001234567', 1);
$contacts = $api->contacts()->searchByEmail('a@b.ru', 1);
$contacts = $api->contacts()->searchByCustomField('Москва', 'Город', 1);

$contact = $api->contacts()->find($contact_id);
$contact = $api->contacts()->create(['name' => 'Иван']);

$contact->cf()->byCode(\Ufee\AmoV4\Enums\CustomFields\EmailEnum::CODE)
    ->setValue('a@b.ru', \Ufee\AmoV4\Enums\CustomFields\EmailEnum::WORK);
$contact->cf()->byCode(\Ufee\AmoV4\Enums\CustomFields\PhoneEnum::CODE)
    ->setValue('+79001234567', \Ufee\AmoV4\Enums\CustomFields\PhoneEnum::MOB);
$contact->save();

$user = $contact->responsibleUser();

$contact->attachTag('VIP');
$contact->attachTags(['VIP', 'Partner']);
$contact->attachLead($lead_id);
$contact->attachCompany($company_id);

// создание связанной сделки/компании (поля задаются после create*)
$lead = $contact->createLead();
$lead->name = 'Новая сделка';
$lead->save();

$company = $contact->createCompany();
$company->name = 'ООО Ромашка';
$company->save();

$paginate = $contact->getTasks($filter = []);
$task = $contact->createTask($type = 1);
$note = $contact->createNote($type = 'common');

// заметки через сервис
$notes = $api->contacts()->notes()->get();
```

См. также: [кастомные поля](custom-fields.md), [связи](links.md), [задачи](tasks.md), [заметки](notes.md).
