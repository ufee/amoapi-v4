<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Webhook;
use Ufee\AmoV4\Services\Webhooks;
use Ufee\AmoV4\Tests\TestCase;

class WebhooksTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('webhooks');

		$this->assertInstanceOf(Webhooks::class, $service);
		$this->assertSame('/api/v4/webhooks', $service->api_path);
		$this->assertSame('webhooks', $service->entity_key);
	}

	public function testCreateModelHasRequiredFields(): void
	{
		$webhook = $this->service('webhooks')->create([
			'destination' => 'https://example.com/hook',
			'settings' => ['add_lead'],
		]);

		$this->assertInstanceOf(Webhook::class, $webhook);
		$this->assertSame('https://example.com/hook', $webhook->destination);
		$this->assertSame(['add_lead'], $webhook->settings);

		$payload = $webhook->getChangedRawData();
		$this->assertSame('https://example.com/hook', $payload->destination);
		$this->assertSame(['add_lead'], $payload->settings);
	}

	public function testModelUnsubscribeDelegatesToService(): void
	{
		$service = $this->getMockBuilder(Webhooks::class)
			->disableOriginalConstructor()
			->onlyMethods(['unsubscribe'])
			->getMock();

		$service->expects($this->once())
			->method('unsubscribe')
			->with('https://example.com/hook')
			->willReturn(true);

		$webhook = new Webhook([
			'destination' => 'https://example.com/hook',
			'settings' => ['add_lead'],
		], $service);

		$this->assertTrue($webhook->unsubscribe());
	}

	public function testGetSubscribeAndUnsubscribeViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$url = 'https://example.com/amo-hook';

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'webhooks' => [
					(object) [
						'destination' => $url,
						'settings' => ['add_lead'],
					],
				],
			],
		]);
		$list = $api->webhooks()->get($url);
		$this->assertCount(1, $list);
		$this->assertSame(['destination' => $url], $api->lastQuery->args['filter']);

		$api->pushResponse(200, ['_embedded' => (object) []]);
		$empty = $api->webhooks()->get();
		$this->assertCount(0, $empty);

		$api->pushResponse(200, [
			'destination' => $url,
			'settings' => ['add_lead', 'update_lead'],
		]);
		$created = $api->webhooks()->subscribe($url, ['add_lead', 'update_lead']);
		$this->assertInstanceOf(Webhook::class, $created);
		$this->assertSame($url, $created->destination);
		$this->assertSame('POST', $api->lastQuery->method);

		$api->pushResponse(204, '');
		$this->assertTrue($api->webhooks()->unsubscribe($url));
		$this->assertSame('DELETE', $api->lastQuery->method);
	}

	public function testGetWithoutEmbeddedThrows(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, ['detail' => 'ok']);

		$this->expectException(\Ufee\AmoV4\Exceptions\AmoException::class);
		$this->expectExceptionMessage('embedded not found');
		$api->webhooks()->get();
	}
}
