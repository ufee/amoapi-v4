<?php
/**
 * amoCRM API V4 Client
 * @author Vlad Ionov <vlad@f5.com.ru>
 */
namespace Ufee\AmoV4;

if (!defined('AMOV4API_ROOT')) {
	define('AMOV4API_ROOT', __DIR__);
}
/**
 * @property \Ufee\AmoV4\Api\Queries $queries
 * @property \Ufee\AmoV4\Api\Callbacks $callbacks
 * @property \Ufee\AmoV4\Api\Oauth $oauth
 * @property \Ufee\AmoV4\Api\Cache $cache
 * @property string $client_id
 * @property string $instance_key
 *
 * @method \Ufee\AmoV4\Services\Account account(...$args)
 * @method \Ufee\AmoV4\Services\Users users(...$args)
 * @method \Ufee\AmoV4\Services\Pipelines pipelines(...$args)
 * @method \Ufee\AmoV4\Services\LossReasons lossReasons(...$args)
 * @method \Ufee\AmoV4\Services\PipelineStatuses pipelineStatuses(...$args)
 * @method \Ufee\AmoV4\Services\CustomFields customFields(...$args)
 * @method \Ufee\AmoV4\Services\Leads leads(...$args)
 * @method \Ufee\AmoV4\Services\Unsorted unsorted(...$args)
 * @method \Ufee\AmoV4\Services\Contacts contacts(...$args)
 * @method \Ufee\AmoV4\Services\Companies companies(...$args)
 * @method \Ufee\AmoV4\Services\Customers customers(...$args)
 * @method \Ufee\AmoV4\Services\CustomerSegments customerSegments(...$args)
 * @method \Ufee\AmoV4\Services\Catalogs catalogs(...$args)
 * @method \Ufee\AmoV4\Services\CatalogElements catalogElements(...$args)
 * @method \Ufee\AmoV4\Services\Links links(...$args)
 * @method \Ufee\AmoV4\Services\Tasks tasks(...$args)
 * @method \Ufee\AmoV4\Services\Notes notes(...$args)
 * @method \Ufee\AmoV4\Services\Events events(...$args)
 * @method \Ufee\AmoV4\Services\Widgets widgets(...$args)
 * @method \Ufee\AmoV4\Services\Webhooks webhooks(...$args)
 * @method \Ufee\AmoV4\Services\Bots bots(...$args)
 * @method \Ufee\AmoV4\Services\Agents agents(...$args)
 * @method \Ufee\AmoV4\Services\Sources sources(...$args)
 * @method \Ufee\AmoV4\Services\Subscriptions subscriptions(...$args)
 * @method \Ufee\AmoV4\Services\Talks talks(...$args)
 * @method \Ufee\AmoV4\Services\Files files(...$args)
 */
class ApiClient
{
	/** Fallback timezone if account datetime_settings are not available */
	public const DEFAULT_TIMEZONE = 'Europe/Moscow';

	protected $services = [
		'account',
		'users',
		'pipelines',
		'lossReasons',
		'pipelineStatuses',
		'customFields',
		'leads',
		'unsorted',
		'contacts',
		'companies',
		'customers',
		'customerSegments',
		'catalogs',
		'catalogElements',
		'links',
		'tasks',
		'notes',
		'events',
		'widgets',
		'webhooks',
		'bots',
		'agents',
		'sources',
		'subscriptions',
		'talks',
		'files'
	];
	protected $_params = [
		'crm_host' => '',
		'token_refresh_time' => 900,
		'connect_timeout' => 15,
		'query_timeout' => 30,
		'query_delay' => 0.15,
		'query_retries' => 7,
		'timezone' => null,
		'lang' => 'ru',
		'user_agent' => 'Amoapi v4',
		'context_user_id' => null
	];
	protected $_integration = [];
	protected $_timezone = null;

	protected $_cache;
	protected $_oauth;
	protected $_queries;
	protected $_callbacks;

	/**
	 * Индекс экземпляров: ключ client_id:domain => \WeakReference.
	 * Клиент живёт, пока его держит владелец; после освобождения ключ становится промахом
	 */
	protected static $_instances = [];
	/** Последний экземпляр каждого client_id держится сильно, как раньше держался весь реестр */
	protected static $_latest = [];
	protected static $_sweep_at = 64;

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
	 * @return Api\Query
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
	 * Set context user for all queries (X-Context-User-ID)
	 * https://www.amocrm.ru/developers/content/oauth/scopes
	 * @param int|null $user_id - null resets context user
	 * @return ApiClient
	 */
	public function asUser($user_id = null)
	{
		if (is_null($user_id)) {
			return $this->setParam('context_user_id', null);
		}
		if (!is_numeric($user_id) || (int) $user_id < 1) {
			throw new \InvalidArgumentException('Context user ID must be a positive integer');
		}
		return $this->setParam('context_user_id', (int) $user_id);
	}

	/**
	 * Get context user of all queries
	 * @return int|null
	 */
	public function getContextUser()
	{
		return $this->getParam('context_user_id');
	}

	/**
	 * Get catalog element
	 * @param int $catalog_id
	 * @param int $element_id
	 * @param array $with
	 * @return Models\CatalogElement|null
	 */
	public function catalogElement(int $catalog_id, int $element_id, array $with = [])
	{
		return $this->catalogElements($catalog_id)->find($element_id, $with);
	}

