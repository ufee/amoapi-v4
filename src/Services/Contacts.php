<?php
/**
 * amoCRM API client Contacts service
 */
namespace Ufee\AmoV4\Services;

class Contacts extends Service
{
	use Traits\SearchByName;
	use Traits\SearchByCustomField;
	use Traits\SearchByEmail;
	use Traits\SearchByPhone;
	use Traits\Cfields;
	use Traits\Notes;

	const PHONE_RU_MOB = 'ru_mob';

	/**
	 * Добавляет в ответ связанные с контактами элементы списков
	 */
	public const CATALOG_ELEMENTS = 'catalog_elements';
	/**
	 * Добавляет в ответ связанные с контактами сделки
	 */
	public const LEADS = 'leads';
	/**
	 * Добавляет в ответ связанных с контактами покупателей
	 */
	public const CUSTOMERS = 'customers';

	protected $api_path = '/api/v4/contacts';
	protected $entity_key = 'contacts';

	protected $entity_model = '\Ufee\AmoV4\Models\Contact';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Contacts';

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withAll(): array
	{
		return [
			self::CATALOG_ELEMENTS,
			self::LEADS,
			self::CUSTOMERS,
		];
	}
}
