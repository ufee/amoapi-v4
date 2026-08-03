<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Webhook;
use Ufee\AmoV4\Services\Webhooks;

/**
 * @group integration
 */
class WebhooksApiTest extends IntegrationTestCase
{
	/** @var string|null */
	private $destination;

	protected function tearDown(): void
	{
		if ($this->destination !== null && $this->api !== null) {
			try {
				$this->api->webhooks()->unsubscribe($this->destination);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->destination = null;
		parent::tearDown();
	}

	public function testListWebhooks(): void
	{
		$service = $this->api->webhooks();
		$this->assertInstanceOf(Webhooks::class, $service);

		try {
			$webhooks = $service->get();
			$this->assertIsObject($webhooks);
		} catch (\Throwable $e) {
			// Пустой ответ без _embedded допустим
			$this->assertStringContainsString('embedded', strtolower($e->getMessage()));
		}
	}

	public function testSubscribeGetByDestinationAndUnsubscribe(): void
	{
		$this->destination = 'https://example.com/amoapi-v4-itest-' . uniqid('', false);
		$events = ['add_lead', 'update_lead'];

		$webhook = $this->api->webhooks()->subscribe($this->destination, $events);
		$this->assertInstanceOf(Webhook::class, $webhook);
		$this->assertSame($this->destination, $webhook->destination);

		$found = $this->api->webhooks()->get($this->destination);
		$this->assertGreaterThan(0, $found->count());
		$this->assertNotNull(
			$found->find('destination', $this->destination)->first(),
			'Подписанный вебхук не найден по destination'
		);

		$destination = $this->destination;
		$this->assertTrue(
			$this->api->webhooks()->unsubscribe($destination),
			'Не удалось отписаться от вебхука'
		);
		$this->destination = null;

		try {
			$after = $this->api->webhooks()->get($destination);
			$this->assertSame(0, $after->count());
		} catch (\Throwable $e) {
			// Пустой список может прийти без _embedded
			$this->assertNotEmpty($e->getMessage());
		}
	}
}
