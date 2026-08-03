<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Tests\Support\LocalHttpServer;
use Ufee\AmoV4\Tests\TestCase;

class QueryHttpTest extends TestCase
{
	/** @var LocalHttpServer|null */
	private $server;

	protected function tearDown(): void
	{
		if ($this->server) {
			$this->server->stop();
			$this->server = null;
		}
		parent::tearDown();
	}

	private function apiForHttp()
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('local-token');
		$api->setParam('query_delay', 0);
		$api->callbacks->off('query.response.code'); // без sleep-ретраев
		return $api;
	}

	public function testRealExecuteCoversHttpVerbsAndHelpers(): void
	{
		$this->server = new LocalHttpServer();
		$api = $this->apiForHttp();
		$base = $this->server->url('/api/v4/contacts/1');

		foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
			$query = $api->query($method, $base);
			if ($method !== 'GET') {
				$query->setJsonData(['name' => 'N']);
			}
			$query->setHeader('X-Test', '1');
			$query->prepare();
			$this->assertTrue($query->execute());
			$this->assertSame(200, $query->response->getCode());
			$data = $query->response->parseJson();
			$this->assertSame($method, $data->method);
			$this->assertNotEmpty($query->startDate());
			$this->assertNotEmpty($query->endDate());
			$this->assertNotNull($query->response->getInfo());
			$query->clear();
		}

		// fail-callback path (не 200/202/204)
		$query = $api->query('GET', $base);
		$query->setHeader('X-Status', '400');
		$failed = false;
		$api->callbacks->on('query.response.fail', function () use (&$failed) {
			$failed = true;
		});
		$query->prepare();
		$this->assertTrue($query->execute());
		$this->assertTrue($failed);
		$this->assertSame(400, $query->response->getCode());

		// verbose + post form body
		$query = $api->query('POST', $this->server->url('/form'));
		$query->setPostData(['a' => 'b']);
		$query->prepare();
		$fp = fopen('php://temp', 'w+');
		$query->verbose($fp);
		$this->assertTrue($query->execute());
		fclose($fp);

		$query = $api->query('GET', $base);
		$query->prepare();
		$this->expectException(\InvalidArgumentException::class);
		$query->verbose('not-resource');
	}

	public function testQueryMagicAndGetRequestBodyJsonRequired(): void
	{
		$api = $this->apiForHttp();
		$query = $api->query('PATCH', '/api/v4/x');
		$ref = new \ReflectionMethod($query, 'getRequestBody');
		$ref->setAccessible(true);
		$this->assertSame('[]', $ref->invoke($query, true));

		try {
			$query->foo;
			$this->fail('expected Exception');
		} catch (\Exception $e) {
			$this->assertStringContainsString('Invalid Query field', $e->getMessage());
		}

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Protected Query field');
		$query->method = 'GET';
	}
}
