<?php
/**
 * amoCRM API client Oauth handler - files
 */
namespace Ufee\AmoV4\Api\Oauth;
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
	}
	
	/**
	 * Get oauth data forced
	 * @return array|bool
	 */
	public function getRaw()
	{
		if (file_exists($this->options['path'] . '/' . $this->domain . '/' . $this->client_id . '.json')) {
			return json_decode(file_get_contents($this->options['path'] . '/' . $this->domain . '/' . $this->client_id . '.json'), true);
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
		
		return (bool)file_put_contents($this->options['path'] . '/' . $this->domain . '/' . $this->client_id . '.json', json_encode($oauth));
	}
}
