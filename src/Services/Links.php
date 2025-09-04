<?php
/**
 * amoCRM API client Webhooks service
 */
namespace Ufee\AmoV4\Services;
use \Ufee\AmoV4\ApiClient;
use \Ufee\AmoV4\Collections;
use \Ufee\AmoV4\Models;
use Ufee\AmoV4\Exceptions;

class Links extends Service
{
	protected $api_path = '/api/v4/{entity}';
	protected $entity_key = 'links';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Link';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Links';
	
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
	
    /**
     * Get entitiy links
	 * @param string $conditions - filter
	 * @return Collections\Links
     */
	public function get($conditions = null)
	{
		$query_args = $this->query_args;
		if (is_array($conditions) && !empty($conditions)) {
			$query_args['filter'] = $conditions;
		}
		$query = $this->instance->query('GET', $this->api_path.'/links');
		$query->setArgs($query_args);
		$query->execute();

		$validated = $query->response->validated();
		if (!property_exists($validated, '_embedded')) {
			throw new Exceptions\AmoException('Invalid API response for entities, embedded not found, code: '.$query->response->getCode(), $query->response->getCode());
		}
		if (property_exists($validated->_embedded, $this->entity_key)) {
			return $this->createCollection($validated->_embedded->{$this->entity_key});
		}
		return $this->createCollection();
	}
	
    /**
     * Add entitiy links
	 * @param array|object $data
	 * @return array|object|null
     */
	public function add($data)
	{
		$query = $this->instance->query('POST', $this->api_path.'/link');
		
		if (is_object($data)) {
			// add one entity
			$query->setJsonData([$data]);
			$query->execute();

			$rows = $query->response->validatedCreatedEntities($this->entity_key);
			return current($rows);
		}
		else if (is_array($data)) {
			// add many entities
			$query->setJsonData($data);
			$query->execute();

			return $query->response->validatedCreatedEntities($this->entity_key);
		}
	}
	
    /**
     * Detach entitiy link
	 * @param array|object $links
	 * @return bool
     */
	public function delete($links)
	{
		if (is_object($links) || is_array($links) && !isset($links[0])) {
			$links = [$links];
		}
		$query = $this->instance->query('POST', $this->api_path.'/unlink');
		$query->setJsonData($links);
		
		$query->execute();
		return $query->response->getCode() === 204;
	}
}
