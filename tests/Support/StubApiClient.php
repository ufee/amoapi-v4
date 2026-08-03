<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\ApiClient;

/**
 * ApiClient для unit-тестов: query() возвращает StubQuery без HTTP.
 */
class StubApiClient extends ApiClient
{
	/** @var array<int, array{code:int, body:mixed}> */
	public $responses = [];

	/** @var StubQuery|null */
	public $lastQuery;

	/**
	 * Подменяет зарегистрированный ApiClient на stub с тем же client_id.
	 */
	public static function hijack(ApiClient $real): self
	{
		$ref = new \ReflectionClass(ApiClient::class);
		$selfRef = new \ReflectionClass(static::class);
		/** @var self $stub */
		$stub = $selfRef->newInstanceWithoutConstructor();

		foreach ($ref->getProperties() as $prop) {
			$prop->setAccessible(true);
			$prop->setValue($stub, $prop->getValue($real));
		}

		$instances = $ref->getProperty('_instances');
		$instances->setAccessible(true);
		$all = $instances->getValue(null);
		$all[$real->getIntegration('client_id')] = $stub;
		$instances->setValue(null, $all);

		return $stub;
	}

	/**
	 * @param mixed $body
	 */
	public function pushResponse(int $code, $body): self
	{
		$this->responses[] = ['code' => $code, 'body' => $body];
		return $this;
	}

	public function query(string $method = 'GET', string $url = '')
	{
		$query = new StubQuery($this);
		$query->setMethod($method);
		if ($url) {
			$query->setUrl($url);
		}
		$this->lastQuery = $query;
		return $query;
	}

	/**
	 * Переустанавливает retry-callbacks без sleep(1) — для быстрых unit-тестов.
	 */
	public function installFastRetries(): self
	{
		$this->callbacks->off('query.delay');
		$this->callbacks->off('query.response.code');
		$this->callbacks->on('query.response.code', function (int $code, Query $query) {
			if ($code == 429 && $query->retries <= $query->instance->getParam('query_retries')) {
				$query->prepare()->execute();
				return false;
			}
			if ($code == 401 && $query->retry) {
				$oauth = $this->oauth->get(null, true);
				$query->setHeader(
					'Authorization',
					$oauth['token_type'] . ' ' . $oauth['access_token']
				);
				$query->prepare()->setRetry(false)->execute();
				return false;
			}
			if (in_array($code, [502, 504], true) && $query->retry) {
				$query->prepare()->setRetry(false)->execute();
				return false;
			}
			return true;
		});
		return $this;
	}
}
