<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
	/** @var ApiClient|null */
	protected $api;

	/** @var array<int, array{path: string, id: int}> */
	private $cleanupQueue = [];

	protected function setUp(): void
	{
		parent::setUp();

		$domain = getenv('AMO_DOMAIN') ?: '';
		$clientId = getenv('AMO_CLIENT_ID') ?: '';
		$token = getenv('AMO_LONG_TOKEN') ?: '';

		if ($domain === '' || $clientId === '' || $token === '') {
			$this->markTestSkipped(
				'Интеграционные тесты требуют AMO_DOMAIN, AMO_CLIENT_ID и AMO_LONG_TOKEN в .env'
			);
		}

		$this->api = $this->makeApiClient([
			'domain' => $domain,
			'client_id' => $clientId,
			'client_secret' => getenv('AMO_CLIENT_SECRET') ?: 'unused-with-long-token',
			'redirect_uri' => getenv('AMO_REDIRECT_URI') ?: 'https://localhost/oauth',
			'zone' => getenv('AMO_ZONE') ?: 'ru',
		]);

		$lang = getenv('AMO_LANG') ?: 'ru';
		$this->api->setParam('lang', $lang);
		$this->api->oauth->setLongToken($token);
	}

	protected function tearDown(): void
	{
		while ($item = array_pop($this->cleanupQueue)) {
			try {
				$this->deleteEntity($item['path'], $item['id']);
			} catch (\Throwable $e) {
				// best-effort cleanup
			}
		}
		parent::tearDown();
	}

	protected function uniqueName(string $prefix): string
	{
		return 'ITEST ' . $prefix . ' ' . uniqid('', false);
	}

	protected function waitForSearch(int $seconds = 1): void
	{
		sleep(max(1, $seconds));
	}

	/**
	 * Поставить сущность в очередь удаления (LIFO).
	 */
	protected function trackDelete(string $apiPath, int $id): void
	{
		$this->cleanupQueue[] = ['path' => $apiPath, 'id' => $id];
	}

	protected function deleteEntity(string $apiPath, int $id): void
	{
		$query = $this->api->query('DELETE', $apiPath);
		$query->setJsonData([['id' => $id]]);
		$query->execute();
	}

	/** @deprecated используйте trackDelete('/api/v4/contacts', $id) */
	protected function deleteContact(int $id): void
	{
		$this->deleteEntity('/api/v4/contacts', $id);
	}
}
