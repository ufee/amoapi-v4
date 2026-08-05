<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Services\Account as AccountService;
use Ufee\AmoV4\Tests\Support\EntityFixtures;
use Ufee\AmoV4\Tests\TestCase;

class EntityServiceMetaTest extends TestCase
{
	/**
	 * @dataProvider metaProvider
	 */
	public function testApiPathAndEntityKey(string $method, array $args, string $apiPath, string $entityKey): void
	{
		$service = $this->service($method, ...$args);

		$this->assertSame($apiPath, $service->api_path);
		$this->assertSame($entityKey, $service->entity_key);
	}

	/**
	 * @dataProvider createProvider
	 */
	public function testCreateAndCreateCollection(string $method, array $args, string $modelClass, string $collectionClass): void
	{
		$service = $this->service($method, ...$args);

		$model = $service->create(['name' => 'Unit']);
		$this->assertInstanceOf($modelClass, $model);
		$this->assertSame('Unit', $model->name);

		$collection = $service->createCollection([
			['id' => 1, 'name' => 'A'],
			['id' => 2, 'name' => 'B'],
		]);
		$this->assertInstanceOf($collectionClass, $collection);
		$this->assertCount(2, $collection);
		$this->assertInstanceOf($modelClass, $collection->first());
	}

	/**
	 * @dataProvider createProvider
	 */
	public function testWithSetsQueryArg(string $method, array $args): void
	{
		$service = $this->service($method, ...$args)->with(['leads']);
		$this->assertSame('leads', $service->getQueryArg('with'));
	}

	public function testAccountServiceMetaAndDefaults(): void
	{
		$service = $this->service('account');

		$this->assertInstanceOf(AccountService::class, $service);
		$this->assertSame('/api/v4/account', $service->api_path);
		$this->assertSame('account', $service->entity_key);
		$this->assertStringContainsString('drive_url', (string) $service->getQueryArg('with'));
	}

	public function testLeadsLossReasonsDelegation(): void
	{
		$service = $this->service('leads');
		$lossReasons = $service->lossReasons();

		$this->assertSame('/api/v4/leads/loss_reasons', $lossReasons->api_path);
	}

	public function testCustomersSegmentsDelegation(): void
	{
		$customers = $this->service('customers');
		$segments = $customers->segments();

		$this->assertSame('/api/v4/customers/segments', $segments->api_path);
	}

	public function testCatalogElementsCustomFieldsArgs(): void
	{
		$service = $this->service('catalogElements', 42);
		$this->assertSame(['catalogs', 42], $service->customFieldsArgs());
	}

	public function metaProvider(): array
	{
		return EntityFixtures::metaProvider();
	}

	public function createProvider(): array
	{
		return EntityFixtures::createProvider();
	}
}
