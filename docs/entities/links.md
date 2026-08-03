# Связи сущностей

[← README](../../README.md) · [Сущности](../entities.md)

```php
// через сервис
$links = $api->links('leads', $lead_id)->get();
$links = $api->links('leads', $lead_id)->get(['to_entity_type' => 'contacts']);

$api->links('leads', $lead_id)->add([
    'to_entity_id' => $contact_id,
    'to_entity_type' => 'contacts',
]);
$api->links('leads', $lead_id)->delete($links);
```

Предпочтительный способ — методы модели:

```php
$lead = $api->leads()->find($lead_id);

$lead->attachContact($contact);
$lead->attachContacts([$contact_id1, $contact_id2]);
$lead->detachContact($contact_id);

$lead->attachCompany($company);
$lead->detachCompany($company_id);

$contact->attachLead($lead_id);
$company->attachLead($lead_id);

$links = $lead->links()->get(['to_entity_type' => 'contacts']);
```

Коллекция связей:

```php
$links = $lead->links()->get();
$contacts = $links->contacts();
$company = $links->company();
$leads = $links->leads();
$elements = $links->catalogElements();
$links->delete();
```

См. также: [элементы списков](catalog-elements.md#привязка-к-сделкам-и-покупателям).
