<?php
/**
 * amoCRM Custom Entity Custom Field model (legal_entity)
 *
 * @see https://www.amocrm.ru/developers/content/crm_platform/custom-fields#legal_entity
 */
namespace Ufee\AmoV4\Models\EntityCfields;

use Ufee\AmoV4\Enums\CustomFields\LegalEntityTypeEnum;

class JurField extends EntityField
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
	 * Replace legal_entity value (name is required)
	 * @param array|object $value
	 * @return static
	 */
	public function setValue($value)
	{
		$normalized = $this->normalizeValue($value);
		if (!isset($normalized->name) || $normalized->name === '' || $normalized->name === null) {
			throw new \InvalidArgumentException('legal_entity requires name');
		}
		$this->data->values = [(object)['value' => $normalized]];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Components as array
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		$value = $this->getValueObject();
		if ($value === null) {
			return [];
		}
		$result = [];
		foreach (self::KEYS as $key) {
			if (property_exists($value, $key)) {
				$result[$key] = $value->{$key};
			}
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
		$value = $this->getValueObject() ?? (object)[];
		$value->{$key} = $propValue;
		if ($key !== 'name' && (!isset($value->name) || $value->name === '' || $value->name === null)) {
			throw new \InvalidArgumentException('legal_entity requires name before setting other properties');
		}
		if ($key === 'name' && ($propValue === '' || $propValue === null)) {
			throw new \InvalidArgumentException('legal_entity requires name');
		}
		$this->data->values = [(object)['value' => $value]];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * @return object|null
	 */
	protected function getValueObject(): ?object
	{
		$value = $this->getValue();
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
}
