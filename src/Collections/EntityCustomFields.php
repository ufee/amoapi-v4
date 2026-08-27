<?php
/**
 * Amoapi Entity CF Collection class
 * @author Vlad Ionov <vlad@f5.com.ru>
 */
namespace Ufee\AmoV4\Collections;
use \Ufee\AmoV4\Models\EntityCfields\EntityField;
use \Ufee\AmoV4\Models\WithCfield;

class EntityCustomFields
{
    const FIELD_CLASSES = [
		'text' => 'Ufee\AmoV4\Models\EntityCfields\TextField',
		'numeric' => 'Ufee\AmoV4\Models\EntityCfields\NumericField',
		'checkbox' => 'Ufee\AmoV4\Models\EntityCfields\CheckboxField',
		'select' => 'Ufee\AmoV4\Models\EntityCfields\SelectField',
		'multiselect' => 'Ufee\AmoV4\Models\EntityCfields\MultiSelectField',
		'date' => 'Ufee\AmoV4\Models\EntityCfields\DateField',
		'url' => 'Ufee\AmoV4\Models\EntityCfields\UrlField',
		'multitext' => 'Ufee\AmoV4\Models\EntityCfields\MultiTextField',
		'textarea' => 'Ufee\AmoV4\Models\EntityCfields\TextField',
		'radiobutton' => 'Ufee\AmoV4\Models\EntityCfields\RadioButtonField',
		'streetaddress' => 'Ufee\AmoV4\Models\EntityCfields\StreetAddressField',
		'smart_address' => 'Ufee\AmoV4\Models\EntityCfields\SmartAddressField',
		'birthday' => 'Ufee\AmoV4\Models\EntityCfields\DateField',
		'legal_entity' => 'Ufee\AmoV4\Models\EntityCfields\LegalEntityField',
		// 'items'
		'category' => 'Ufee\AmoV4\Models\EntityCfields\SelectField',
		'date_time' => 'Ufee\AmoV4\Models\EntityCfields\DateTimeField',
		'price' => 'Ufee\AmoV4\Models\EntityCfields\NumericField',
		'tracking_data' => 'Ufee\AmoV4\Models\EntityCfields\TextField',
		// 'linked_entity'
		'monetary' => 'Ufee\AmoV4\Models\EntityCfields\NumericField',
		'chained_list' => 'Ufee\AmoV4\Models\EntityCfields\ChainedListField',
		'file' => 'Ufee\AmoV4\Models\EntityCfields\FileField',
		// 'payer'
		// 'supplier'
	];
	protected $collection;
	protected $model;

	/**
	 * Constructor
	 * @param array $entity_cfields
	 * @param WithCfield $model
	 */
	public function __construct(array $entity_cfields, WithCfield $model)
	{
		$this->collection = new Collection();
		$this->model = $model;

		foreach ($entity_cfields as $cfield) {

			$cf_class = static::FIELD_CLASSES[$cfield->field_type] ?? 'Ufee\AmoV4\Models\EntityCfields\EntityField';
			$this->collection->push(
				new $cf_class($cfield, $model), $cfield->field_id
			);
		}
	}

    /**
     * Get all cfields
	 * @return Collection
     */
    public function all()
    {
		return $this->collection;
    }


    /**
     * Get cf by name
     * @param string $name
	 * @return EntityField|bool
     */
    public function byName(string $cf_name)
    {
		return $this->byField('field_name', $cf_name);
    }

    /**
     * Get cf by id
     * @param integer $cf_id
	 * @return EntityField|bool
     */
    public function byId(int $cf_id)
    {
		return $this->byField('field_id', $cf_id);
    }

    /**
     * Get cf by code
     * @param string $cf_code
	 * @return EntityField|bool
     */
    public function byCode(string $cf_code)
    {
		return $this->byField('field_code', $cf_code);
    }

    /**
     * Get cf by type
     * @param string $cf_type
	 * @return EntityField|bool
     */
    public function byType(string $cf_type)
    {
		return $this->byField('field_type', $cf_type);
    }

    /**
     * Get cf by field
     * @param string $field
     * @param int|string $val
	 * @return EntityField|bool
     */
    public function byField(string $field, $val)
    {
		if (!$cf = $this->collection->find($field, $val)->first()) {
			$service = $this->model->service;
			$accountCfs = $service->instance->cache->customFields(...$service->customFieldsArgs());
			$field_key = str_replace('field_', '', $field);

			if ($acf = $accountCfs->find($field_key, $val)->first()) {
				$cfield = (object)[
					'field_id' => $acf->id,
					'field_name' => $acf->name,
					'field_code' => $acf->code,
					'field_type' => $acf->type,
					'values' => []
				];
				$cf_class = static::FIELD_CLASSES[$cfield->field_type] ?? 'Ufee\AmoV4\Models\EntityCfields\EntityField';
				$cf = new $cf_class($cfield, $this->model);
				$this->collection->push($cf, $cfield->field_id);
			}
		}
		return $cf;
	}
}
