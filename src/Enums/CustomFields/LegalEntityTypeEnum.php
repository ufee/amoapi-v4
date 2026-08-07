<?php
/**
 * entity_type для поля legal_entity
 *
 * @see https://www.amocrm.ru/developers/content/crm_platform/custom-fields#legal_entity
 */
namespace Ufee\AmoV4\Enums\CustomFields;

final class LegalEntityTypeEnum
{
	/** Частное лицо */
	public const INDIVIDUAL = 1;
	/** Юридическое лицо */
	public const LEGAL = 2;

	/**
	 * @return list<int>
	 */
	public static function values(): array
	{
		return [
			self::INDIVIDUAL,
			self::LEGAL,
		];
	}

	/**
	 * @param int $type
	 * @return bool
	 */
	public static function has(int $type): bool
	{
		return in_array($type, self::values(), true);
	}
}
