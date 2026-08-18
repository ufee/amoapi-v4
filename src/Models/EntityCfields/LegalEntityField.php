<?php
/**
 * amoCRM Custom Entity Custom Field model (legal_entity)
 *
 * @see https://www.amocrm.ru/developers/content/crm_platform/custom-fields#legal_entity
 */
namespace Ufee\AmoV4\Models\EntityCfields;

use Ufee\AmoV4\Enums\CustomFields\LegalEntityTypeEnum;

class LegalEntityField extends EntityField
{
	/** @var list<string> */
	private const KEYS = [
		'name',
		'entity_type',
		'address',
		'real_address',
		'bank_account_number',
		'director',
		'vat_id',
		'tax_registration_reason_code',
		'kpp',
		'bank_code',
		'unp',
		'bin',
		'egrpou',
		'mfo',
		'oked',
		'external_uid',
	];

	/**
	 * Replace with one legal entity, or a list of entities
	 * @param array|object $value
	 * @return static
	 */
	public function setValue($value)
	{
		if ($this->isEntitiesList($value)) {
			return $this->setValues($value);
		}
		$normalized = $this->normalizeValue($value);
		$this->assertName($normalized);
		$this->data->values = [(object)['value' => $normalized]];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Append legal entity (name is required)
	 * @param array|object $value
	 * @return static
	 */
	public function addValue($value)
	{
		$normalized = $this->normalizeValue($value);
		$this->assertName($normalized);
		if (!isset($this->data->values) || !is_array($this->data->values)) {
			$this->data->values = [];
		}
		$this->data->values[] = (object)['value' => $normalized];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Replace all legal entities
	 * @param array $values list of entity arrays/objects
	 * @return static
	 */
	public function setValues(array $values)
	{
		$this->data->values = [];
		foreach ($values as $value) {
			$normalized = $this->normalizeValue($value);
			$this->assertName($normalized);
			$this->data->values[] = (object)['value' => $normalized];
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * GET returns a union of country-specific keys (often empty). PATCH schema is
	 * account-country specific and rejects unexpected keys with FieldNotExpected.
	 * @return object
	 */
	public function getRawData()
	{
		$raw = parent::getRawData();
		if (empty($raw->values) || !is_array($raw->values)) {
			return $raw;
		}
		$values = [];
		foreach ($raw->values as $item) {
			$obj = $this->asValueObject($item->value ?? null);
			if ($obj === null) {
				$values[] = $item;
				continue;
			}
			$values[] = (object)['value' => $this->toApiValue($obj)];
		}
		$raw->values = $values;
		return $raw;
	}

	/**
	 * Legal entities as arrays of known keys
	 * @return list<array<string, mixed>>
	 */
	public function toArray(): array
	{
		$result = [];
		foreach ($this->data->values ?? [] as $item) {
			$obj = $this->asValueObject($item->value ?? null);
			if ($obj === null) {
				continue;
			}
			$result[] = $this->valueToArray($obj);
		}
		return $result;
	}

	public function getName()
	{
		return $this->getProp('name');
	}

	public function setName($value)
	{
		return $this->setProp('name', $value);
	}

	public function getEntityType()
	{
		$type = $this->getProp('entity_type');
		return $type === null ? null : (int)$type;
	}

	/**
	 * @param int $type LegalEntityTypeEnum::INDIVIDUAL|LEGAL
	 * @return static
	 */
	public function setEntityType(int $type)
	{
		if (!LegalEntityTypeEnum::has($type)) {
			throw new \InvalidArgumentException(
				sprintf('Unknown legal_entity entity_type %d, allowed: %s', $type, implode(', ', LegalEntityTypeEnum::values()))
			);
		}
		return $this->setProp('entity_type', $type);
	}

	public function getAddress()
	{
		return $this->getProp('address');
	}

	public function setAddress($value)
	{
		return $this->setProp('address', $value);
	}

	public function getRealAddress()
	{
		return $this->getProp('real_address');
	}

	public function setRealAddress($value)
	{
		return $this->setProp('real_address', $value);
	}

	public function getBankAccountNumber()
	{
		return $this->getProp('bank_account_number');
	}

	public function setBankAccountNumber($value)
	{
		return $this->setProp('bank_account_number', $value);
	}

	public function getDirector()
	{
		return $this->getProp('director');
	}

	public function setDirector($value)
	{
		return $this->setProp('director', $value);
	}

	public function getVatId()
	{
		return $this->getProp('vat_id');
	}

	public function setVatId($value)
	{
		return $this->setProp('vat_id', $value);
	}

	public function getTaxRegistrationReasonCode()
	{
		return $this->getProp('tax_registration_reason_code');
	}

	public function setTaxRegistrationReasonCode($value)
	{
		return $this->setProp('tax_registration_reason_code', $value);
	}

	public function getKpp()
	{
		return $this->getProp('kpp');
	}

	public function setKpp($value)
	{
		return $this->setProp('kpp', $value);
	}

	public function getBankCode()
	{
		return $this->getProp('bank_code');
	}

	public function setBankCode($value)
	{
		return $this->setProp('bank_code', $value);
	}

	public function getUnp()
	{
		return $this->getProp('unp');
	}

	public function setUnp($value)
	{
		return $this->setProp('unp', $value);
	}

	public function getBin()
	{
		return $this->getProp('bin');
	}

	public function setBin($value)
	{
		return $this->setProp('bin', $value);
	}

	public function getEgrpou()
	{
		return $this->getProp('egrpou');
	}

	public function setEgrpou($value)
	{
		return $this->setProp('egrpou', $value);
	}

	public function getMfo()
	{
		return $this->getProp('mfo');
	}

	public function setMfo($value)
	{
		return $this->setProp('mfo', $value);
	}

	public function getOked()
	{
		return $this->getProp('oked');
	}

	public function setOked($value)
	{
		return $this->setProp('oked', $value);
	}

	public function getExternalUid()
	{
		return $this->getProp('external_uid');
	}

	public function setExternalUid($value)
	{
		return $this->setProp('external_uid', $value);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	protected function getProp(string $key)
	{
		$value = $this->getValueObject();
		if ($value === null || !property_exists($value, $key)) {
			return null;
		}
		return $value->{$key};
	}

	/**
	 * Upsert one property into value object
	 * @param string $key
	 * @param mixed $propValue
	 * @return static
	 */
	protected function setProp(string $key, $propValue)
	{
		$current = $this->getValueObject();
		$value = $current ? clone $current : (object)[];
		$value->{$key} = $propValue;
		if ($key !== 'name' && (!isset($value->name) || $value->name === '' || $value->name === null)) {
			throw new \InvalidArgumentException('legal_entity requires name before setting other properties');
		}
		if ($key === 'name' && ($propValue === '' || $propValue === null)) {
			throw new \InvalidArgumentException('legal_entity requires name');
		}
		if (!isset($this->data->values) || !is_array($this->data->values)) {
			$this->data->values = [];
		}
		$this->data->values[0] = (object)['value' => $value];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * @return object|null
	 */
	protected function getValueObject(): ?object
	{
		return $this->asValueObject($this->getValue());
	}

	/**
	 * @param mixed $value
	 * @return object|null
	 */
	protected function asValueObject($value): ?object
	{
		if ($value === null || $value === '') {
			return null;
		}
		if (is_array($value)) {
			return (object)$value;
		}
		if (is_object($value)) {
			return $value;
		}
		return null;
	}

	/**
	 * @param object $value
	 */
	protected function assertName(object $value): void
	{
		if (!isset($value->name) || $value->name === '' || $value->name === null) {
			throw new \InvalidArgumentException('legal_entity requires name');
		}
	}

	/**
	 * @param mixed $value
	 */
	protected function isEntitiesList($value): bool
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
	 * @param object $value
	 * @return array<string, mixed>
	 */
	protected function valueToArray(object $value): array
	{
		$result = [];
		foreach (self::KEYS as $key) {
			if (property_exists($value, $key)) {
				$result[$key] = $value->{$key};
			}
		}
		return $result;
	}

	/**
	 * @param array|object $value
	 * @return object
	 */
	protected function normalizeValue($value): object
	{
		if (is_object($value)) {
			$value = (array)$value;
		}
		if (!is_array($value)) {
			throw new \InvalidArgumentException('legal_entity setValue() expects array or object');
		}
		$result = (object)[];
		foreach (self::KEYS as $key) {
			if (!array_key_exists($key, $value)) {
				continue;
			}
			$prop = $value[$key];
			if ($key === 'entity_type') {
				$type = (int)$prop;
				if (!LegalEntityTypeEnum::has($type)) {
					throw new \InvalidArgumentException(
						sprintf('Unknown legal_entity entity_type %d, allowed: %s', $type, implode(', ', LegalEntityTypeEnum::values()))
					);
				}
				$result->entity_type = $type;
				continue;
			}
			$result->{$key} = $prop;
		}
		return $result;
	}

	/**
	 * @param object $value
	 * @return object
	 */
	protected function toApiValue(object $value): object
	{
		$result = (object)[];
		foreach (self::KEYS as $key) {
			if (!property_exists($value, $key)) {
				continue;
			}
			$prop = $value->{$key};
			if ($prop === null || $prop === '') {
				continue;
			}
			if ($key === 'entity_type') {
				$result->entity_type = (int)$prop;
				continue;
			}
			$result->{$key} = $prop;
		}
		return $result;
	}
}
