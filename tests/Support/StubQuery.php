<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\Api\Response;
use Ufee\AmoV4\Exceptions;

/**
 * Query без реального HTTP: отдаёт заранее подготовленные ответы.
 * Повторяет callback-flow настоящего Query::execute (retries / fail / after).
 */
class StubQuery extends Query
{
	/** @var array<int, array{code:int, body:string}> */
	public $queue = [];

	/** @var int|null */
	private $pendingHttpCode;

	/**
	 * @param mixed $body
	 */
	public function pushResponse(int $code, $body): self
	{
		$this->queue[] = [
			'code' => $code,
			'body' => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE),
		];
		return $this;
	}

	public function execute()
	{
		$ref = new \ReflectionClass(Query::class);
		$attr = $ref->getProperty('attributes');
		$attr->setAccessible(true);
		$attributes = $attr->getValue($this);
		$attributes['retries'] = (int) ($attributes['retries'] ?? 0) + 1;
		$attr->setValue($this, $attributes);

		$instance = $this->instance;

		$oauth = $instance->oauth->get();
		if (empty($oauth['access_token'])) {
			throw new Exceptions\OauthException('Empty oauth access_token');
		}
		$expire_time = ($oauth['created_at'] + $oauth['expires_in']) - time();

		if ($expire_time < $instance->getParam('token_refresh_time')) {
			$instance->oauth->refreshToken();
			$oauth = $instance->oauth->get();
		}
		$this->setHeader(
			'Authorization',
			$oauth['token_type'] . ' ' . $oauth['access_token']
		);
		$this->generateHash();
		if ($this->retries === 1) {
			$instance->callbacks->trigger('query.request.before', $this);
			$instance->callbacks->trigger('query.delay', $this);
		}
		$method = strtolower($this->method);

		$this->prepare();
		$body = call_user_func([$this, $method]);
		$response = new Response($body, $this);
		ResponseFactory::forceHttpCode($response, (int) $this->pendingHttpCode);
		$this->pendingHttpCode = null;

		$attributes = $attr->getValue($this);
		$attributes['response'] = $response;
		if (isset($attributes['curl']) && is_resource($attributes['curl'])) {
			curl_close($attributes['curl']);
		}
		$attributes['curl'] = null;
		$attributes['end_time'] = microtime(true);
		$attributes['execution_time'] = round(
			$attributes['end_time'] - (float) ($attributes['start_time'] ?? $attributes['end_time']),
			5
		);
		$attributes['memory_usage'] = memory_get_peak_usage(true) / 1024 / 1024;
		$attr->setValue($this, $attributes);

		$code = $this->response->getCode();

		if ($instance->callbacks->trigger('query.response.code', $code, $this) === false) {
			return false;
		}
		if (in_array($code, [200, 202, 204], true)) {
			$instance->queries->pushQuery($this);
		} else {
			$instance->callbacks->trigger('query.response.fail', $this, $code);
		}
		$instance->callbacks->trigger('query.response.after', $this, $code);
		return true;
	}

	public function get()
	{
		return $this->httpStub();
	}

	public function post()
	{
		return $this->httpStub();
	}

	public function put()
	{
		return $this->httpStub();
	}

	public function patch()
	{
		return $this->httpStub();
	}

	public function delete()
	{
		return $this->httpStub();
	}

	/**
	 * HTTP-код последнего stub-ответа (для вызовов post()/get() вне execute).
	 */
	public function lastHttpCode(): ?int
	{
		return $this->pendingHttpCode;
	}

	/**
	 * Применить код к Response после прямого post()/get() (oauth refresh и т.п.).
	 */
	public function applyPendingCode(Response $response): void
	{
		if ($this->pendingHttpCode !== null) {
			ResponseFactory::forceHttpCode($response, $this->pendingHttpCode);
			$this->pendingHttpCode = null;
		}
	}

	private function httpStub(): string
	{
		$ref = new \ReflectionClass(Query::class);
		$attr = $ref->getProperty('attributes');
		$attr->setAccessible(true);
		$attributes = $attr->getValue($this);
		$attributes['start_time'] = microtime(true);
		$attr->setValue($this, $attributes);

		$item = $this->takeQueuedItem();
		$this->pendingHttpCode = $item['code'];
		return $item['body'];
	}

	/**
	 * @return array{code:int, body:string}
	 */
	private function takeQueuedItem(): array
	{
		$item = array_shift($this->queue);
		if ($item === null) {
			$client = $this->instance;
			if ($client instanceof StubApiClient && !empty($client->responses)) {
				$next = array_shift($client->responses);
				$item = [
					'code' => $next['code'],
					'body' => is_string($next['body'])
						? $next['body']
						: json_encode($next['body'], JSON_UNESCAPED_UNICODE),
				];
			}
		}
		if ($item === null) {
			throw new \RuntimeException('StubQuery: очередь ответов пуста');
		}
		return $item;
	}
}
