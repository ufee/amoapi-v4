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

	/** Локальный кеш хранилища, null до initialize(): модели держат сервис и клиента, статический кеш не дал бы клиенту освободиться */
	protected $_local = null;

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
		$this->_local = [];
	}

	/**
	 * Check cached data
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key)
	{
		if (is_null($this->_local)) {
			$this->initialize();
		}
		return isset($this->_local[$key]);
	}

	/**
	 * Get cached data
	 * @param string $key
	 * @return object|null
	 */
	public function get(string $key)
	{
		if (is_null($this->_local)) {
			$this->initialize();
		}
		$data = $this->_local[$key] ?? null;
		if ($data && $data['expire_at'] < time()) {
			unset($this->_local[$key]);
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
		if (is_null($this->_local)) {
			$this->initialize();
		}
		$data = ['expire_at' => time()+$ttl, 'payload' => $object];
		$this->_local[$key] = $data;

		return true;
	}

	/**
	 * Flush cache queries
	 * @param string|null $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		if (is_null($this->_local)) {
			$this->initialize();
		}
		if (is_string($key)) {
			$this->_local[$key] = null;
		} else {
			$this->_local = [];
		}
		return true;
	}
}