	/**
	 * Get account timezone
	 * Param 'timezone' is a manual override, by default resolved from account datetime_settings
	 * @return \DateTimeZone
	 */
	public function timezone()
	{
		if ($tz = $this->getParam('timezone')) {
			return new \DateTimeZone($tz);
		}
		if (is_null($this->_timezone)) {
			$this->_timezone = static::DEFAULT_TIMEZONE;
			try {
				$settings = $this->cache->account()->datetime_settings;
				if (!empty($settings->timezone)) {
					$this->_timezone = $settings->timezone;
				}
			} catch (\Exception $e) {}
		}
		return new \DateTimeZone($this->_timezone);
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
		// client_id и domain входят в ключ экземпляра, crm_host и ключи хранилищ токенов и кеша
		if (in_array($key, ['client_id', 'domain']) && $value !== $this->_integration[$key]) {
			throw new \LogicException('Integration ' . $key . ' can not be changed after client creation, use ApiClient::setInstance');
		}
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
	 * Ключ экземпляра: один client_id обслуживает много аккаунтов,
	 * поэтому экземпляры различаются по домену
	 * @param string $client_id
	 * @param string $domain
	 * @return string
	 */
	public static function instanceKey($client_id, $domain = '')
	{
		return $client_id . ':' . $domain;
	}

	/**
	 * Get instance key
	 * @return string
	 */
	public function getKey()
	{
		return static::instanceKey($this->_integration['client_id'], $this->_integration['domain']);
	}

	/**
	 * Живые экземпляры по ключу: составной как есть, голый client_id — все его домены
	 * @param string $key
	 * @return ApiClient[] - ключ экземпляра => клиент
	 */
	protected static function resolveKey($key)
	{
		$keys = [];
		if (isset(static::$_instances[$key])) {
			$keys[] = $key;
		} else if (strpos($key, ':') === false) {
			foreach (array_keys(static::$_instances) as $existing) {
				if (strpos($existing, $key . ':') === 0) {
					$keys[] = $existing;
				}
			}
		}
		$found = [];
		foreach ($keys as $existing) {
			if ($instance = static::$_instances[$existing]->get()) {
				$found[$existing] = $instance;
			} else {
				unset(static::$_instances[$existing]);
			}
		}
		return $found;
	}

	/**
	 * Ошибка неоднозначного голого client_id
	 * @param string $key
	 * @param ApiClient[] $found
	 * @return \Exception
	 */
	protected static function ambiguous($key, array $found)
	{
		return new \Exception('Account is ambiguous: client_id ' . $key . ' has ' . count($found) . ' accounts (' . join(', ', array_keys($found)) . '), use ApiClient::instanceKey($client_id, $domain)');
	}

	/**
	 * Внести экземпляр в индекс
	 * @param ApiClient $instance
	 * @return void
	 */
	protected static function register(ApiClient $instance)
	{
		// мёртвые ссылки чистим, когда индекс вырос вдвое с прошлой чистки
		if (count(static::$_instances) >= static::$_sweep_at) {
			foreach (static::$_instances as $key => $ref) {
				if (!$ref->get()) {
					unset(static::$_instances[$key]);
				}
			}
			static::$_sweep_at = max(64, count(static::$_instances) * 2);
		}
		static::$_instances[$instance->getKey()] = \WeakReference::create($instance);
		static::$_latest[$instance->getIntegration('client_id')] = $instance;
	}

	/**
	 * Создать состояние экземпляра
	 * @return void
	 */
	protected function _bootState()
	{
		$this->_callbacks = new Api\Callbacks($this);
		$this->_oauth = new Api\Oauth($this);
		$this->_cache = new Api\Cache($this);
		$this->_queries = new Api\Queries();
		$this->_queries->boot($this);
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
		$instance->_bootState();
		static::register($instance);
		return $instance;
	}

	/**
	 * Get client instance
	 * @param string $key - client_id:domain или уникальный client_id
	 * @return ApiClient
	 */
	public static function getInstance($key)
	{
		$found = static::resolveKey($key);
		if (count($found) > 1) {
			throw static::ambiguous($key, $found);
		}
		if (!$found) {
			throw new \Exception('Account not found: ' . $key . ' (not registered or client already released)');
		}
		return current($found);
	}

	/**
	 * Has client isset: true, если getInstance вернёт клиента
	 * @param string $key - client_id:domain или уникальный client_id
	 * @return bool
	 */
	public static function hasInstance($key)
	{
		return count(static::resolveKey($key)) === 1;
	}

	/**
	 * Remove client from registry, the client itself keeps working for its owner
	 * @param string $key - client_id:domain или уникальный client_id
	 * @return void
	 * @throws \Exception - client_id с несколькими аккаунтами
	 */
	public static function removeInstance($key)
	{
		$found = static::resolveKey($key);
		if (count($found) > 1) {
			throw static::ambiguous($key, $found);
		}
		if (!$found) {
			return;
		}
		$instance = current($found);
		unset(static::$_instances[key($found)]);
		$client_id = $instance->getIntegration('client_id');
		if (isset(static::$_latest[$client_id]) && static::$_latest[$client_id] === $instance) {
			unset(static::$_latest[$client_id]);
		}
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
			return $this->_queries;
		} else if ($target === 'callbacks') {
			return $this->_callbacks;
		} else if ($target === 'oauth') {
			return $this->_oauth;
		} else if ($target === 'cache') {
			return $this->_cache;
		} else if ($target === 'client_id') {
			return $this->getIntegration('client_id');
		} else if ($target === 'instance_key') {
			return $this->getKey();
		} else if (in_array($target, $this->services)) {
			$service_class = '\\Ufee\\AmoV4\\Services\\' . ucfirst($target);
			return (new $service_class($this))->get();
		}
		return null;
	}
}
