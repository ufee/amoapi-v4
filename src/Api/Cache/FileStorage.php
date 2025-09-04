<?php
/**
 * amoCRM API client Cache - files
 */
namespace Ufee\AmoV4\Api\Cache;
use Ufee\AmoV4\ApiClient;

class FileStorage extends AbstractStorage
{
	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $options
	 */
	public function __construct(ApiClient $client, array $options)
	{
		parent::__construct($client, $options);

		if (empty($this->options['path'])) {
			throw new \InvalidArgumentException('File Storage options[path] must be string path');
		}
		$this->options['serialize'] ??= 'serialize';
		if (isset($this->options['serialize']) && !is_callable('serialize')) {
			throw new \InvalidArgumentException('Option serialize must be callable function');
		}
		$this->options['unserialize'] ??= 'unserialize';
		if (isset($this->options['unserialize']) && !is_callable('unserialize')) {
			throw new \InvalidArgumentException('Option unserialize must be callable function');
		}
	}
	
	/**
	 * Init oauth handler
	 * @return void
	 */
	public function initialize()
	{
		parent::initialize();

		if (!file_exists($this->options['path'] . '/' . $this->domain)) {
			mkdir($this->options['path'] . '/' . $this->domain, 0777, true);
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
		return file_exists($this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-'.$key.'.cache');
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
		if (!file_exists($this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-'.$key.'.cache')) {
			return null;
		}
		$contents = file_get_contents($this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-'.$key.'.cache');
		$unserialize = $this->options['unserialize'];
		if ($contents && ($data = $unserialize($contents))) {
			if ($data['expire_at'] < time()) {
				@unlink($this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-'.$key.'.cache');
				$data = null;
			}
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
		parent::set($key, $object, $ttl);
		$data = ['expire_at' => time()+$ttl, 'payload' => $object];
		$serialize = $this->options['serialize'];
		
		return file_put_contents(
			$this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-'.$key.'.cache',
			$serialize($data)
		);
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
			$cache_path = $this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-'.$key.'.cache';
			if (file_exists($cache_path)) {
				@unlink($cache_path);
			}
		} else {
			array_map('unlink', glob($this->options['path'] . '/' . $this->domain . '/' . $this->client_id.'-*.cache'));
		}
		return true;
	}
}
