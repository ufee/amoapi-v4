# Сделки

[← README](../README.md)

## Сделки

```php
$leads = $api->leads()->get();
$leads = $api->leads;
$leads = $api->leads()->with(['source_id','source']))->get();
$leads = $api->leads()->searchByCustomField('Москва', 'Город', 1); // 1 page (250 rows max)
$leads = $api->leads()->searchByName('Разработка ПО', 1, ['source_id','source']); // 1 page with source

$paginate = $api->leads()
                ->orderBy('updated_at', 'desc')
                ->with(['source_id','source','loss_reason'])
                ->paginate();

$paginate = $api->leads()->filter($conditions = [],  $with = []);
$paginate = $api->leads()->search('VIP');

$lead = $api->leads()->find($lead_id);
$lead = $api->leads()->find($lead_ids, ['source_id','source']);
$lead = $api->leads()->create(['name' => 'Новая сделка']);

$lead->price = 100;
$lead->status_id = 21776227;
$lead->cf('Приоритет')->setValue('Высокий'); // set value name by cf name
$lead->cf(123678)->setEnum(83565); // set enum id by cf id

$lead->attachTag('Tag1');
$lead->attachTag(['name' => 'Цветной', 'color' => 'FF8F92']);
$lead->detachTag('Tag3');
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
$note = $lead->findTask($task_id);
$note = $lead->createNote($type = 'common');
```

См. также: [элементы списков в сделках](catalogs.md#элементы-списков-в-сделках-и-покупателях).

## Причины отказа сделок

```php
// через новый sugar-метод Leads сервиса
$lossReasons = $api->leads()->lossReasons()->get();
$lossReason = $api->leads()->lossReasons()->find($loss_reason_id);

// или напрямую через отдельный сервис
$lossReasons = $api->lossReasons()->get();
$lossReason = $api->lossReasons()->find($loss_reason_id);

// или из кеша
$lossReasons = $this->crm->cache->lossReasons();
$lossReason = $this->crm->cache->lossReason($loss_reason_id);

// очистка кеша
$this->crm->cache->clear('lossReasons');
```
