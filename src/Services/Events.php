<?php
/**
 * amoCRM API client Events service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\Collections;

class Events extends Service
{
	/**
	 * Если сущностью события является контакт, то помимо его ID, вы получите и название
	 */
	public const CONTACT_NAME = 'contact_name';

	/**
	 * Если сущностью события является сделка, то помимо её ID, вы получите и название
	 */
	public const LEAD_NAME = 'lead_name';

	/**
	 * Если сущностью события является компания, то помимо её ID, вы получите и название
	 */
	public const COMPANY_NAME = 'company_name';

	/**
	 * Если сущностью события является покупатель, то помимо его ID, вы получите и название
	 */
	public const CUSTOMER_NAME = 'customer_name';

	/**
	 * Если сущностью события является элемент каталога, то помимо его ID, вы получите и название
	 */
	public const CATALOG_ELEMENT_NAME = 'catalog_element_name';

	/**
	 * Если сущностью события является элемент каталога, то помимо ID каталога, к которому он относится, вы получите и название каталога
	 */
	public const CATALOG_NAME = 'catalog_name';

	protected $api_path = '/api/v4/events';
	protected $entity_key = 'events';

	protected $entity_model = '\Ufee\AmoV4\Models\Event';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Events';

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::CONTACT_NAME,
			self::LEAD_NAME,
			self::COMPANY_NAME,
			self::CUSTOMER_NAME,
			self::CATALOG_ELEMENT_NAME,
			self::CATALOG_NAME,
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
     * Get task type
	 * @param string|null $lang – en, es, ru, pt
	 * @return object|null
     */
    public function types($lang = null)
    {
		if (is_null($lang)) {
			$lang = $this->instance->getParam('lang');
		}
		$query = $this->instance->query('GET', $this->api_path.'/types');
		$query->setArgs([
			'language_code' => $lang
		]);
		$query->execute();
		
		return new Collections\Collection($query->response->validatedEntities('events_types'));
	}
}
