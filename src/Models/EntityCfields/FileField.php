<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class FileField extends EntityField
{
	/**
	 * Reset file cf value.
	 * File fields require exactly one values element; empty array is rejected by API.
	 * @return EntityField
	 */
	public function reset()
	{
		$this->data->values = [
			(object)['value' => null]
		];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}
}
