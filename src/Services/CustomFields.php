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
	 * @param array $args
     */
    public function __construct(ApiClient $client, array $args)
    {
        $this->client_id = $client->client_id;
        $entity_key = join('/', $args);
		$this->api_path = str_replace('{entity}', $entity_key, $this->api_path);
		$this->_boot();
	}
}
