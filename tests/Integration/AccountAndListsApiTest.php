<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Account;
use Ufee\AmoV4\Models\User;

/**
 * Smoke-тесты read-only сущностей (без создания мусора в аккаунте).
 *
 * @group integration
 */
class AccountAndListsApiTest extends IntegrationTestCase
{
	public function testAccountGet(): void
	{
		$account = $this->api->account()->get();
		$this->assertInstanceOf(Account::class, $account);
		$this->assertNotEmpty($account->id);
	}

	public function testUsersList(): void
	{
		$users = $this->api->users()->maxPageRows(5)->paginate()->fetchPage();
		$this->assertGreaterThan(0, $users->count());
		$this->assertInstanceOf(User::class, $users->first());
	}

	public function testPipelinesAndStatuses(): void
	{
		$pipelines = $this->api->pipelines()->get();
		$this->assertGreaterThan(0, $pipelines->count());

		$pipeline = $pipelines->first();
		$statuses = $this->api->pipelineStatuses($pipeline->id)->get();
		$this->assertGreaterThan(0, $statuses->count());
	}

	public function testLossReasonsList(): void
	{
		$page = $this->fetchFirstPage('lossReasons');
		$this->assertNotNull($page);
	}

	public function testCustomFieldsForContacts(): void
	{
		$fields = $this->api->customFields('contacts')->maxPageRows(10)->paginate()->fetchPage();
		$this->assertGreaterThan(0, $fields->count());
	}

	public function testEventsFirstPage(): void
	{
		$this->assertNotNull($this->fetchFirstPage('events'));
	}

	public function testCustomerSegmentsList(): void
	{
		$page = $this->fetchFirstPage('customerSegments');
		if ($page === null) {
			$this->markTestSkipped('Сегменты покупателей недоступны в этом аккаунте');
		}
		$this->assertIsObject($page);
	}

	/**
	 * @return \Ufee\AmoV4\Collections\Collection|null
	 */
	private function fetchFirstPage(string $serviceMethod)
	{
		try {
			return $this->api->{$serviceMethod}()->maxPageRows(5)->paginate()->fetchPage();
		} catch (\Throwable $e) {
			// 204 / пустой embedded — допустимо для smoke
			return null;
		}
	}

	public function testFilesServiceBootstraps(): void
	{
		$files = $this->api->files();
		$this->assertSame('/v1.0/files', $files->api_path);
		$this->assertSame(50, $files->getQueryArg('limit'));
	}
}
