# Файлы (Drive API)

[← README](../../README.md) · [Сущности](../entities.md)

Работа с [API файлов](https://www.amocrm.ru/developers/content/files/files-api). Большинство методов выполняются на хосте Drive (`drive_url` из параметров аккаунта). У интеграции должен быть scope «Доступ к файлам» (для удаления — также «Удаление файлов»).

```php
$files = $api->files();
```

## Загрузка файла

```php
// из пути на диске
$file = $api->files()->upload('/path/to/document.pdf');

// из бинарной строки
$file = $api->files()->upload($binary, [
    'file_name' => 'photo.jpg',
    'content_type' => 'image/jpeg',
    'with_preview' => true,
]);

// новая версия существующего файла
$file = $api->files()->upload('/path/to/v2.pdf', [
    'file_uuid' => $uuid,
]);

echo $file->uuid;
echo $file->getDownloadUrl();
```

Низкоуровневая загрузка по частям:

```php
$session = $api->files()->createSession([
    'file_name' => 'big.bin',
    'file_size' => filesize($path),
    'content_type' => 'application/octet-stream',
]);

$upload_url = $session->upload_url;
$handle = fopen($path, 'rb');
while (!feof($handle)) {
    $chunk = fread($handle, $session->max_part_size);
    $result = $api->files()->uploadPart($upload_url, $chunk);
    if (!empty($result->next_url)) {
        $upload_url = $result->next_url;
    }
}
fclose($handle);
// $result — объект (stdClass) загруженного файла, модель File возвращает только upload()
```

## Получение и фильтры

```php
$file = $api->files()->find($uuid);

$files = $api->files()->get();
$files = $api->files()->filter([
    'name' => 'contract',
    'extensions' => ['pdf', 'docx'],
])->fetchAll();

$files = $api->files()->search('счёт')->fetchAll();

// удалённые файлы, значение — пустая строка: null не попадает в query string
$trashed = $api->files()->filter(['deleted' => ''])->fetchAll();
```

## Статистика диска

`GET /v1.0/files/stats` на хосте Drive. Значения `limit` и `used` — в байтах.

```php
$stats = $api->files()->stats();
echo $stats->limit; // квота аккаунта
echo $stats->used;  // занято
```

## Редактирование

```php
$file = $api->files()->find($uuid);
$file->name = 'Новое имя';
$file->save();

// или смена активной версии
$file->version_uuid = $version_uuid;
$file->save();
```

## Удаление и восстановление

```php
$file->delete();
$api->files()->delete([$uuid1, $uuid2]);

$api->files()->restore($uuid);
$api->files()->restore([$uuid1, $uuid2]);
```

## Версии файла

```php
$versions = $file->versions();
// или
$versions = $api->files()->versions($uuid);

foreach ($versions as $version) {
    echo $version->uuid . ' ' . $version->is_main;
    echo $version->getDownloadUrl();
}
```

## Связь с сущностями

Методы на CRM-хосте (не Drive).

```php
// через сервис
$links = $api->files()->getByEntity('leads', $lead_id);
$api->files()->attachToEntity('leads', $lead_id, $file->uuid);
$api->files()->detachFromEntity('leads', $lead_id, [$uuid1, $uuid2]);

// сущности, связанные с файлом
$linked = $api->files()->getLinks($uuid);
// $linked->file_uuid, $linked->entities

// через модель сделки / контакта / компании / покупателя
$lead = $api->leads()->find($lead_id);
$lead->attachFiles($file);
$lead->attachFiles([$uuid1, $uuid2]);
$files = $lead->getFiles(); // массивы: $row['file_uuid'], $row['id']
$lead->detachFiles($uuid1);
```

Чтобы записать файл в **кастомное поле типа `file`**, используйте `FileField::setFile()` — см. [Поля → file](custom-fields/file.md).
