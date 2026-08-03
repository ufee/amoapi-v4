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
}
