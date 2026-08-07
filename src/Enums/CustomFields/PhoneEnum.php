<?php
/**
 * enum_code для поля PHONE (multitext)
 */
namespace Ufee\AmoV4\Enums\CustomFields;

final class PhoneEnum
{
	/** Код поля */
	public const CODE = 'PHONE';

	/** Рабочий */
	public const WORK = 'WORK';
	/** Рабочий прямой */
	public const WORKDD = 'WORKDD';
	/** Мобильный */
	public const MOB = 'MOB';
	/** Факс */
	public const FAX = 'FAX';
	/** Домашний */
	public const HOME = 'HOME';
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
			self::WORKDD,
			self::MOB,
			self::FAX,
			self::HOME,
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
