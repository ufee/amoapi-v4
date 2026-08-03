<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Services\Service;
use Ufee\AmoV4\Tests\Support\StubApiClient;

abstract class TestCase extends BaseTestCase
{
	/** @var string|null */
	protected $clientId;

	protected function tearDown(): void
	{
		if ($this->clientId !== null && ApiClient::hasInstance($this->clientId)) {
			ApiClient::removeInstance($this->clientId);
		}
		$this->clientId = null;
		parent::tearDown();
	}

	protected function makeApiClient(array $overrides = []): ApiClient
	{
		$this->clientId = $overrides['client_id'] ?? ('test-' . uniqid('', true));

		return ApiClient::setInstance(array_merge([
			'domain' => 'example',
			'client_id' => $this->clientId,
			'client_secret' => 'secret',
			'redirect_uri' => 'https://localhost/oauth',
			'zone' => 'ru',
		], $overrides));
	}

	/**
	 * @param mixed ...$args
	 */
	protected function service(string $name, ...$args): Service
	{
		$api = $this->makeApiClient();
		return $api->{$name}(...$args);
	}

	protected function makeStubApiClient(array $overrides = []): StubApiClient
	{
		$api = StubApiClient::hijack($this->makeApiClient($overrides));
		$api->oauth->setLongToken($overrides['long_token'] ?? 'stub-access-token');
		$api->setParam('query_delay', 0);
		$api->installFastRetries();
		return $api;
	}
}
