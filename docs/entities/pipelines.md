# Воронки и этапы

[← README](../../README.md) · [Сущности](../entities.md)

## Воронки сделок

```php
$pipelines = $api->pipelines()->get();
$pipeline = $api->pipelines()->find($pipeline_id);
// или из кеша
$pipelines = $api->cache->pipelines();
$pipeline = $api->cache->pipeline($pipeline_id);
// или новая воронка
$pipeline = $api->pipelines()->create(['name' => 'Рекламации']);

$pipeline->sort = 20;
$pipeline->is_main = false;
$pipeline->is_unsorted_on = false;
$pipeline->_embedded = [];
$pipeline->save();

// удаление воронки
$pipeline->delete();
```

## Этапы воронок

```php
$statuses = $pipeline->statuses(); // collection
$statuses = $api->pipelineStatuses($pipeline_id)->with(['descriptions'])->get();
$status = $api->pipelineStatuses($pipeline_id)->find($status_id, ['descriptions']);

// или новый этап
$status = $api->pipelineStatuses($pipeline_id)->create(['name' => 'Договор подписан','sort' => 10]);
$status = $pipeline->createStatus(['name' => 'Договор подписан','sort' => 10]);

// обновление этапа
$status->sort = 50;
$status->save();

// удаление этапа
$status->delete();
$pipeline->deleteStatus($status_id);
```
