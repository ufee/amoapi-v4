<?php
/**
 * amoCRM API client Oauth handler interface
 */
namespace Ufee\AmoV4\Api\Oauth;
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
	public function __construct(ApiClient $client, array $options)
	{
		$this->domain = $client->getIntegration('domain');
		$this->client_id = $client->getIntegration('client_id');
		$this->key = $this->domain . '_' . $this->client_id;
		$this->options = $options;
	}

	/**
	 * Init oauth handler
	 * @return void
	 */
	public function initialize()
	{
		static::$_local[$this->key] = [
			'token_type' => '',
			'expires_in' => 0,
			'access_token' => '',
			'refresh_token' => '',
			'created_at' => 0
		];
	}

	/**
	 * Get oauth data
	 * @param string|null $field
	 * @return array
	 */
	public function get($field = null)
	{
		if (!array_key_exists($this->key, static::$_local)) {
			$this->initialize();
		}
		if (!is_null($field) && array_key_exists($field, static::$_local[$this->key])) {
			return static::$_local[$this->key][$field];
		}
		return static::$_local[$this->key];
	}
	
	/**
	 * Set oauth data
	 * @param array $oauth
	 * @return bool
	 */
	public function set(array $oauth)
	{
		if (!array_key_exists($this->key, static::$_local)) {
			$this->initialize();
		}
		static::$_local[$this->key] = $oauth;
		return true;
	}
}
