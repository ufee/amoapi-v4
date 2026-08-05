<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Unsorted;
use Ufee\AmoV4\Services\Unsorted as UnsortedService;

/**
 * @group integration
 */
class UnsortedApiTest extends IntegrationTestCase
{
	/** @var string|null */
	private $pendingUid;

	protected function tearDown(): void
	{
		if ($this->pendingUid !== null && $this->api !== null) {
			try {
				$this->api->unsorted()->decline($this->pendingUid);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->pendingUid = null;
		parent::tearDown();
	}

	public function testSummaryAndList(): void
	{
		$service = $this->api->unsorted();
		$this->assertInstanceOf(UnsortedService::class, $service);

		$summary = $service->summary();
		$this->assertIsObject($summary);
		$this->assertTrue(property_exists($summary, 'total'));

		try {
			$page = $service->maxPageRows(5)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			// Пустой ответ без _embedded допустим
			$this->assertStringContainsString('embedded', strtolower($e->getMessage()));
		}
	}

	public function testAddFormsFindAndDecline(): void
	{
		$pipelineId = $this->pipelineIdWithUnsorted();
		$sourceUid = 'amoapi-v4-itest-' . uniqid('', false);

		$created = $this->api->unsorted()->addForms([
			'source_name' => 'amoapi-v4 itest',
			'source_uid' => $sourceUid,
			'pipeline_id' => $pipelineId,
			'created_at' => time(),
			'metadata' => [
				'form_id' => 'amoapi_v4_itest',
				'form_name' => 'amoapi-v4 itest',
				'form_page' => 'https://example.com/amoapi-v4-itest',
				'form_sent_at' => time(),
				'ip' => '127.0.0.1',
				'referer' => 'https://example.com',
			],
			'_embedded' => [
				'leads' => [
					['name' => $this->uniqueName('UnsortedLead')],
				],
				'contacts' => [
					['name' => $this->uniqueName('UnsortedContact')],
				],
			],
		]);

		$this->assertIsObject($created);
		$this->assertNotEmpty($created->uid);
		$this->pendingUid = (string) $created->uid;

		$found = $this->api->unsorted()->find($this->pendingUid);
		$this->assertInstanceOf(Unsorted::class, $found);
		$this->assertSame($this->pendingUid, $found->uid);

		$declined = $found->decline();
		$this->assertInstanceOf(Unsorted::class, $declined);
		$this->pendingUid = null;
	}

	private function pipelineIdWithUnsorted(): int
	{
		$pipelines = $this->api->pipelines()->get();
		$withUnsorted = $pipelines->find('is_unsorted_on', true)->first();
		if ($withUnsorted) {
			return (int) $withUnsorted->id;
		}

		$first = $pipelines->first();
		if (!$first) {
			$this->markTestSkipped('В аккаунте нет воронок');
		}

		// Пробуем первую воронку — если unsorted выключен, API вернёт ошибку
		return (int) $first->id;
	}
}
