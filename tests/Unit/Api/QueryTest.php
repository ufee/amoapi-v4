<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Exceptions\OauthException;
use Ufee\AmoV4\Tests\TestCase;

class QueryTest extends TestCase
{
	public function testGetUrlBuildsCrmHostPathAndArgs(): void
	{
		$api = $this->makeApiClient(['domain' => 'acme', 'zone' => 'ru']);
		$query = $api->query('GET', '/api/v4/contacts');
		$query->setArgs(['limit' => 10, 'page' => 2]);

		$this->assertSame(
			'https://acme.amocrm.ru/api/v4/contacts?limit=10&page=2',
			$query->getUrl()
		);
	}

	public function testSetHostOverridesCrmHost(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('GET', '/v1.0/files');
		$query->setHost('drive-b.amocrm.ru');

		$this->assertSame('https://drive-b.amocrm.ru/v1.0/files', $query->getUrl());
	}

	public function testAbsoluteUrlKeepsScheme(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('POST', 'https://upload.example/part');
		$query->setArgs(['x' => 1]);

		$this->assertSame('https://upload.example/part?x=1', $query->getUrl());
	}

	public function testSetJsonDataSetsHeaderAndBody(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('POST', '/api/v4/contacts');
		$query->setJsonData([['name' => 'A']]);

		$this->assertSame('application/json', $query->headers['Content-Type']);
		$this->assertSame([['name' => 'A']], $query->json_data);

		$ref = new \ReflectionMethod($query, 'getRequestBody');
		$ref->setAccessible(true);
		$this->assertSame('[{"name":"A"}]', $ref->invoke($query));
	}

	public function testSetRawDataClearsJson(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('POST', '/upload');
		$query->setJsonData(['a' => 1]);
		$query->setRawData('binary');

		$this->assertSame('binary', $query->raw_data);
		$this->assertSame([], $query->json_data);

		$ref = new \ReflectionMethod($query, 'getRequestBody');
		$ref->setAccessible(true);
		$this->assertSame('binary', $ref->invoke($query));
	}

	public function testResetArgs(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('GET', '/x');
		$query->setArgs(['a' => 1, 'b' => 2]);
		$query->resetArgs(['a']);
		$this->assertSame(['b' => 2], $query->args);

		$query->resetArgs();
		$this->assertSame([], $query->args);
	}

	public function testGenerateHashIsStable(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('GET', '/api/v4/contacts');
		$query->setArgs(['limit' => 1]);
		$hash1 = $query->generateHash();
		$hash2 = $query->generateHash();
		$this->assertSame($hash1, $hash2);
		$this->assertNotEmpty($hash1);
	}

	public function testExecuteWithoutAccessTokenThrows(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('GET', '/api/v4/account');
		$query->prepare();

		$this->expectException(OauthException::class);
		$this->expectExceptionMessage('Empty oauth access_token');
		$query->execute();
	}

	public function testToArrayHidesSensitiveFields(): void
	{
		$api = $this->makeApiClient();
		$query = $api->query('GET', '/x');
		$query->prepare();
		$data = $query->toArray();

		$this->assertArrayHasKey('method', $data);
		$this->assertArrayNotHasKey('curl', $data);
		$this->assertArrayNotHasKey('client_id', $data);
		$this->assertArrayNotHasKey('response', $data);
	}

	public function testKommoZoneHost(): void
	{
		$api = $this->makeApiClient(['domain' => 'acme', 'zone' => 'com']);
		$query = $api->query('GET', '/api/v4/leads');
		$this->assertSame('https://acme.kommo.com/api/v4/leads', $query->getUrl());
	}
}
