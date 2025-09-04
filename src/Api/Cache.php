<?php
/**
 * amoCRM API client Cache
 */
namespace Ufee\AmoV4\Api;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Api\Cache\AbstractStorage;
use Ufee\AmoV4\Api\Cache\FileStorage;
use Ufee\AmoV4\Api\Cache\RedisStorage;
use Ufee\AmoV4\Models;
use Ufee\AmoV4\Collections;

/**
 * Cache for amoCRM entities
 * @property \Ufee\AmoV4\ApiClient $instance
 */
class Cache
{
	protected $client_id;
	protected $storage;
	
	protected $ttl = [
		'account'      => 3600,
		'users'        => 1800,
		'userGroups'   => 3600,
		'customFields' => 1800,
		'taskTypes'    => 3600,
		'eventTypes'   => 86400
	];

    /**
     * Constructor
	 * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client_id = $client->client_id;
		$this->setStorage(new FileStorage($client, ['path' => AMOV4API_ROOT . '/Temp']));
	}
	
    /**
     * Get cached Account
	 * @param array $with
	 * @return Models\Account
     */
	public function account(array $with = [])
	{
		$key = $with === [] ? 'account' : 'account-'.join('_', $with);
		$ttl = $this->ttl['account'];
		
		if (!$model = $this->storage->get($key)) {
			$model = $this->instance->account()->get($with);
			$this->storage->set($key, $model, $ttl);
		}
		return $model;
	}

    /**
     * Get cached Users
	 * @param array $with
	 * @return Collections\Users
     */
	public function users(array $with = [])
	{
		$key = $with === [] ? 'users' : 'users-'.join('_', $with);
		$ttl = $this->ttl['users'];
		
		if (!$models = $this->storage->get($key)) {
			$models = $this->instance->users()->get($with);
			$this->storage->set($key, $models, $ttl);
		}
		return $models;
	}
	
    /**
     * Get cached User
	 * @param int $user_id
	 * @param array $with
	 * @return Models\User|null
     */
	public function user(int $user_id, array $with = [])
	{
		return $this->users($with)->where('id', $user_id)->first();
	}
	
    /**
     * Get cached User Groups
	 * @return Collections\Collection
     */
	public function userGroups()
	{
		$key = 'userGroups';
		$ttl = $this->ttl['userGroups'];
		
		if (!$models = $this->storage->get($key)) {
			$models = $this->account()->userGroups;
			$this->storage->set($key, $models, $ttl);
		}
		return $models;
	}
	
    /**
     * Get cached Task Types
	 * @return Collections\Collection
     */
	public function taskTypes()
	{
		$key = 'taskTypes';
		$ttl = $this->ttl['taskTypes'];
		
		if (!$models = $this->storage->get($key)) {
			$models = $this->account()->taskTypes;
			$this->storage->set($key, $models, $ttl);
		}
		return $models;
	}
	
    /**
     * Get cached Event Types
	 * @param string|null $lang
	 * @return Collections\Collection
     */
	public function eventTypes($lang = null)
	{
		if (is_null($lang)) {
			$lang = $this->instance->getParam('lang');
		}
		$key = 'eventTypes-'.$lang;
		$ttl = $this->ttl['eventTypes'];
		
		if (!$models = $this->storage->get($key)) {
			$models = $this->instance->events()->types($lang);
			$this->storage->set($key, $models, $ttl);
		}
		return $models;
	}
	
    /**
     * Get cached Custom Fields
	 * Dynamic args
	 * @return Collections\CustomFields
     */
	public function customFields()
	{
		$args = func_get_args();
		if ($args === []) {
			throw new \InvalidArgumentException('Required entity key in args');
		}
		$key = 'customFields-'.join('_', $args);
		$ttl = $this->ttl['customFields'];
		
		if (!$models = $this->storage->get($key)) {
			$models = $this->instance->customFields(...$args)->get();
			$this->storage->set($key, $models, $ttl);
		}
		return $models;
	}
	
	/**
	 * Set cache ttl
	 * @param array $ttls
	 * @return Cache
	 */
	public function setTtl(array $ttls)
	{
		foreach($ttls as $key=>$val) {
			$this->ttl[$key] = $val;
		}
		return $this;
	}
	
	/**
	 * Clear cache
	 * @param string|null $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		return $this->storage->clear($key);
	}
	
	/**
	 * Set file storage handler
	 * @param string $path
	 * @param array $options
	 * @return Cache
	 */
	public function setStorageFiles(string $path, array $options = [])
	{
		$options = array_merge(['path' => $path], $options);
		$this->setStorage(
			new FileStorage($this->instance, $options)
		);
		return $this;
	}
	
	/**
	 * Set redis storage handler
	 * @param \Redis $connection
	 * @return Cache
	 */
	public function setStorageRedis(\Redis $connection)
	{
		$this->setStorage(
			new RedisStorage($this->instance, ['connection' => $connection])
		);
		return $this;
	}

	/**
	 * Set cache storage handler
	 * @param AbstractStorage $storage
	 * @return Cache
	 */
	public function setStorage(AbstractStorage $storage)
	{
		$this->storage = $storage;
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
			throw new \Exception('Invalid Cache field: '.$target);
		}
		return $this->{$target};
	}
}
