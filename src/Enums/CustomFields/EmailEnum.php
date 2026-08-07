<?php
/**
 * enum_code для поля EMAIL (multitext)
 */
namespace Ufee\AmoV4\Enums\CustomFields;

final class EmailEnum
{
	/** Код поля */
	public const CODE = 'EMAIL';

	/** Рабочий */
	public const WORK = 'WORK';
	/** Личный */
	public const PRIV = 'PRIV';
	/** Другой */
	public const OTHER = 'OTHER';

	/**
	 * Допустимые enum_code
	 * @return list<string>
	 */
	public static function values(): array
	{
		return [
			self::WORK,
			self::PRIV,
			self::OTHER,
		];
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function has(string $code): bool
	{
		return in_array($code, self::values(), true);
	}
}
