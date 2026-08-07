<?php
/**
 * amoCRM Custom Entity Custom Field model (multitext: PHONE, EMAIL, …)
 */
namespace Ufee\AmoV4\Models\EntityCfields;

use Ufee\AmoV4\Enums\CustomFields\EmailEnum;
use Ufee\AmoV4\Enums\CustomFields\PhoneEnum;

class MultiTextField extends EntityField
{
	/**
	 * Set single multitext value
	 * @param mixed $value
	 * @param int|string|null $enum enum_id or enum_code (PhoneEnum::*, EmailEnum::*); для PHONE/EMAIL по умолчанию WORK
	 * @return static
	 */
	public function setValue($value, $enum = null)
	{
		$this->data->values = [$this->makeItem($value, $enum)];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Append multitext value
	 * @param mixed $value
	 * @param int|string|null $enum enum_id or enum_code; для PHONE/EMAIL по умолчанию WORK
	 * @return static
	 */
	public function addValue($value, $enum = null)
	{
		if (!isset($this->data->values) || !is_array($this->data->values)) {
			$this->data->values = [];
		}
		$this->data->values[] = $this->makeItem($value, $enum);
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * Replace all multitext values
	 * Accepts strings or items with value + enum_id/enum_code:
	 *   ['a', 'b']
	 *   [['value' => '+7…', 'enum_code' => PhoneEnum::MOB], …]
	 *   [(object)['value' => '…', 'enum_id' => 1], …]
	 * @param array $values
	 * @return static
	 */
	public function setValues(array $values)
	{
		$this->data->values = [];
		foreach ($values as $item) {
			if (is_array($item) || is_object($item)) {
				$row = (object)$item;
				$value = $row->value ?? null;
				$enum = $row->enum_id ?? $row->enum_code ?? $row->enum ?? null;
				$this->data->values[] = $this->makeItem($value, $enum);
				continue;
			}
			$this->data->values[] = $this->makeItem($item, null);
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
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
		return $this->data->values[0]->enum_code ?? null;
	}

	/**
	 * Get enum_id of first value
	 * @return integer|null
	 */
	public function getEnum()
	{
		if (!isset($this->data->values[0])) {
			return null;
		}
		return $this->data->values[0]->enum_id ?? null;
	}

	/**
	 * Get enum_codes of all values
	 * @return array
	 */
	public function getEnumCodes()
	{
		$codes = [];
		foreach ($this->data->values as $setted) {
			$codes[] = $setted->enum_code ?? null;
		}
		return $codes;
	}

	/**
	 * Get enum_ids of all values
	 * @return array
	 */
	public function getEnums()
	{
		$enums = [];
		foreach ($this->data->values as $setted) {
			$enums[] = $setted->enum_id ?? null;
		}
		return $enums;
	}

	/**
	 * Default enum_code for PHONE/EMAIL fields
	 * @return string|null
	 */
	protected function defaultEnum()
	{
		$code = $this->data->field_code ?? null;
		if ($code === PhoneEnum::CODE) {
			return PhoneEnum::WORK;
		}
		if ($code === EmailEnum::CODE) {
			return EmailEnum::WORK;
		}
		return null;
	}

	/**
	 * Build API value item
	 * @param mixed $value
	 * @param int|string|null $enum
	 * @return object
	 */
	protected function makeItem($value, $enum = null): object
	{
		$item = (object)['value' => $value];
		if ($enum === null || $enum === '') {
			$enum = $this->defaultEnum();
		}
		if ($enum === null || $enum === '') {
			return $item;
		}
		if (is_int($enum) || (is_string($enum) && ctype_digit($enum))) {
			$item->enum_id = (int)$enum;
			return $item;
		}
		$enum = (string)$enum;
		$this->assertEnumCode($enum);
		$item->enum_code = $enum;
		return $item;
	}

	/**
	 * Validate enum_code against PhoneEnum / EmailEnum
	 * @param string $enum
	 * @return void
	 */
	protected function assertEnumCode(string $enum): void
	{
		$code = $this->data->field_code ?? null;
		if ($code === PhoneEnum::CODE && !PhoneEnum::has($enum)) {
			throw new \InvalidArgumentException(
				sprintf('Unknown phone enum "%s", allowed: %s', $enum, implode(', ', PhoneEnum::values()))
			);
		}
		if ($code === EmailEnum::CODE && !EmailEnum::has($enum)) {
			throw new \InvalidArgumentException(
				sprintf('Unknown email enum "%s", allowed: %s', $enum, implode(', ', EmailEnum::values()))
			);
		}
	}
}
