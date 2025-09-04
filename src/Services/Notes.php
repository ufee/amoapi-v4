<?php
/**
 * amoCRM API client Notes service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class Notes extends Service
{
	protected $api_path = '/api/v4/{entity}/notes';
	protected $entity_key = 'notes';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Note';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Notes';
	
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
