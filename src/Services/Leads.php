<?php
/**
 * amoCRM API client Leads service
 */
namespace Ufee\AmoV4\Services;

class Leads extends Service
{
	use Traits\SearchByName;
	use Traits\SearchByCustomField;
	use Traits\Cfields;
	use Traits\Notes;

	/**
	 * Добавляет в ответ связанные со сделками элементы списков
	 */
	public const CATALOG_ELEMENTS = 'catalog_elements';

	/**
	 * Добавляет в ответ свойство, показывающее, изменен ли в последний раз бюджет сделки роботом
	 */
	public const IS_PRICE_MODIFIED_BY_ROBOT = 'is_price_modified_by_robot';

	/**
	 * Добавляет в ответ расширенную информацию по причине отказа
	 */
	public const LOSS_REASON = 'loss_reason';

	/**
	 * Добавляет в ответ информацию о связанных со сделкой контактах
	 */
	public const CONTACTS = 'contacts';

	/**
	 * Если передать данный параметр, то в ответе на запрос метода, вернутся удаленные сделки, которые еще находятся в корзине.
	 * В ответ вы получите модель сделки, у которой доступны дату изменения, ID пользователя сделавшего последнее изменение, её ID и параметр is_deleted = true.
	 */
	public const ONLY_DELETED = 'only_deleted';

	/**
	 * Добавляет в ответ ID источника
	 */
	public const SOURCE_ID = 'source_id';

	/**
	 * Добавляет в ответ информацию об источнике,его ID и название
	 */
	public const SOURCE = 'source';

	protected $api_path = '/api/v4/leads';
	protected $entity_key = 'leads';

	protected $entity_model = '\Ufee\AmoV4\Models\Lead';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Leads';

	/**
	 * Все with-параметры для обогащения ответа (без only_deleted)
	 * @return string[]
	 */
	public static function withAll(): array
	{
		return [
			self::CATALOG_ELEMENTS,
			self::IS_PRICE_MODIFIED_BY_ROBOT,
			self::LOSS_REASON,
			self::CONTACTS,
			self::SOURCE_ID,
			self::SOURCE,
		];
	}

	/**
	 * Get leads loss reasons service
	 * @return LossReasons
	 */
	public function lossReasons()
	{
		return $this->instance->lossReasons();
	}
}
