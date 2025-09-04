<?php
/**
 * amoCRM API client Oauth handler - long token
 */
namespace Ufee\AmoV4\Api\Oauth;
use Ufee\AmoV4\ApiClient;

class LongTokenStorage extends AbstractStorage
{
	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $options
	 */
	public function __construct(ApiClient $client, array $options)
	{
		parent::__construct($client, $options);

		if (empty($this->options['long_token'])) {
			throw new \InvalidArgumentException('Long Token Storage options[long_token] must be string of the access token');
		}
	}

	/**
	 * Init oauth handler
	 * @return void
	 */
	public function initialize()
	{
		static::$_local[$this->key] = [
			"token_type" => "Bearer",
			"expires_in" => time()+157680000, // 5 years
			"access_token" => $this->options['long_token'],
			"created_at" => 1
		];
	}
}
