<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;
use \Ufee\AmoV4\Models\WithCfield;

class EntityField
{
	protected $data;
	protected $model;
	
    /**
     * Constructor
	 * @param object $data
     */
    public function __construct(object $data, WithCfield $model)
    {
        $this->data = $data;
        $this->model = $model;
	}
	
    /**
     * Get cf value
	 * @return mixed
     */
    public function getValue()
    {
		if (!isset($this->data->values[0])) {
			return null;
		}
		return $this->data->values[0]->value ?? $this->data->values[0];
	}

    /**
     * Get cf values
	 * @return array
     */
    public function getValues()
    {
        $values = [];
		foreach ($this->data->values as $setted) {
			if (property_exists($setted, 'value')) {
				$values[]= $setted->value;
			}
        }
        return $values;
    }
	
    /**
     * Set cf value
	 * @param mixed $value value
	 * @return EntityField
     */
    public function setValue($value)
    {
		$this->data->values = [
			(object)['value' => $value]
		];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}
	
    /**
     * Set cf values
	 * @param array $values
	 * @return EntityField
     */
    public function setValues(array $values)
    {
		$this->data->values = [];
		foreach($values as $value) {
			$this->data->values[]= [
				(object)['value' => $value]
			];
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}
	
    /**
     * Set cf enum
	 * @param int $enum_id
	 * @return EntityField
     */
    public function setEnum(int $enum_id)
    {
		$this->data->values = [
			(object)['enum_id' => $enum_id]
		];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}
	
    /**
     * Set cf enums
	 * @param array $enum_ids
	 * @return EntityField
     */
    public function setEnums(array $enum_ids)
    {
		$this->data->values = [];
		foreach($enum_ids as $enum_id) {
			$this->data->values[]= [
				(object)['enum_id' => $enum_id]
			];
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

    /**
     * Reset cf value
	 * @return EntityField
     */
    public function reset()
    {
		$this->data->values = [];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}
	
    /**
     * Get cf raw data
	 * @return object
     */
    public function getRawData()
    {
		return (object)[
			'field_id' => $this->data->field_id,
			'values' => $this->data->values
		];
	}
	
    /**
     * Protect get cf property
	 * @param string $property
     */
	public function __get(string $property)
	{
		if (property_exists($this->data, $property)) {
			return $this->data->{$property};
		}
		if ($property === 'field') {
			$service = $this->model->service;
			return $service->instance->cache->customFields($service->entity_key)->find('id', $this->data->field_id)->first();
		}
		return null;
	}
}
