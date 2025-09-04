<?php
/**
 * amoCRM API client Oauth
 */
namespace Ufee\AmoV4\Api;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Api\Oauth\AbstractStorage;
use Ufee\AmoV4\Api\Oauth\FileStorage;
use Ufee\AmoV4\Api\Oauth\RedisStorage;
use Ufee\AmoV4\Api\Oauth\MongoDbStorage;
use Ufee\AmoV4\Api\Oauth\LongTokenStorage;
use Ufee\AmoV4\Exceptions;

/**
 * Oauth for amoCRM entities
 * @property \Ufee\AmoV4\ApiClient $instance
 */
class Oauth
{
	protected $client_id;
	protected $storage;

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
	 * Set oauth data
	 * @param array $oauth
	 * @return bool
	 */
	public function set(array $oauth)
	{
		return $this->storage->set($oauth);
	}

	/**
	 * Get oauth access data
	 * @param string|null $key
	 * @param bool $init - reinitialize storage
	 * @return string|array
	 */
	public function get($key = null, bool $init = false)
	{
		if ($init === true) {
			$this->storage->initialize();
		}
		return $this->storage->get($key);
	}

	/**
	 * Get authorize url
	 * @param array $data
	 * @return string
	 */
	public function getUrl(array $data = [])
	{
		$defaults = [
			'mode' => 'popup',
			'state' => 'amoapi'
		];
		$params = [];
		foreach ($defaults as $key => $val) {
			$params[$key] = $data[$key] ?? $val;
		}
		$params['client_id'] = $this->client_id;
		$params['redirect_uri'] = $this->instance->getIntegration('redirect_uri');

		$query = http_build_query($params, '', '&');
		return $this->instance->getParam('crm_host') . '/oauth?' . $query;
	}
	
	/**
	 * Get access token by code
	 * @param string $code
	 * @return array
	 */
	public function fetchToken(string $code)
	{
		$instance = $this->instance;
		$query = $instance->query('POST', '/oauth2/access_token')->setPostData([
			'client_id' => $instance->getIntegration('client_id'),
			'client_secret' => $instance->getIntegration('client_secret'),
			'redirect_uri' => $instance->getIntegration('redirect_uri'),
			'grant_type' => 'authorization_code',
			'code' => $code
		]);
		$response = new \Ufee\AmoV4\Api\Response($query->post(), $query);
		if (!$data = $response->parseJson()) {
			throw new Exceptions\AmoException('Fetch access token failed (non JSON), code: ' . $response->getCode(), $response->getCode());
		}
		if ($response->getCode() != 200 && !empty($data->hint)) {
			throw new Exceptions\OauthException('Fetch access token error: ' . $data->hint, $response->getCode());
		}
		if (!empty($data->status) && !empty($data->title) && !empty($data->detail)) {
			throw new Exceptions\OauthException('Fetch access token error: ' . $data->detail . ' - ' . $data->title, intval($data->status));
		}
		$oauth = (array) $data;
		$oauth['created_at'] = time();
		$instance->callbacks->trigger('oauth.token.fetch', $oauth, $query, $response);
		$this->storage->set($oauth);
		return $oauth;
	}

	/**
	 * Get access token by refresh token
	 * @param string|null $refresh_token
	 * @return array
	 */
	public function refreshToken($refresh_token = null)
	{
		$instance = $this->instance;
		$oauth = null;
		$e = null;
		if (is_null($refresh_token)) {
			$refresh_token = $this->get('refresh_token');
		}
		if (!$refresh_token) {
			$e = new Exceptions\OauthException('Empty oauth refresh_token');
			if ($instance->callbacks->has('oauth.token.refresh.error')) {
				$instance->callbacks->trigger('oauth.token.refresh.error', $e);
			} else {
				throw $e;
			}
		}
		$query = $instance->query('POST', '/oauth2/access_token')->setPostData([
			'client_id' => $instance->getIntegration('client_id'),
			'client_secret' => $instance->getIntegration('client_secret'),
			'redirect_uri' => $instance->getIntegration('redirect_uri'),
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token
		]);
		$query->setStartTime(microtime(true));
		$response = new \Ufee\AmoV4\Api\Response($query->post(), $query);
		$query->setEndTime(microtime(true));

		if (!$data = $response->parseJson()) {
			$e = new Exceptions\AmoException('Refresh access token failed (non JSON), code: ' . $response->getCode(), $response->getCode());
		} else if ($response->getCode() != 200 && !empty($data->hint)) {
			$e = new Exceptions\OauthException('Refresh access token error: ' . $data->hint, $response->getCode());
		} else if (!empty($data->status) && !empty($data->title) && !empty($data->detail)) {
			$e = new Exceptions\OauthException('Refresh access token error: ' . $data->detail . ' - ' . $data->title, intval($data->status));
		}
		if ($e) {
			if ($instance->callbacks->has('oauth.token.refresh.error')) {
				$instance->callbacks->trigger('oauth.token.refresh.error', $e, $query, $response);
			} else {
				throw $e;
			}
		} else {
			$oauth = (array) $data;
			$oauth['created_at'] = time();

			$instance->callbacks->trigger('oauth.token.refresh', $oauth, $query, $response);
			$this->storage->set($oauth);
		}
		return $oauth;
	}

	/**
	 * Set oauth storage handler
	 * @param string $path
	 * @return Oauth
	 */
	public function setStorageFiles(string $path)
	{
		$this->setStorage(
			new FileStorage($this->instance, ['path' => $path])
		);
		return $this;
	}
	
	/**
	 * Set oauth storage handler
	 * @param \Redis $connection
	 * @return Oauth
	 */
	public function setStorageRedis(\Redis $connection)
	{
		$this->setStorage(
			new RedisStorage($this->instance, ['connection' => $connection])
		);
		return $this;
	}
	
	/**
	 * Set oauth storage handler
	 * @param \MongoDB\Collection $collection
	 * @return Oauth
	 */
	public function setStorageMongo(\MongoDB\Collection $collection)
	{
		$this->setStorage(
			new MongoDbStorage($this->instance, ['collection' => $collection])
		);
		return $this;
	}
	
	/**
	 * Set long token storage
	 * @param string $long_token
	 * @return Oauth
	 */
	public function setLongToken(string $long_token)
	{
		$this->setStorage(
			new LongTokenStorage($this->instance, ['long_token' => $long_token])
		);
		return $this;
	}

	/**
	 * Set oauth storage handler
	 * @param AbstractStorage $storage
	 * @return Oauth
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
			throw new \Exception('Invalid Oauth field: '.$target);
		}
		return $this->{$target};
	}
}
