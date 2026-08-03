<?php
/**
 * amoCRM API client Custom Fields service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class CustomFields extends Service
{
	protected $api_path = '/api/v4/{entity}/custom_fields';
	protected $entity_key = 'custom_fields';
	
	protected $entity_model = '\Ufee\AmoV4\Models\AccountCfield';
	protected $entity_collection = '\Ufee\AmoV4\Collections\CustomFields';
	
    /**
     * Constructor
	 * @param ApiClient $client
	 * @param array $args - entity type, for catalogs also catalog_id
     */
    public function __construct(ApiClient $client, array $args)
    {
        $this->client_id = $client->client_id;

		if (empty($args[0])) {
			throw new \InvalidArgumentException('CustomFields Service required entity argument');
		}

		$entity = (string)$args[0];

		if ($entity === 'catalogs') {
			if (empty($args[1]) || !(int)$args[1]) {
				throw new \InvalidArgumentException('CustomFields Service for catalogs required catalog_id argument');
			}
			$this->api_path = '/api/v4/catalogs/' . (int)$args[1] . '/custom_fields';
		} else {
			$this->api_path = str_replace('{entity}', $entity, $this->api_path);
		}

		$this->_boot();
	}
}
