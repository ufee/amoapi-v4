<?php
/**
 * amoCRM API client Companies service
 */
namespace Ufee\AmoV4\Services;

class Companies extends Service
{
	use Traits\SearchByName;
	use Traits\SearchByCustomField;
	use Traits\SearchByEmail;
	use Traits\SearchByPhone;
	use Traits\Cfields;
	use Traits\Notes;

	const PHONE_RU_MOB = 'ru_mob';

	/**
	 * Добавляет в ответ связанные с компанией элементы списков
	 */
	public const CATALOG_ELEMENTS = 'catalog_elements';
	/**
	 * Добавляет в ответ связанные с компанией сделки
	 */
	public const LEADS = 'leads';
	/**
	 * Добавляет в ответ связанных с компанией покупателей
	 */
	public const CUSTOMERS = 'customers';
	/**
	 * Добавляет в ответ связанные с компанией контакты
	 */
	public const CONTACTS = 'contacts';

	protected $api_path = '/api/v4/companies';
	protected $entity_key = 'companies';

	protected $entity_model = '\Ufee\AmoV4\Models\Company';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Companies';

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::CATALOG_ELEMENTS,
			self::LEADS,
			self::CUSTOMERS,
			self::CONTACTS,
		];
	}

	/**
	 * Установить все with-параметры обогащения ответа
	 * @return static
	 */
	public function withAll()
	{
		return $this->with(static::withValues());
	}
}
