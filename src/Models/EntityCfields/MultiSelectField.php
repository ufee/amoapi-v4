<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class MultiSelectField extends EntityField
{
    public function getRawData()
    {
		return (object)[
			'field_id' => $this->data->field_id,
			'values' => $this->data->values ?: null
		];
	}
}
