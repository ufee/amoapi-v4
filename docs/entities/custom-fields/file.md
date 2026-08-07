# file

[← Поля и группы полей](../custom-fields.md)

Связь кастомного поля с файлом из [Drive API](../files.md). Класс `FileField`.

```php
// 1) загрузить файл в Drive
$file = $api->files()->upload('/path/to/contract.pdf');

// 2) записать в поле типа file
$lead->cf('Договор')->setFile($file);
// или
$lead->cf('Договор')->setFile([
    'file_uuid' => $file->uuid,
    'file_name' => $file->name,
    'file_size' => $file->size,
]);
$lead->save();

// чтение
$cf = $lead->cf('Договор');
if ($cf->hasFile()) {
    echo $cf->getUuid();
    echo $cf->getFileName();
    echo $cf->getFileSize();
    $driveFile = $cf->getFile(); // модель File (запрос в Drive)
    echo $driveFile->getDownloadUrl();
}

// очистка поля (API требует values с null, пустой массив не принимается)
$lead->cf('Договор')->reset();
$lead->save();
```

Это **не** то же самое, что прикрепление файла к сущности (`$lead->attachFiles()`). См. [Файлы](../files.md).
