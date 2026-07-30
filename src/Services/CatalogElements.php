<?php
/**
 * amoCRM API client Catalog elements service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class CatalogElements extends Service
{
	use Traits\SearchByName;

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
	 * Get catalog Custom Fields service
	 * @return CustomFields
	 */
	public function customFields()
	{
		return $this->instance->customFields('catalogs', $this->catalog_id);
	}
}
