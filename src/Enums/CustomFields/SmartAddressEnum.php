<?php
/**
 * enum_code / enum_id для поля smart_address
 *
 * @see https://www.amocrm.ru/developers/content/crm_platform/custom-fields#smart_address
 */
namespace Ufee\AmoV4\Enums\CustomFields;

final class SmartAddressEnum
{
	/** Первая строка адреса */
	public const ADDRESS_LINE_1 = 'address_line_1';
	/** Вторая строка адреса */
	public const ADDRESS_LINE_2 = 'address_line_2';
	/** Город */
	public const CITY = 'city';
	/** Регион */
	public const STATE = 'state';
	/** Почтовый индекс */
	public const ZIP = 'zip';
	/** Страна */
	public const COUNTRY = 'country';

	/** @var array<string, int> */
	private const CODE_TO_ID = [
		self::ADDRESS_LINE_1 => 1,
		self::ADDRESS_LINE_2 => 2,
		self::CITY => 3,
		self::STATE => 4,
		self::ZIP => 5,
		self::COUNTRY => 6,
	];

	/**
	 * Допустимые enum_code
	 * @return list<string>
	 */
	public static function values(): array
	{
		return array_keys(self::CODE_TO_ID);
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function has(string $code): bool
	{
		return isset(self::CODE_TO_ID[$code]);
	}

	/**
	 * enum_id по enum_code
	 * @param string $code
	 * @return int|null
	 */
	public static function idByCode(string $code): ?int
	{
		return self::CODE_TO_ID[$code] ?? null;
	}

	/**
	 * enum_code по enum_id
	 * @param int $id
	 * @return string|null
	 */
	public static function codeById(int $id): ?string
	{
		$code = array_search($id, self::CODE_TO_ID, true);
		return $code === false ? null : $code;
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public static function hasId(int $id): bool
	{
		return self::codeById($id) !== null;
	}
}
