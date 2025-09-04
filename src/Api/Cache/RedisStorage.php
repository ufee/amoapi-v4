<?php
/**
 * amoCRM API client Cache - Redis
 */
namespace Ufee\AmoV4\Api\Cache;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Base\Models\QueryModel;

class RedisStorage extends AbstractStorage
{
	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $options
	 */
	public function __construct(ApiClient $client, array $options)
	{
		parent::__construct($client, $options);

		if (empty($this->options['connection']) || !$this->options['connection'] instanceof \Redis) {
			throw new \InvalidArgumentException('Redis Storage options[connection] must be instance of \Redis');
		}
	}
	
	/**
	 * Check cached data
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key)
	{
		if (parent::has($key)) {
			return true;
		}
		return $this->options['connection']->exists($this->domain . ':' . $this->client_id.':'.$key);
	}

	/**
	 * Get cached data
	 * @param string $key
	 * @return object|null
	 */
	public function get(string $key)
	{
		if ($payload = parent::get($key)) {
			return $payload;
		}
		return $this->options['connection']->get($this->domain . ':' . $this->client_id.':'.$key);
	}

	/**
	 * Set cached data
	 * @param string $key
	 * @param object $object
	 * @param int $ttl
	 * @return bool
	 */
	public function set(string $key, object $object, int $ttl = 60)
	{
		parent::set($key, $object, $ttl);
		
		return $this->options['connection']->setEx($this->domain . ':' . $this->client_id.':'.$key, $ttl, $object);
	}

	/**
	 * Flush cache queries
	 * @param string|null $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		parent::clear($key);
		
		if (is_string($key)) {
			$this->options['connection']->del($this->domain . ':' . $this->client_id.':'.$key);
		} else {
			$keys = $this->options['connection']->keys($this->domain . ':' . $this->client_id.':*');
			foreach ($keys as $key) {
				$this->options['connection']->del($key);
			}
		}
		return true;
	}
}
