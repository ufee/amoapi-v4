<?php
/**
 * amoCRM Custom Entity Custom Field model (text, textarea, tracking_data)
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class TextField extends EntityField
{
	/** API accepts at most 256 characters for type=text. */
	public const MAX_LENGTH = 256;
	/** API accepts at most 26000 characters for type=textarea. */
	public const TEXTAREA_MAX_LENGTH = 26000;

	/**
	 * Set cf value
	 * @param mixed $value
	 * @return static
	 */
	public function setValue($value)
	{
		$this->assertValueLength($value);
		return parent::setValue($value);
	}

	/**
	 * Set cf values
	 * @param array $values
	 * @return static
	 */
	public function setValues(array $values)
	{
		foreach ($values as $value) {
			$this->assertValueLength($value);
		}
		return parent::setValues($values);
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	protected function assertValueLength($value): void
	{
		$max = $this->maxLength();
		if ($max === null) {
			return;
		}
		if (!is_scalar($value) && $value !== null) {
			return;
		}
		if (mb_strlen((string)$value) > $max) {
			throw new \InvalidArgumentException(
				sprintf('%s value must not exceed %d characters', $this->data->field_type, $max)
			);
		}
	}

	/**
	 * @return int|null
	 */
	protected function maxLength(): ?int
	{
		$type = $this->data->field_type ?? null;
		if ($type === self::TYPE_TEXT) {
			return self::MAX_LENGTH;
		}
		if ($type === self::TYPE_TEXTAREA) {
			return self::TEXTAREA_MAX_LENGTH;
		}
		return null;
	}
}
