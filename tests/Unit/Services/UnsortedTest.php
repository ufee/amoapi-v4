<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Collections\Unsorteds;
use Ufee\AmoV4\Models\Unsorted;
use Ufee\AmoV4\Services\Unsorted as UnsortedService;
use Ufee\AmoV4\Tests\TestCase;

class UnsortedTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('unsorted');

		$this->assertInstanceOf(UnsortedService::class, $service);
		$this->assertSame('/api/v4/leads/unsorted', $service->api_path);
		$this->assertSame('unsorted', $service->entity_key);
		$this->assertSame('\\' . Unsorted::class, $service->entity_model);
		$this->assertSame('\\' . Unsorteds::class, $service->entity_collection);
	}

	public function testAddAndUpdateAreDisabled(): void
	{
		$service = $this->service('unsorted');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('addSip');
		$service->add(['source_name' => 'x']);
	}

	public function testUpdateIsDisabled(): void
	{
		$service = $this->service('unsorted');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('can not be updated');
		$service->update('uid', (object) []);
	}

	public function testFindRejectsInvalidUid(): void
	{
		$service = $this->service('unsorted');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('non-empty string');
		$service->find(123);
	}

	public function testFindByUidViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'uid' => 'abc123',
			'category' => 'forms',
			'pipeline_id' => 10,
		]);

		$item = $api->unsorted()->find('abc123');

		$this->assertInstanceOf(Unsorted::class, $item);
		$this->assertSame('abc123', $item->uid);
		$this->assertStringEndsWith('/api/v4/leads/unsorted/abc123', $api->lastQuery->url);
	}

	public function testFindByUidsUsesFilter(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'unsorted' => [
					(object) ['uid' => 'u1', 'category' => 'sip'],
					(object) ['uid' => 'u2', 'category' => 'forms'],
				],
			],
		]);
		$api->pushResponse(204, '');

		$result = $api->unsorted()->find(['u1', 'u2']);
		$this->assertCount(2, $result);
		$this->assertSame(['u1', 'u2'], array_values($result->fieldValues('uid')->all()));
	}

	public function testAddSipAndAddForms(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'unsorted' => [
					(object) ['uid' => 'sip-1', 'category' => 'sip'],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'unsorted' => [
					(object) ['uid' => 'form-1', 'category' => 'forms'],
					(object) ['uid' => 'form-2', 'category' => 'forms'],
				],
			],
		]);

		$sip = $api->unsorted()->addSip([
			'source_name' => 'PBX',
			'source_uid' => 'src-1',
			'pipeline_id' => 1,
			'metadata' => ['phone' => 79001112233],
			'_embedded' => ['leads' => [['name' => 'Call']]],
		]);
		$this->assertSame('sip-1', $sip->uid);
		$this->assertStringEndsWith('/api/v4/leads/unsorted/sip', $api->lastQuery->url);

		$forms = $api->unsorted()->addForms([
			['source_name' => 'A', 'source_uid' => 's1', 'pipeline_id' => 1, 'metadata' => ['form_id' => '1'], '_embedded' => ['leads' => [['name' => 'A']]]],
			['source_name' => 'B', 'source_uid' => 's2', 'pipeline_id' => 1, 'metadata' => ['form_id' => '2'], '_embedded' => ['leads' => [['name' => 'B']]]],
		]);
		$this->assertCount(2, $forms);
		$this->assertStringEndsWith('/api/v4/leads/unsorted/forms', $api->lastQuery->url);
	}

	public function testAddByCategoryRejectsEmptyPayload(): void
	{
		$api = $this->makeStubApiClient();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('can not be empty');
		$api->unsorted()->addForms([]);
	}

	public function testAcceptDeclineLinkAndSummary(): void
	{
		$api = $this->makeStubApiClient();
		$uid = 'chat-uid-1';

		$api->pushResponse(200, ['uid' => $uid, 'category' => 'forms', 'account_id' => 1]);
		$accepted = $api->unsorted()->accept($uid, ['user_id' => 5, 'status_id' => 20]);
		$this->assertInstanceOf(Unsorted::class, $accepted);
		$this->assertStringEndsWith('/api/v4/leads/unsorted/' . $uid . '/accept', $api->lastQuery->url);
		$this->assertSame('POST', $api->lastQuery->method);

		$api->pushResponse(200, ['uid' => $uid, 'category' => 'forms']);
		$declined = $api->unsorted()->decline($uid, ['user_id' => 5]);
		$this->assertSame($uid, $declined->uid);
		$this->assertStringEndsWith('/decline', $api->lastQuery->url);
		$this->assertSame('DELETE', $api->lastQuery->method);

		$api->pushResponse(200, ['uid' => $uid, 'category' => 'chats']);
		$linked = $api->unsorted()->link($uid, [
			'entity_id' => 99,
			'entity_type' => 'leads',
		], 7);
		$this->assertSame($uid, $linked->uid);
		$this->assertStringEndsWith('/link', $api->lastQuery->url);
		$this->assertSame([
			'link' => ['entity_id' => 99, 'entity_type' => 'leads'],
			'user_id' => 7,
		], $api->lastQuery->json_data);

		$api->pushResponse(200, [
			'total' => 3,
			'accepted' => 1,
			'declined' => 1,
			'average_sort_time' => 10,
			'categories' => (object) ['forms' => 2],
		]);
		$summary = $api->unsorted()->summary(['pipeline_id' => 10]);
		$this->assertSame(3, $summary->total);
		$this->assertSame(['pipeline_id' => 10], $api->lastQuery->args['filter']);
	}

	public function testLinkValidation(): void
	{
		$api = $this->makeStubApiClient();

		try {
			$api->unsorted()->link('u1', ['entity_id' => 0, 'entity_type' => 'leads']);
			$this->fail('Expected InvalidArgumentException for entity_id');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('entity_id', $e->getMessage());
		}

		try {
			$api->unsorted()->link('u1', ['entity_id' => 1, 'entity_type' => 'contacts']);
			$this->fail('Expected InvalidArgumentException for entity_type');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('entity_type', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('User ID');
		$api->unsorted()->link('u1', ['entity_id' => 1, 'entity_type' => 'leads'], 0);
	}

	public function testModelDelegatesAcceptDeclineLinkAndSaveThrows(): void
	{
		$service = $this->getMockBuilder(UnsortedService::class)
			->disableOriginalConstructor()
			->onlyMethods(['accept', 'decline', 'link'])
			->getMock();

		$service->expects($this->once())
			->method('accept')
			->with('uid-1', ['status_id' => 2])
			->willReturn(new Unsorted(['uid' => 'uid-1'], $service));
		$service->expects($this->once())
			->method('decline')
			->with('uid-1', [])
			->willReturn(new Unsorted(['uid' => 'uid-1'], $service));
		$service->expects($this->once())
			->method('link')
			->with('uid-1', ['entity_id' => 1, 'entity_type' => 'customers'], 9)
			->willReturn(new Unsorted(['uid' => 'uid-1'], $service));

		$model = new Unsorted(['uid' => 'uid-1', 'category' => 'chats'], $service);
		$model->accept(['status_id' => 2]);
		$model->decline();
		$model->link(['entity_id' => 1, 'entity_type' => 'customers'], 9);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('addSip');
		$model->save();
	}
}
