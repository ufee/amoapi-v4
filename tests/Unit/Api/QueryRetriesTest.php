<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Tests\TestCase;

class QueryRetriesTest extends TestCase
{
	public function testRetryOn429ThenSuccess(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('query_retries', 3);

		$events = [];
		$api->callbacks->on('query.request.before', function () use (&$events) {
			$events[] = 'before';
		});
		$api->callbacks->on('query.response.fail', function () use (&$events) {
			$events[] = 'fail';
		});
		$api->callbacks->on('query.response.after', function ($query, $code) use (&$events) {
			$events[] = 'after:' . $code;
		});

		$api->pushResponse(429, ['detail' => 'Too many requests']);
		$api->pushResponse(200, ['id' => 7, 'name' => 'Ok']);

		$model = $api->contacts()->find(7);

		$this->assertNotNull($model);
		$this->assertSame(7, $model->id);
		$this->assertSame(['before', 'after:200'], $events);
		$this->assertGreaterThanOrEqual(2, $api->lastQuery->retries);
	}

	public function testRetryOn502Once(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(502, 'Bad Gateway');
		$api->pushResponse(200, ['id' => 1, 'name' => 'A']);

		$model = $api->contacts()->find(1);
		$this->assertSame(1, $model->id);
		$this->assertFalse($api->lastQuery->retry);
	}

	public function testFailCallbackOnNonRetryableError(): void
	{
		$api = $this->makeStubApiClient();
		$failed = null;
		$api->callbacks->on('query.response.fail', function ($query, $code) use (&$failed) {
			$failed = $code;
		});

		$api->pushResponse(400, ['detail' => 'Bad request']);
		$query = $api->query('GET', '/api/v4/contacts/1');
		$query->execute();

		$this->assertSame(400, $failed);
		$this->assertSame(400, $query->response->getCode());
	}

	public function testResponseCodeCallbackCanAbort(): void
	{
		$api = $this->makeStubApiClient();
		$api->callbacks->off('query.response.code');
		$api->callbacks->on('query.response.code', function () {
			return false;
		});

		$after = false;
		$api->callbacks->on('query.response.after', function () use (&$after) {
			$after = true;
		});

		$api->pushResponse(200, ['ok' => true]);
		$result = $api->query('GET', '/api/v4/account')->execute();

		$this->assertFalse($result);
		$this->assertFalse($after);
	}
}
