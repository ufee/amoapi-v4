<?php
/**
 * amoCRM API client Catalog elements service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class CatalogElements extends Service
{
	use Traits\SearchByName;

	/**
	 * При передаче данного параметра, вернется дополнительное свойство invoice_link,
	 * содержащие ссылку на печатную форму счета.
	 * Если передать этот параметр с отличным от списка Счетов списком, то вернется null.
	 */
	public const INVOICE_LINK = 'invoice_link';

	protected $api_path = '/api/v4/catalogs/{catalog_id}/elements';
	protected $entity_key = 'elements';

	protected $entity_model = '\Ufee\AmoV4\Models\CatalogElement';
	protected $entity_collection = '\Ufee\AmoV4\Collections\CatalogElements';

	protected $catalog_id;

	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $args
	 */
	public function __construct(ApiClient $client, array $args)
	{
		if (!$catalog_id = (int)current($args)) {
			throw new \InvalidArgumentException('CatalogElements Service required catalog_id argument');
		}
		$this->client_id = $client->client_id;
		$this->catalog_id = $catalog_id;
		$this->api_path = str_replace('{catalog_id}', $catalog_id, $this->api_path);
		$this->_boot();
	}

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::INVOICE_LINK,
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
	 * Args for custom fields API / cache
	 * @return array
	 */
	public function customFieldsArgs(): array
	{
		return ['catalogs', $this->catalog_id];
	}

	/**
	 * Get catalog Custom Fields service
	 * @return CustomFields
	 */
	public function customFields()
	{
		return $this->instance->customFields(...$this->customFieldsArgs());
	}
}
