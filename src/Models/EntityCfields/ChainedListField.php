<?php
/**
 * amoCRM Custom Entity Custom Field model (chained_list)
 *
 * PATCH values are {catalog_id, catalog_element_id} without a nested value key.
 *
 * @see https://www.amocrm.ru/developers/content/crm_platform/custom-fields
 */
namespace Ufee\AmoV4\Models\EntityCfields;

use Ufee\AmoV4\Models\CatalogElement;

class ChainedListField extends EntityField
{
	/** API accepts at most 5 linked catalog elements. */
	public const MAX_VALUES = 5;

	/**
	 * Replace with one item, or a list of items
	 * @param mixed $value CatalogElement, item array/object, or list of those
	 * @return static
	 */
	public function setValue($value)
	{
		if ($this->isItemsList($value)) {
			return $this->setValues($value);
		}
		return $this->setValues([$value]);
	}

	/**
	 * Append one linked catalog element
	 * @param mixed $value CatalogElement or item array/object
	 * @return static
	 */
	public function addValue($value)
	{
		if (!isset($this->data->values) || !is_array($this->data->values)) {
			$this->data->values = [];
		}
		$item = $this->normalizeItem($value);
		$this->assertMaxValues(count($this->data->values) + 1);
		$this->data->values[] = $item;
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Replace all linked catalog elements (max 5).
	 * Accepts:
	 *   [['catalog_id' => 1001, 'catalog_element_id' => 12235], …]
	 *   [(object)['catalog_id' => 1001, 'catalog_element_id' => 12235], …]
	 *   [CatalogElement, …]
	 * @param array $values
	 * @return static
	 */
	public function setValues(array $values)
	{
		$this->assertMaxValues(count($values));
		$items = [];
		foreach ($values as $value) {
			$items[] = $this->normalizeItem($value);
		}
		$this->data->values = $items;
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * First linked element as object {catalog_id, catalog_element_id}
	 * @return object|null
	 */
	public function getValue()
	{
		if (!isset($this->data->values[0])) {
			return null;
		}
		return $this->unwrapItem($this->data->values[0]);
	}

	/**
	 * Linked elements as objects {catalog_id, catalog_element_id}
	 * @return list<object>
	 */
	public function getValues()
	{
		$values = [];
		foreach ($this->data->values ?? [] as $item) {
			$values[] = $this->unwrapItem($item);
		}
		return $values;
	}

	/**
	 * Linked elements as arrays of API keys
	 * @return list<array{catalog_id: int, catalog_element_id: int}>
	 */
	public function toArray(): array
	{
		$result = [];
		foreach ($this->getValues() as $item) {
			$result[] = [
				'catalog_id' => (int) ($item->catalog_id ?? 0),
				'catalog_element_id' => (int) ($item->catalog_element_id ?? 0),
			];
		}
		return $result;
	}

	/**
	 * PATCH schema rejects extra keys; send only catalog_id + catalog_element_id.
	 * @return object
	 */
	public function getRawData()
	{
		$values = $this->data->values ?: null;
		if (is_array($values)) {
			$values = array_values(array_map([$this, 'toApiValue'], $values));
		}
		return (object)[
			'field_id' => $this->data->field_id,
			'values' => $values
		];
	}

	/**
	 * @param mixed $value
	 * @return object
	 */
	protected function normalizeItem($value): object
	{
		if ($value instanceof CatalogElement) {
			$catalogId = (int) ($value->catalog_id ?: $value->service->catalog_id);
			$elementId = (int) $value->id;
			$this->assertIds($catalogId, $elementId);
			return $this->makeItem($catalogId, $elementId);
		}
		if (!is_array($value) && !is_object($value)) {
			throw new \InvalidArgumentException(
				'chained_list value must be array/object with catalog_id and catalog_element_id, or CatalogElement'
			);
		}
		$row = $this->unwrapItem($value);
		$catalogId = (int) ($row->catalog_id ?? 0);
		$elementId = (int) ($row->catalog_element_id ?? 0);
		$this->assertIds($catalogId, $elementId);
		return $this->makeItem($catalogId, $elementId);
	}

	/**
	 * @param mixed $item
	 * @return object
	 */
	protected function unwrapItem($item): object
	{
		$row = is_object($item) ? $item : (object) $item;
		if (isset($row->value) && (is_object($row->value) || is_array($row->value))) {
			$inner = is_object($row->value) ? $row->value : (object) $row->value;
			if (isset($inner->catalog_id) || isset($inner->catalog_element_id)) {
				return $inner;
			}
		}
		return $row;
	}

	/**
	 * @param mixed $item
	 * @return object
	 */
	protected function toApiValue($item): object
	{
		$row = $this->unwrapItem($item);
		return $this->makeItem(
			(int) ($row->catalog_id ?? 0),
			(int) ($row->catalog_element_id ?? 0)
		);
	}

	/**
	 * @param int $catalogId
	 * @param int $elementId
	 * @return object
	 */
	protected function makeItem(int $catalogId, int $elementId): object
	{
		return (object)[
			'catalog_id' => $catalogId,
			'catalog_element_id' => $elementId,
		];
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	protected function isItemsList($value): bool
	{
		if (!is_array($value) || $value === []) {
			return false;
		}
		$i = 0;
		foreach ($value as $key => $item) {
			if ($key !== $i || (!is_array($item) && !is_object($item))) {
				return false;
			}
			$i++;
		}
		return true;
	}

	/**
	 * @param int $count
	 * @return void
	 */
	protected function assertMaxValues(int $count): void
	{
		if ($count > self::MAX_VALUES) {
			throw new \InvalidArgumentException(
				sprintf('chained_list accepts at most %d values', self::MAX_VALUES)
			);
		}
	}

	/**
	 * @param int $catalogId
	 * @param int $elementId
	 * @return void
	 */
	protected function assertIds(int $catalogId, int $elementId): void
	{
		if ($catalogId < 1 || $elementId < 1) {
			throw new \InvalidArgumentException(
				'chained_list value requires catalog_id and catalog_element_id'
			);
		}
	}
}
