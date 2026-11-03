<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit;

use Ufee\AmoV4\Api;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Services\Leads;
use Ufee\AmoV4\Tests\TestCase;

final class ApiClientInstancesTest extends TestCase
{
	private array $keys = [];

	protected function tearDown(): void
	{
		foreach ($this->keys as $key) {
			if (ApiClient::hasInstance($key)) {
				ApiClient::removeInstance($key);
			}
		}
		$this->keys = [];
		gc_collect_cycles();
		parent::tearDown();
	}

	private function make(string $client_id, string $domain): ApiClient
	{
		$client = ApiClient::setInstance([
			'domain' => $domain,
			'client_id' => $client_id,
			'client_secret' => 'secret',
			'redirect_uri' => 'https://localhost/oauth',
			'zone' => 'ru',
		]);
		$this->keys[] = $client->getKey();
		return $client;
	}

	/**
	 * Регистрирует клиентов, не сохраняя ссылок на них
	 */
	private function makeReleased(string $client_id, int $from, int $to): void
	{
		for ($i = $from; $i <= $to; $i++) {
			$client = $this->make($client_id, 'd' . $i);
			// колбэк замыкает клиента на себя: освободить его может только сборщик циклов
			$client->callbacks->on('test.ping', function () use ($client) {
				return $client;
			});
		}
		unset($client);
	}

	public function testTwoDomainsWithSameClientIdKeepSeparateInstances(): void
	{
		$a = $this->make('shared', 'alpha');
		$b = $this->make('shared', 'beta');

		self::assertSame('shared:alpha', $a->getKey());
		self::assertSame('shared:beta', $b->getKey());
		self::assertSame($a, ApiClient::getInstance(ApiClient::instanceKey('shared', 'alpha')));
		self::assertSame($b, ApiClient::getInstance(ApiClient::instanceKey('shared', 'beta')));
		self::assertSame('alpha.amocrm.ru', $a->leads()->instance->getParam('crm_host'));
		self::assertSame('beta.amocrm.ru', $b->leads()->instance->getParam('crm_host'));
		self::assertNotSame($a->oauth, $b->oauth);
		self::assertSame($a, $a->oauth->instance);
		self::assertSame($b, $b->cache->instance);
	}

	public function testSecondDomainDoesNotHijackFirstClientState(): void
	{
		// одна интеграция на много аккаунтов: до составного ключа второй setInstance подменял состояние первого
		$first = $this->make('one-integration', 'dimitriydevelop');
		$leads = $first->leads();
		$oauth = $first->oauth;
		$cache = $first->cache;
		$queries = $first->queries;
		$callbacks = $first->callbacks;

		for ($i = 0; $i < 250; $i++) {
			$this->make('one-integration', 'fake-b-' . $i);
		}

		self::assertSame($first, $leads->instance);
		self::assertSame($first, $first->leads()->instance);
		self::assertSame('dimitriydevelop.amocrm.ru', $leads->instance->getParam('crm_host'));
		self::assertSame($oauth, $first->oauth);
		self::assertSame($cache, $first->cache);
		self::assertSame($queries, $first->queries);
		self::assertSame($callbacks, $first->callbacks);
		self::assertSame($first, $first->oauth->instance);
		self::assertSame($first, $first->cache->instance);
		self::assertSame($first, ApiClient::getInstance($first->getKey()));
	}

	public function testBareClientIdResolvesOnlyWhenUnique(): void
	{
		$a = $this->make('solo', 'alpha');
		self::assertSame($a, ApiClient::getInstance('solo'));
		self::assertTrue(ApiClient::hasInstance('solo'));

		$b = $this->make('solo', 'beta');
		self::assertFalse(ApiClient::hasInstance('solo'));
		ApiClient::removeInstance('solo');
		self::assertSame($a, ApiClient::getInstance('solo:alpha'));

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Account is ambiguous: client_id solo has 2 accounts (solo:alpha, solo:beta)');
		ApiClient::getInstance('solo');
	}

	public function testHeldClientKeepsWorkingAfterThousandRegistrations(): void
	{
		$first = $this->make('many', 'd0');
		$pings = 0;
		$first->callbacks->on('test.ping', function () use (&$pings) {
			$pings++;
			return true;
		});
		$this->makeReleased('many', 1, 999);
		gc_collect_cycles();

		self::assertTrue(ApiClient::hasInstance($first->getKey()));
		self::assertInstanceOf(Api\Oauth::class, $first->oauth);
		self::assertInstanceOf(Api\Cache::class, $first->cache);
		self::assertInstanceOf(Api\Queries::class, $first->queries);
		self::assertSame($first, $first->oauth->instance);
		self::assertSame($first, $first->cache->instance);
		self::assertSame($first, $first->leads()->instance);
		self::assertSame($first, $first->query('GET', '/api/v4/leads')->instance);
		self::assertTrue($first->callbacks->trigger('test.ping'));
		self::assertSame(1, $pings);
	}

	public function testReleasedClientsAreFreed(): void
	{
		// первый проход прогревает автозагрузку и кеши движка
		$this->makeReleased('free', 1, 1000);
		gc_collect_cycles();
		$before = memory_get_usage();

		$this->makeReleased('free', 1001, 2000);
		gc_collect_cycles();
		$growth = memory_get_usage() - $before;

		for ($i = 1001; $i < 2000; $i++) {
			self::assertFalse(ApiClient::hasInstance(ApiClient::instanceKey('free', 'd' . $i)), 'd' . $i);
		}
		// последний экземпляр client_id держится, как держался весь реестр до составного ключа
		self::assertTrue(ApiClient::hasInstance(ApiClient::instanceKey('free', 'd2000')));
		// тысяча живых клиентов заняла бы мегабайты, индекс мёртвых ключей — десятки килобайт
		self::assertLessThan(512 * 1024, $growth, 'memory growth ' . $growth);
	}

	public function testSerializedServiceFindsClientThroughRegistry(): void
	{
		$client = $this->make('ser', 'alpha');
		$restored = unserialize(serialize($client->leads()));

		self::assertInstanceOf(Leads::class, $restored);
		self::assertSame($client, $restored->instance);

		ApiClient::removeInstance($client->getKey());
		unset($client);
		gc_collect_cycles();

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Account not found: ser:alpha (not registered or client already released)');
		$restored->instance;
	}

	public function testDomainAndClientIdCanNotBeChanged(): void
	{
		$client = $this->make('fixed', 'alpha');
		$client->setIntegration('domain', 'alpha');
		$client->setIntegration('client_secret', 'other');
		self::assertSame('other', $client->getIntegration('client_secret'));

		$this->expectException(\LogicException::class);
		$client->setIntegration('domain', 'beta');
	}
}
