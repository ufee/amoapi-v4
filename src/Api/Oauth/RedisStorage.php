<?php
/**
 * amoCRM API client Oauth handler - Redis
 */
namespace Ufee\AmoV4\Api\Oauth;
use Ufee\AmoV4\ApiClient;

class RedisStorage extends AbstractStorage
{
	const OAUTH_TTL = 7776000; // 90 days

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
	 * Get oauth data forced
	 * @return array|bool
	 */
	public function getRaw()
	{
		if ($data = $this->options['connection']->get($this->key)) {
			return $data;
		}
		return false;
	}

	/**
	 * Set oauth data
	 * @param array $oauth
	 * @return bool
	 */
	public function set(array $oauth)
	{
		parent::set($oauth);
		
		return $this->options['connection']->setEx($this->key, static::OAUTH_TTL, $oauth);
	}
}
