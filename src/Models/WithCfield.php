<?php
/**
 * amoCRM model with custom fields
 */
namespace Ufee\AmoV4\Models;
use \Ufee\AmoV4\Services\Service;
use \Ufee\AmoV4\Collections\EntityCustomFields;

class WithCfield extends Model
{
	protected $fields = [
		'updated_at' => 0
	];
	protected $embedded = [];
	protected $cfields = [];
	protected $required = [];
	protected $temporary = [];
	protected $changed_fields = [];
	protected $changed_cfields = [];
	protected $changed_embedded = [];
	protected $service;
	
    /**
     * Constructor
	 * @param array $data
	 * @param Service $service
     */
    public function __construct(array $data, Service $service)
    {
		$this->service = $service;
		$this->embedded = (array)($data['_embedded'] ?? []);
		$this->cfields = $data['custom_fields_values'] ?? [];
		unset($data['_embedded'], $data['_links'], $data['custom_fields_values']);
		$this->fields = array_merge($this->fields, (array)$data);
		$this->_boot($data);
    }
	
    /**
     * Get custom fields
	 * @param string|int|null $cf_id_name
	 * @return EntityCustomFields
     */
    public function cf($cf_id_name = null)
    {
		if (is_array($this->cfields)) {
			$this->cfields = new EntityCustomFields($this->cfields, $this);
		}
		if (is_null($cf_id_name)) {
			return $this->cfields;
		}
		return is_int($cf_id_name) ? $this->cfields->byId($cf_id_name) : $this->cfields->byName($cf_id_name);
	}
	
    /**
     * Get changed raw model api data
	 * @param array $required
	 * @return object
     */
    public function getChangedRawData(array $required = [])
	{
		$fields = parent::getChangedRawData($required);
		
		if (!empty($this->changed_cfields)) {
			
			$fields->custom_fields_values = [];
			
			foreach ($this->changed_cfields as $field_id) {

				$fields->custom_fields_values[]= $this->cfields->byId($field_id)->getRawData();
			}
		}
		return $fields;
	}
	
	/**
     * Set changed custom field
	 * @param int $field_id
	 * @return WithCfield
     */
    public function cfChanged(int $field_id)
    {
		if (!in_array($field_id, $this->changed_cfields)) {
			$this->changed_cfields[]= $field_id;
		}
		return $this;
	}
	
	/**
     * Saved data trigger
	 * @param integer $id
	 * @return void
     */
    public function _saved()
    {
		parent::_saved();
		
		$this->changed_cfields = [];
	}
}
