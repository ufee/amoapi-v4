<?php
/**
 * amoCRM API client Base service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Models\Model;
use Ufee\AmoV4\Collections\Collection;
use Ufee\AmoV4\Api\Paginate;

/**
 * Base service for amoCRM entities
 * 
 * This class is designed to be extended. Subclasses must override:
 * - $api_path (e.g. '/api/v4/leads')
 * - $entity_key (e.g. 'leads')
 * - $entity_model (e.g. Lead::class)
 * - $entity_collection (e.g. leads::class)
 * 
 * @property \Ufee\AmoV4\ApiClient $instance
 */
class Service
{
	protected static $_service_instances = [];
	protected $client_id;
	
	protected $api_path = '/api/v4/{entity}';
	protected $entity_key = '{entities}';
	
	protected $entity_model = '\Ufee\AmoV4\Model';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Collection';
	
	protected $query_args = [
		'limit' => 250
	];
		
    /**
     * Constructor
	 * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client_id = $client->client_id;
		$this->_boot();
	}
	
    /**
     * Service on load
	 * @return void
     */
	protected function _boot() {}
	
    /**
     * Create new entity
	 * @param array $fields
	 * @return Model
     */
	public function create(array $fields = [])
	{
		$entity_model = $this->entity_model;
		return new $entity_model($fields, $this);
	}
	
	/**
	 * Get entities collection
	 * @param array $rows raw data
	 * @return Collection
	 */
	public function createCollection(array $rows = [])
	{
		$collection = $this->entity_collection;
		$entity_model = $this->entity_model;
		
		foreach($rows as &$row) {
			$row = new $entity_model((array)$row, $this);
		}
		return new $collection($rows);
	}
	
    /**
     * Find entities by id
	 * @param int|array $elem_id - ir or ids
	 * @param array $with
	 * @return Model|null
     */
	public function find($elem_id, $with = [])
	{
		if (!is_int($elem_id) && !is_array($elem_id)) {
			throw new \InvalidArgumentException('Element ID must be integer or array of integers');
		}
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		if (is_int($elem_id)) {
			$query = $this->instance->query('GET', $this->api_path.'/'.$elem_id);
			$query->setArgs($query_args);
			$query->execute();
			
			if (in_array($query->response->getCode(), [204, 404])) {
				return null;
			}
			$row = $query->response->validated();
			$entity_model = $this->entity_model;
			return new $entity_model((array)$row, $this);
		} else {
			return $this->filter(['id' => $elem_id], $with)->fetchAll();
		}
	}
	
    /**
     * Get all entities
	 * @param array $with
	 * @return Collection
     */
	public function get($with = null)
	{
		if (is_null($with)) {
			$with = [];
		}
		return $this->paginate($with)->fetchAll();
	}
	
    /**
     * Get entities by pages
	 * @param array $with
	 * @return Paginate
     */
	public function paginate(array $with = [])
	{
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		return new Paginate($query, $this);
	}
	
    /**
     * Search entities by query
	 * @param string $phrase
	 * @param array $with
	 * @return Paginate
     */
	public function search(string $phrase, array $with = [])
	{
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->setArgs([
			'query' => $phrase
		]);
		return new Paginate($query, $this);
	}
	
    /**
     * Filter entities by conditions
	 * @param array $conditions
	 * @param array $with
	 * @return Paginate
     */
	public function filter(array $conditions, array $with = [])
	{
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->setArgs([
			'filter' => $conditions
		]);
		return new Paginate($query, $this);
	}
	
    /**
     * Add entities to CRM
	 * @param array|object $data
	 * @return array|object|null
     */
	public function add($data)
	{
		$query = $this->instance->query('POST', $this->api_path);
		
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
     * Update entity in CRM
	 * @param int|array $elem_id
	 * @param object|null $data
	 * @return array|object|null
     */
	public function update($elem_id, $data = null)
	{
		if (is_object($data)) {
			// add one entity
			$query = $this->instance->query('PATCH', $this->api_path.'/'.$elem_id);
			$query->setJsonData($data);
			$query->execute();

			return $query->response->validatedUpdatedEntity($elem_id);
		}
		else if (is_array($elem_id)) {
			// add many entities
			$query = $this->instance->query('PATCH', $this->api_path);
			$data = $elem_id;
			$query->setJsonData($data);
			$query->execute();

			return $query->response->validatedUpdatedEntities($this->entity_key);
		}
	}
	
	/**
	 * Set page rows limit
	 * @param string $value
	 * @return Service
	 */
	public function maxPageRows(int $value)
	{
		$this->query_args['limit'] = $value;
		return $this;
	}
	
	/**
	 * Set results order
	 * @param string $field - id/created_at/updated_at/...
	 * @param string $direction - asc/desc
	 * @return Service
	 */
	public function orderBy(string $field, string $direction = 'asc')
	{
		$this->query_args['order'] = [$field => $direction];
		return $this;
	}
	
	/**
	 * Set with parameter
	 * @param array $values
	 * @return Service
	 */
	public function with(array $values)
	{
		$this->query_args['with'] = join(',', $values);
		return $this;
	}
	
	
	/**
	 * Get query arg value
	 * @param string $key
	 * @return mixed
	 */
	public function getQueryArg(string $key)
	{
		return $this->query_args[$key] ?? null;
	}
	
	/**
	 * Set query arg value
	 * @param string $key
	 * @param string $value
	 * @return Service
	 */
	public function setQueryArg(string $key, $value)
	{
		$this->query_args[$key] = $value;
		return $this;
	}
	
	/**
	 * Set query args values
	 * @param array $args
	 * @return Service
	 */
	public function setQueryArgs(array $args)
	{
		$this->query_args = $args;
		return $this;
	}
	
    /**
     * Get api method
	 * @param string $target
     */
	public function __get($target)
	{
		if ($target === 'instance') {
			return ApiClient::getInstance($this->client_id);
		}
		else if (!isset($this->{$target})) {
			throw new \Exception('Invalid Service field: '.$target);
		}
		return $this->{$target};
	}
}
