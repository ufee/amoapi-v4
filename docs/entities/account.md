# Параметры аккаунта

[← README](../../README.md) · [Сущности](../entities.md)

```php
$account = $api->account()->get();
// или
$account = $api->account;
// все with-параметры
$account = $api->account()->withAll()->get();
$account = $api->account()->with(\Ufee\AmoV4\Services\Account::withValues())->get();
// или из кеша
$account = $api->cache->account();

// модель аккаунта
echo $account->id;
echo $account->name;
echo $account->drive_url; // хост Files/Drive API, см. [файлы](files.md)

$userGroups = $account->userGroups; // collection
$taskTypes = $account->taskTypes; // collection

// или из кеша
$userGroups = $api->cache->userGroups();
$taskTypes = $api->cache->taskTypes();
$eventTypes = $api->cache->eventTypes($lang = null); // текущий язык по умолчанию

// очистка кеша
$api->cache->clear('account');
```
