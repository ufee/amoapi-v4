<?php
/**
 * amoCRM API V4 Client
 * @author Vlad Ionov <vlad@f5.com.ru>
 */
namespace Ufee\AmoV4;

if (!defined('AMOV4API_ROOT')) {
	define('AMOV4API_ROOT',  __DIR__);
}
/**
 * @property \Ufee\AmoV4\Api\Queries $queries
 * @property \Ufee\AmoV4\Api\Callbacks $callbacks
 * @property \Ufee\AmoV4\Api\Oauth $oauth
 * @property \Ufee\AmoV4\Api\Cache $cache
 * @property string $client_id
 *
 * @method \Ufee\AmoV4\Services\Account account(...$args)
 * @method \Ufee\AmoV4\Services\Users users(...$args)
 * @method \Ufee\AmoV4\Services\Pipelines pipelines(...$args)
 * @method \Ufee\AmoV4\Services\PipelineStatuses pipelineStatuses(...$args)
 * @method \Ufee\AmoV4\Services\CustomFields customFields(...$args)
 * @method \Ufee\AmoV4\Services\Leads leads(...$args)
 * @method \Ufee\AmoV4\Services\Contacts contacts(...$args)
 * @method \Ufee\AmoV4\Services\Companies companies(...$args)
 * @method \Ufee\AmoV4\Services\Links links(...$args)
 * @method \Ufee\AmoV4\Services\Tasks tasks(...$args)
 * @method \Ufee\AmoV4\Services\Notes notes(...$args)
 * @method \Ufee\AmoV4\Services\Events events(...$args)
 * @method \Ufee\AmoV4\Services\Widgets widgets(...$args)
 * @method \Ufee\AmoV4\Services\Webhooks webhooks(...$args)
 * @method \Ufee\AmoV4\Services\Bots bots(...$args)
 */
class ApiClient
{
	protected $services = [
		'account',
		'users',
		'pipelines',
		'pipelineStatuses',
		'customFields',
		'leads',
		'contacts',
		'companies',
		'links',
		'tasks',
		'notes',
		'events',
		'widgets',
		'webhooks',
		'bots'
	];
	protected $_params = [
		'crm_host' => '',
		'token_refresh_time' => 900,
		'connect_timeout' => 15,
		'query_timeout' => 30,
		'query_delay' => 0.15,
		'query_retries' => 7,
		'timezone' => 'Europe/Moscow',
		'lang' => 'ru',
		'user_agent' => 'Amoapi v4'
	];
	protected $_integration = [];
	
	protected static $_cache = [];
	protected static $_oauth = [];
	protected static $_queries = [];
	protected static $_callbacks = [];
	protected static $_instances = [];

	/**
	 * Constructor
	 * @param array $account
	 */
	private function __construct(array $account)
	{
		$this->_integration = $account;
		$this->_params['crm_host'] = $account['zone'] == 'com' ? $account['domain'] . '.kommo.com' : $account['domain'] . '.amocrm.ru';
	}
	
	/**
	 * Get api query
	 * @param string $method
	 * @param string $url
	 * @return Query
	 */
	public function query(string $method = 'GET', string $url = '')
	{
		$query = new \Ufee\AmoV4\Api\Query($this);
		$query->setMethod($method);
		if ($url) {
			$query->setUrl($url);
		}
		return $query;
	}

	/**
	 * Set param value
	 * @param string $key
	 * @param mixed $value
	 * @return ApiClient
	 */
	public function setParam(string $key, $value)
	{
		$this->_params[$key] = $value;
		return $this;
	}
	
	/**
	 * Get param value
	 * @param string|null $key
	 * @param mixed $default
	 * @return string|array
	 */
	public function getParam($key = null, $default = null)
	{
		if (!array_key_exists($key, $this->_params)) {
			return $default;
		}
		return $this->_params[$key];
	}

	/**
	 * Set integration data
	 * @param string $key
	 * @param mixed $value
	 * @return ApiClient
	 */
	public function setIntegration(string $key, $value)
	{
		if (array_key_exists($key, $this->_integration)) {
			$this->_integration[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * Get integration data
	 * @param string|null $key
	 * @return string|array
	 */
	public function getIntegration($key = null)
	{
		if ($key == 'id') {
			return $this->_integration['client_id'];
		}
		if (!is_null($key) && isset($this->_integration[$key])) {
			return $this->_integration[$key];
		}
		return $this->_integration;
	}

	/**
	 * Set client instance
	 * @param array $data
	 * @return ApiClient
	 */
	public static function setInstance(array $data)
	{
		if (empty($data) || empty($data['client_id'])) {
			throw new \Exception('Incorrect amoCRM oauth data');
		}
		$account = [
			'domain' => '',
			'client_id' => '',
			'client_secret' => '',
			'redirect_uri' => '',
			'zone' => 'ru'
		];
		foreach ($account as $key => $val) {
			$account[$key] = isset($data[$key]) ? $data[$key] : $val;
		}
		$instance = new static($account);
		$instance->setParam('user_agent', 'Amoapi v4 (' . $account['client_id'] . ')');
		static::$_instances[$account['client_id']] = $instance;
		static::$_callbacks[$account['client_id']] = new Api\Callbacks($instance);
		static::$_oauth[$account['client_id']] = new Api\Oauth($instance);
		static::$_cache[$account['client_id']] = new Api\Cache($instance);
		static::$_queries[$account['client_id']] = new Api\Queries();
		static::$_queries[$account['client_id']]->boot($instance);
		return $instance;
	}

	/**
	 * Get client instance
	 * @param string $client_id
	 * @return ApiClient
	 */
	public static function getInstance($client_id)
	{
		if (!isset(static::$_instances[$client_id])) {
			throw new \Exception('Account not found: ' . $client_id);
		}
		return static::$_instances[$client_id];
	}

	/**
	 * Has client isset
	 * @param string $client_id
	 * @return bool
	 */
	public static function hasInstance($client_id)
	{
		return isset(static::$_instances[$client_id]);
	}

	/**
	 * Remove client isset
	 * @param string $client_id
	 * @return ApiClient
	 */
	public static function removeInstance($client_id)
	{
		if (isset(static::$_instances[$client_id])) {
			static::$_instances[$client_id] = null;
		}
		unset(static::$_instances[$client_id]);
	}

	/**
	 * Call Service Methods
	 * @param string $service_name
	 * @param array $args
	 */
	public function __call($service_name, $args)
	{
		if (!in_array($service_name, $this->services)) {
			throw new \Exception('Invalid service called: ' . $service_name);
		}
		$service_class = '\\Ufee\\AmoV4\\Services\\' . ucfirst($service_name);
		return new $service_class($this, $args);
	}

	/**
	 * Get Services
	 * @param string $target
	 */
	public function __get($target)
	{
		if ($target === 'queries') {
			return static::$_queries[$this->getIntegration('id')];
		}
		else if ($target === 'callbacks') {
			return static::$_callbacks[$this->getIntegration('id')];
		}
		else if ($target === 'oauth') {
			return static::$_oauth[$this->getIntegration('id')];
		}
		else if ($target === 'cache') {
			return static::$_cache[$this->getIntegration('id')];
		}
		else if ($target === 'client_id') {
			return $this->getIntegration('client_id');
		}
		else if (in_array($target, $this->services)) {
			$service_class = '\\Ufee\\AmoV4\\Services\\' . ucfirst($target);
			return (new $service_class($this))->get();
		}
		return null;
	}
}
