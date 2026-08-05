<?php
/**
 * amoCRM Base model
 */
namespace Ufee\AmoV4\Models;
use \Ufee\AmoV4\Services\Service;
use Ufee\AmoV4\Exceptions;

class Model
{
	public $request_id;
	protected $fields = [];
	protected $embedded = [];
	protected $required = [];
	protected $temporary = [];
	protected $changed_fields = [];
	protected $changed_embedded = [];
	protected $on_create = [];
	protected $service;

	/**
	 * Constructor
	 * @param array $data
	 * @param Service $service
	 */
	public function __construct(array $data, Service $service)
	{
		$this->service = $service;
		$this->embedded = (array) ($data['_embedded'] ?? []);
		unset($data['_embedded'], $data['_links']);
		$this->fields = (array) $data;
		$this->_boot($data);
	}

	/**
	 * Model on load
	 * @param array $data
	 * @return void
	 */
	protected function _boot(array $data = [])
	{
		// code...
	}

	/**
	 * Has field exists
	 * @param string $field
	 * @return bool
	 */
	public function hasField(string $field)
	{
		return array_key_exists($field, $this->fields);
	}

	/**
	 * Saved data trigger
	 * @return void
	 */
	public function _saved()
	{
		$this->changed_fields = [];
		$this->changed_embedded = [];
		$this->temporary = [];
	}

	/**
	 * Set changed field
	 * @param string $field
	 * @return Model
	 */
	public function setChanged(string $field)
	{
		if (!in_array($field, $this->changed_fields)) {
			$this->changed_fields[] = $field;
		}
		return $this;
	}

	/**
	 * Has changed field
	 * @param string $field
	 * @return bool
	 */
	public function hasChanged(string $field)
	{
		return in_array($field, $this->changed_fields);
	}

	/**
	 * Get changed raw model api data
	 * @param array $required
	 * @return object
	 */
	public function getChangedRawData(array $required = [])
	{
		$data = $this->temporary;
		if (empty($this->fields['id'])) {
			$data = array_merge($data, $this->fields);
		}
		$changed_fields = array_unique(array_merge($this->changed_fields, $this->required, $required));
		foreach ($changed_fields as $field) {
			if (!isset($this->fields[$field])) {
				throw new Exceptions\AmoException(static::getBasename() . ' required field value not found: ' . $field);
			}
			$data[$field] = $this->fields[$field];
		}
		if (!empty($this->changed_embedded)) {
			$data['_embedded'] = [];
			foreach ($this->changed_embedded as $field) {
				$data['_embedded'][$field] = $this->embedded[$field];
			}
		}
		return (object) $data;
	}

	/**
	 * Register callback after successful create (save without id)
	 * @param callable $callback function(Model $model): void
	 * @return Model
	 */
	public function onCreate(callable $callback)
	{
		$this->on_create[] = $callback;
		return $this;
	}

	/**
	 * Save model in CRM
	 * @return bool
	 */
	public function save()
	{
		$is_new = empty($this->fields['id']);
		if ($is_new) {
			// create new entity
			if (!$result = $this->service->add($this->getChangedRawData())) {
				return false;
			}
		}
		// update entity
		else if (!$result = $this->service->update($this->id, $this->getChangedRawData(['id']))) {
			return false;
		}
		foreach ($result as $field => $val) {
			if (in_array($field, ['request_id', '_links'])) {
				continue;
			}
			$this->setSilent($field, $val);
		}
		$this->_saved();
		if ($is_new && $this->on_create !== []) {
			$callbacks = $this->on_create;
			$this->on_create = [];
			foreach ($callbacks as $callback) {
				$callback($this);
			}
		}
		return true;
	}

	/**
	 * Set field value
	 * @param string $field
	 * @param mixed $value
	 */
	public function set(string $field, $value)
	{
		$this->$field = $value;
	}

	/**
	 * Silent set field value
	 * @param string $field
	 * @param mixed $value
	 */
	public function setSilent(string $field, $value)
	{
		$this->fields[$field] = $value;
	}

	/**
	 * Protect get model fields
	 * @param string $field
	 */
	public function __get(string $field)
	{
		if (array_key_exists($field, $this->fields)) {
			return $this->fields[$field];
		}
		if (array_key_exists($field, $this->embedded)) {
			return $this->embedded[$field];
		}
		if ($field === 'service') {
			return $this->service;
		}
		return null;
	}

	/**
	 * Has model field value, keeps isset()/empty() consistent with __get()
	 * @param string $field
	 * @return bool
	 */
	public function __isset(string $field)
	{
		return !is_null($this->__get($field));
	}

	/**
	 * Protect set model fields
	 * @param string $field
	 * @param mixed $value
	 */
	public function __set(string $field, $value)
	{
		$current_field_val = $this->fields[$field] ?? null;
		if ($current_field_val !== $value && !in_array($field, $this->changed_fields)) {
			$this->changed_fields[] = $field;
		}
		$this->fields[$field] = $value;
	}

	/**
	 * Convert Model to array
	 * @return array
	 */
	public function toArray()
	{
		$fields = [];
		foreach ($this->fields as $field_key => $val) {
			$fields[$field_key] = $val;
		}
		foreach ($this->embedded as $field_key => $val) {
			$fields[$field_key] = $val;
		}
		return $fields;
	}

	/**
	 * Get class basename
	 * @return string
	 */
	public static function getBasename()
	{
		return substr(static::class, strrpos(static::class, '\\') + 1);
	}
}
