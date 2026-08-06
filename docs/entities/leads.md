# Сделки

[← README](../../README.md) · [Сущности](../entities.md)

```php
$leads = $api->leads()->get();
$leads = $api->leads;
$leads = $api->leads()->with(['source_id', 'source'])->get();
$leads = $api->leads()->withAll()->get(); // все with, кроме only_deleted
$leads = $api->leads()->with(\Ufee\AmoV4\Services\Leads::withValues())->get();
$leads = $api->leads()->searchByCustomField('Москва', 'Город', 1); // 1 page (250 rows max)
$leads = $api->leads()->searchByName('Разработка ПО', 1, ['source_id', 'source']);

$paginate = $api->leads()
                ->orderBy('updated_at', 'desc')
                ->with(['source_id', 'source', 'loss_reason'])
                ->paginate();

$paginate = $api->leads()->filter($conditions = [], $with = []);
$paginate = $api->leads()->search('VIP');

$lead = $api->leads()->find($lead_id);
$lead = $api->leads()->find($lead_ids, ['source_id', 'source']);
$lead = $api->leads()->create(['name' => 'Новая сделка']);

$lead->price = 100;
$lead->status_id = 21776227;
$lead->cf('Приоритет')->setValue('Высокий'); // set value name by cf name
$lead->cf(123678)->setEnum(83565); // set enum id by cf id
$lead->save();

$pipeline = $lead->pipeline();
$status = $lead->status();
$user = $lead->responsibleUser();

$lead->attachTag('Tag1');
$lead->attachTag(['name' => 'Цветной', 'color' => 'FF8F92']);
$lead->attachTags(['Tag1', 'Tag2']);
$lead->detachTag('Tag3');
$lead->detachTags(['Tag3', 'Tag4']);
$tags = $lead->getTags();

// replace all existing tags
$lead->setTags($tags); // ids or names
$lead->resetTags(); // replace with none

$paginate = $lead->getTasks($filter = []);
$tasks = $lead->getTasks($filter = [])->fetchAll();
$task = $lead->findTask($task_id);
$task = $lead->createTask($type = 1);

$paginate = $lead->getNotes($filter = []);
$notes = $lead->getNotes($filter = [])->fetchAll();
$note = $lead->findNote($note_id);
$note = $lead->createNote($type = 'common');

// связи
$lead->attachContact($contact);
$lead->attachCompany($company);

// создание связанного контакта/компании (поля задаются после create*)
$contact = $lead->createContact();
$contact->name = 'Иван';
$contact->save();

$company = $lead->createCompany();
$company->name = 'ООО Ромашка';
$company->save();

// главный контакт
$main = $lead->getMainContact();
$bool = $lead->hasMainContact();
```

См. также: [причины отказа](loss-reasons.md), [элементы списков](catalog-elements.md#привязка-к-сделкам-и-покупателям), [подписчики](subscriptions.md), [задачи](tasks.md), [заметки](notes.md), [связи](links.md).
