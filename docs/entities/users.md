# Пользователи

[← README](../../README.md) · [Сущности](../entities.md)

```php
$users = $api->users()->get();
// или из кеша
$users = $api->cache->users();
// пользователь по id
$user = $api->cache->user(128737);

$users = $api->users()->with([
    \Ufee\AmoV4\Services\Users::ROLE,
    \Ufee\AmoV4\Services\Users::GROUP,
    \Ufee\AmoV4\Services\Users::UUID,
    \Ufee\AmoV4\Services\Users::AMOJO_ID,
    \Ufee\AmoV4\Services\Users::USER_RANK,
    \Ufee\AmoV4\Services\Users::PHONE_NUMBER,
])->get();

// группа пользователя
$group = $user->group();
```
