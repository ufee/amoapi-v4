<?php
/**
 * amoCRM Custom Entity Custom Field model (multiselect)
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class MultiSelectField extends EntityField
{
	/**
	 * Append one option by value (no-op if already selected)
	 * @param mixed $value
	 * @return static
	 */
	public function addValue($value)
	{
		$this->ensureValues();
		if ($this->containsValue($value)) {
			return $this;
		}
		$this->data->values[] = (object)['value' => $value];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Append options by value (duplicates skipped)
	 * @param array $values
	 * @return static
	 */
	public function addValues(array $values)
	{
		foreach ($values as $value) {
			$this->addValue($value);
		}
		return $this;
	}

	/**
	 * Append one option by enum_id (no-op if already selected)
	 * @param int $enum_id
	 * @return static
	 */
	public function addEnum(int $enum_id)
	{
		$this->ensureValues();
		if ($this->containsEnum($enum_id)) {
			return $this;
		}
		$this->data->values[] = (object)['enum_id' => $enum_id];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Append options by enum_id (duplicates skipped)
	 * @param array $enum_ids
	 * @return static
	 */
	public function addEnums(array $enum_ids)
	{
		foreach ($enum_ids as $enum_id) {
			$this->addEnum((int)$enum_id);
		}
		return $this;
	}

	/**
	 * Remove option by value
	 * @param mixed $value
	 * @return static
	 */
	public function removeValue($value)
	{
		return $this->filterValues(function ($item) use ($value) {
			return !property_exists($item, 'value') || $item->value != $value;
		});
	}

	/**
	 * Remove options by value
	 * @param array $values
	 * @return static
	 */
	public function removeValues(array $values)
	{
		foreach ($values as $value) {
			$this->removeValue($value);
		}
		return $this;
	}

	/**
	 * Remove option by enum_id
	 * @param int $enum_id
	 * @return static
	 */
	public function removeEnum(int $enum_id)
	{
		return $this->filterValues(function ($item) use ($enum_id) {
			return !isset($item->enum_id) || (int)$item->enum_id !== $enum_id;
		});
	}

	/**
	 * Remove options by enum_id
	 * @param array $enum_ids
	 * @return static
	 */
	public function removeEnums(array $enum_ids)
	{
		foreach ($enum_ids as $enum_id) {
			$this->removeEnum((int)$enum_id);
		}
		return $this;
	}

	/**
	 * GET includes enum_code on selected options; PATCH schema only accepts
	 * value / enum_id and rejects enum_code with FieldNotExpected.
	 * @return object
	 */
	public function getRawData()
	{
		$values = $this->data->values ?: null;
		if (is_array($values)) {
			$values = array_map([$this, 'toApiValue'], $values);
		}
		return (object)[
			'field_id' => $this->data->field_id,
			'values' => $values
		];
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	protected function containsValue($value): bool
	{
		foreach ($this->data->values as $item) {
			if (property_exists($item, 'value') && $item->value == $value) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param int $enum_id
	 * @return bool
	 */
	protected function containsEnum(int $enum_id): bool
	{
		foreach ($this->data->values as $item) {
			if (isset($item->enum_id) && (int)$item->enum_id === $enum_id) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param callable $keep
	 * @return static
	 */
	protected function filterValues(callable $keep)
	{
		$this->ensureValues();
		$filtered = array_values(array_filter($this->data->values, $keep));
		if (count($filtered) === count($this->data->values)) {
			return $this;
		}
		$this->data->values = $filtered;
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	protected function ensureValues(): void
	{
		if (!isset($this->data->values) || !is_array($this->data->values)) {
			$this->data->values = [];
		}
	}

	/**
	 * @param object $item
	 * @return object
	 */
	protected function toApiValue(object $item): object
	{
		$row = [];
		if (property_exists($item, 'value')) {
			$row['value'] = $item->value;
		}
		if (isset($item->enum_id)) {
			$row['enum_id'] = (int)$item->enum_id;
		}
		return (object)$row;
	}
}
