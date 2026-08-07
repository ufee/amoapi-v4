<?php
/**
 * amoCRM Custom Entity Custom Field model (smart_address)
 *
 * @see https://www.amocrm.ru/developers/content/crm_platform/custom-fields#smart_address
 */
namespace Ufee\AmoV4\Models\EntityCfields;

use Ufee\AmoV4\Enums\CustomFields\SmartAddressEnum;

class SmartAddressField extends EntityField
{
	/**
	 * Set / upsert one address component
	 * @param mixed $value
	 * @param int|string $enum enum_id or enum_code (SmartAddressEnum::*)
	 * @return static
	 */
	public function setValue($value, $enum = null)
	{
		if ($enum === null || $enum === '') {
			throw new \InvalidArgumentException(
				'smart_address setValue requires enum_id or enum_code (' . implode(', ', SmartAddressEnum::values()) . ')'
			);
		}
		$this->upsertItem($value, $enum);
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Replace all components.
	 * Accepts:
	 *   [SmartAddressEnum::CITY => 'Москва', 'country' => 'RU']
	 *   [['value' => '…', 'enum_code' => 'city'], …]
	 *   [(object)['value' => '…', 'enum_id' => 3], …]
	 * @param array $values
	 * @return static
	 */
	public function setValues(array $values)
	{
		$this->data->values = [];
		if ($this->isComponentMap($values)) {
			foreach ($values as $enum => $value) {
				$this->data->values[] = $this->makeItem($value, $enum);
			}
		} else {
			foreach ($values as $item) {
				if (is_array($item) || is_object($item)) {
					$row = (object)$item;
					$value = $row->value ?? null;
					$enum = $row->enum_id ?? $row->enum_code ?? $row->enum ?? null;
					if ($enum === null || $enum === '') {
						throw new \InvalidArgumentException(
							'smart_address value item requires enum_id or enum_code'
						);
					}
					$this->data->values[] = $this->makeItem($value, $enum);
					continue;
				}
				throw new \InvalidArgumentException(
					'smart_address setValues expects component map or list of value+enum items'
				);
			}
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Get value of first component, or of a specific enum
	 * @param int|string|null $enum
	 * @return mixed
	 */
	public function getValue($enum = null)
	{
		if ($enum === null || $enum === '') {
			return parent::getValue();
		}
		$item = $this->findItem($enum);
		return $item->value ?? null;
	}

	/**
	 * Components as [enum_code => value]
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		$result = [];
		foreach ($this->data->values ?? [] as $setted) {
			$code = $setted->enum_code ?? null;
			if ($code === null && isset($setted->enum_id)) {
				$code = SmartAddressEnum::codeById((int)$setted->enum_id);
			}
			if ($code === null) {
				continue;
			}
			$result[$code] = $setted->value ?? null;
		}
		return $result;
	}

	/**
	 * Get enum_code of first value
	 * @return string|null
	 */
	public function getEnumCode()
	{
		if (!isset($this->data->values[0])) {
			return null;
		}
		$first = $this->data->values[0];
		if (isset($first->enum_code)) {
			return $first->enum_code;
		}
		if (isset($first->enum_id)) {
			return SmartAddressEnum::codeById((int)$first->enum_id);
		}
		return null;
	}

	/**
	 * Get enum_codes of all values
	 * @return list<string|null>
	 */
	public function getEnumCodes()
	{
		$codes = [];
		foreach ($this->data->values ?? [] as $setted) {
			if (isset($setted->enum_code)) {
				$codes[] = $setted->enum_code;
				continue;
			}
			$codes[] = isset($setted->enum_id)
				? SmartAddressEnum::codeById((int)$setted->enum_id)
				: null;
		}
		return $codes;
	}

	public function setAddressLine1($value)
	{
		return $this->setValue($value, SmartAddressEnum::ADDRESS_LINE_1);
	}

	public function getAddressLine1()
	{
		return $this->getValue(SmartAddressEnum::ADDRESS_LINE_1);
	}

	public function setAddressLine2($value)
	{
		return $this->setValue($value, SmartAddressEnum::ADDRESS_LINE_2);
	}

	public function getAddressLine2()
	{
		return $this->getValue(SmartAddressEnum::ADDRESS_LINE_2);
	}

	public function setCity($value)
	{
		return $this->setValue($value, SmartAddressEnum::CITY);
	}

	public function getCity()
	{
		return $this->getValue(SmartAddressEnum::CITY);
	}

	public function setState($value)
	{
		return $this->setValue($value, SmartAddressEnum::STATE);
	}

	public function getState()
	{
		return $this->getValue(SmartAddressEnum::STATE);
	}

	public function setZip($value)
	{
		return $this->setValue($value, SmartAddressEnum::ZIP);
	}

	public function getZip()
	{
		return $this->getValue(SmartAddressEnum::ZIP);
	}

	public function setCountry($value)
	{
		return $this->setValue($value, SmartAddressEnum::COUNTRY);
	}

	public function getCountry()
	{
		return $this->getValue(SmartAddressEnum::COUNTRY);
	}

	/**
	 * @param mixed $value
	 * @param int|string $enum
	 * @return void
	 */
	protected function upsertItem($value, $enum): void
	{
		if (!isset($this->data->values) || !is_array($this->data->values)) {
			$this->data->values = [];
		}
		$item = $this->makeItem($value, $enum);
		[$matchCode, $matchId] = $this->normalizeEnum($enum);
		foreach ($this->data->values as $i => $setted) {
			$code = $setted->enum_code ?? null;
			$id = isset($setted->enum_id) ? (int)$setted->enum_id : null;
			if (($matchCode !== null && $code === $matchCode)
				|| ($matchId !== null && $id === $matchId)
				|| ($matchCode !== null && $id !== null && SmartAddressEnum::codeById($id) === $matchCode)
				|| ($matchId !== null && $code !== null && SmartAddressEnum::idByCode($code) === $matchId)
			) {
				$this->data->values[$i] = $item;
				return;
			}
		}
		$this->data->values[] = $item;
	}

	/**
	 * @param int|string $enum
	 * @return object|null
	 */
	protected function findItem($enum): ?object
	{
		[$matchCode, $matchId] = $this->normalizeEnum($enum);
		foreach ($this->data->values ?? [] as $setted) {
			$code = $setted->enum_code ?? null;
			$id = isset($setted->enum_id) ? (int)$setted->enum_id : null;
			if (($matchCode !== null && $code === $matchCode)
				|| ($matchId !== null && $id === $matchId)
				|| ($matchCode !== null && $id !== null && SmartAddressEnum::codeById($id) === $matchCode)
				|| ($matchId !== null && $code !== null && SmartAddressEnum::idByCode($code) === $matchId)
			) {
				return $setted;
			}
		}
		return null;
	}

	/**
	 * @param mixed $value
	 * @param int|string $enum
	 * @return object
	 */
	protected function makeItem($value, $enum): object
	{
		$item = (object)['value' => $value];
		if (is_int($enum) || (is_string($enum) && ctype_digit($enum))) {
			$id = (int)$enum;
			if (!SmartAddressEnum::hasId($id)) {
				throw new \InvalidArgumentException(
					sprintf('Unknown smart_address enum_id %d, allowed: 1–6', $id)
				);
			}
			$item->enum_id = $id;
			return $item;
		}
		$code = (string)$enum;
		if (!SmartAddressEnum::has($code)) {
			throw new \InvalidArgumentException(
				sprintf('Unknown smart_address enum "%s", allowed: %s', $code, implode(', ', SmartAddressEnum::values()))
			);
		}
		$item->enum_code = $code;
		return $item;
	}

	/**
	 * @param int|string $enum
	 * @return array{0: ?string, 1: ?int}
	 */
	protected function normalizeEnum($enum): array
	{
		if (is_int($enum) || (is_string($enum) && ctype_digit($enum))) {
			$id = (int)$enum;
			return [SmartAddressEnum::codeById($id), $id];
		}
		$code = (string)$enum;
		return [$code, SmartAddressEnum::idByCode($code)];
	}

	/**
	 * Associative map keyed by enum_code / enum_id
	 * @param array $values
	 * @return bool
	 */
	protected function isComponentMap(array $values): bool
	{
		if ($values === []) {
			return true;
		}
		foreach (array_keys($values) as $key) {
			if (is_int($key) || (is_string($key) && ctype_digit($key))) {
				if (!SmartAddressEnum::hasId((int)$key)) {
					return false;
				}
				continue;
			}
			if (!is_string($key) || !SmartAddressEnum::has($key)) {
				return false;
			}
		}
		return true;
	}
}
