# Списки (Catalogs)

[← README](../../README.md) · [Сущности](../entities.md)

```php
$catalogs = $api->catalogs()->get();
$catalog = $api->catalogs()->find($catalog_id);
// или из кеша
$catalogs = $api->cache->catalogs();
$catalog = $api->cache->catalog($catalog_id);

// создание списка
$catalog = $api->catalogs()->create(['name' => 'Договоры']);
$catalog->type = \Ufee\AmoV4\Services\Catalogs::TYPE_REGULAR; // regular|invoices|products
$catalog->can_add_elements = true;
$catalog->can_link_multiple = false;
$catalog->save();
// системный список Catalogs::TYPE_SUPPLIERS ('suppliers') создается аккаунтом вместе со счетами,
// он доступен только на чтение и не может быть создан через API

// обновление списка
$catalog->name = 'Новое имя списка';
$catalog->save();

// очистка кеша
$api->cache->clear('catalogs');
```

См. также: [элементы списков](catalog-elements.md).
