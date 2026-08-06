<?php
/**
 * amoCRM API client Customers service
 */
namespace Ufee\AmoV4\Services;

class Customers extends Service
{
	use Traits\SearchByName;
	use Traits\Cfields;

	/**
	 * Добавляет в ответ связанные со покупателем элементы списков
	 */
	public const CATALOG_ELEMENTS = 'catalog_elements';

	/**
	 * Добавляет в ответ информацию о связанных с покупателем контактах
	 */
	public const CONTACTS = 'contacts';

	/**
	 * Добавляет в ответ информацию о связанных с покупателем компаниях
	 */
	public const COMPANIES = 'companies';

	protected $api_path = '/api/v4/customers';
	protected $entity_key = 'customers';

	protected $entity_model = '\Ufee\AmoV4\Models\Customer';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Customers';

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::CATALOG_ELEMENTS,
			self::CONTACTS,
			self::COMPANIES,
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

	/**
	 * Get customer segments service
	 * @return CustomerSegments
	 */
	public function segments()
	{
		return $this->instance->customerSegments();
	}
}
