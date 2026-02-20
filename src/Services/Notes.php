<?php
/**
 * amoCRM API client Notes service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class Notes extends Service
{
	protected $api_path = '/api/v4/{entity}/notes';
	protected $api_entity_path = '/api/v4/{entity}/notes';
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
        $this->api_entity_path = str_replace('{entity}', current($args), $this->api_entity_path);
        $entity_key = join('/', $args);
		$this->api_path = str_replace('{entity}', $entity_key, $this->api_path);
		$this->_boot();
	}
	
    /**
     * Set note pinned
	 * @param integer $note_id
	 * @return bool
     */
    public function pin(int $note_id)
    {
		$query = $this->instance->query('POST', $this->api_entity_path.'/'.$note_id.'/pin');
		$query->execute();
		return $query->response->getCode() === 204;
	}
	
    /**
     * Unpin note
	 * @param integer $note_id
	 * @return bool
     */
    public function unpin(int $note_id)
    {
		$query = $this->instance->query('POST', $this->api_entity_path.'/'.$note_id.'/unpin');
		$query->execute();
		return $query->response->getCode() === 204;
	}
}
