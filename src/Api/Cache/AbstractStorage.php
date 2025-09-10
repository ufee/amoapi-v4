<?php
/**
 * amoCRM API client Cache interface
 */
namespace Ufee\AmoV4\Api\Cache;
use Ufee\AmoV4\ApiClient;

class AbstractStorage
{
	protected $domain;
	protected $client_id;
	protected $key;
	protected $options;
	
	protected static $_local = [];

	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $options
	 */
	public function __construct(ApiClient $client, array $options = [])
	{
		$this->domain = $client->getIntegration('domain');
		$this->client_id = $client->getIntegration('client_id');
		$this->key = $this->domain . '_' . $this->client_id;
		$this->options = $options;
	}

	/**
	 * Init cache handler
	 * @return void
	 */
	public function initialize()
	{
		static::$_local[$this->key] = [];
	}

	/**
	 * Check cached data
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key)
	{
		if (!array_key_exists($this->key, static::$_local)) {
			$this->initialize();
		}
		return isset(static::$_local[$this->key][$key]);
	}

	/**
	 * Get cached data
	 * @param string $key
	 * @return object|null
	 */
	public function get(string $key)
	{
		if (!array_key_exists($this->key, static::$_local)) {
			$this->initialize();
		}
		$data = static::$_local[$this->key][$key] ?? null;
		if ($data && $data['expire_at'] < time()) {
			unset(static::$_local[$this->key][$key]);
			$data = null;
		}
		return $data ? $data['payload'] : null;
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
		if (!array_key_exists($this->key, static::$_local)) {
			$this->initialize();
		}
		$data = ['expire_at' => time()+$ttl, 'payload' => $object];
		static::$_local[$this->key][$key] = $data;

		return true;
	}

	/**
	 * Flush cache queries
	 * @param string|null $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		if (!array_key_exists($this->key, static::$_local)) {
			$this->initialize();
		}
		if (is_string($key)) {
			static::$_local[$this->key][$key] = null;
		} else {
			static::$_local[$this->key] = [];
		}
		return true;
	}
}
