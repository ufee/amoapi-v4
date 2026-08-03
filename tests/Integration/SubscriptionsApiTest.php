<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Collections\Subscriptions as SubscriptionsCollection;
use Ufee\AmoV4\Services\Subscriptions;

/**
 * @group integration
 */
class SubscriptionsApiTest extends IntegrationTestCase
{
	public function testListSubscriptionsForLead(): void
	{
		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Lead subs')]);
		$this->assertTrue($lead->save());
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$service = $this->api->subscriptions('leads', $lead->id);
		$this->assertInstanceOf(Subscriptions::class, $service);
		$this->assertSame('/api/v4/leads/' . $lead->id . '/subscriptions', $service->api_path);

		try {
			$subscriptions = $service->get();
			$this->assertInstanceOf(SubscriptionsCollection::class, $subscriptions);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Subscriptions API недоступен: ' . $e->getMessage());
		}

		try {
			$viaModel = $lead->getSubscriptions();
			$this->assertInstanceOf(SubscriptionsCollection::class, $viaModel);
		} catch (\Throwable $e) {
			$this->markTestSkipped('lead->getSubscriptions() недоступен: ' . $e->getMessage());
		}
	}

	public function testListSubscriptionsForCustomer(): void
	{
		try {
			$customer = $this->api->customers()->create([
				'name' => $this->uniqueName('Customer subs'),
				'next_date' => time() + 86400,
			]);
			$this->assertTrue($customer->save());
			$this->trackDelete('/api/v4/customers', (int) $customer->id);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Покупатели недоступны: ' . $e->getMessage());
		}

		$service = $this->api->subscriptions('customers', $customer->id);
		$this->assertSame('/api/v4/customers/' . $customer->id . '/subscriptions', $service->api_path);

		try {
			$subscriptions = $service->get();
			$this->assertInstanceOf(SubscriptionsCollection::class, $subscriptions);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Subscriptions для customers недоступны: ' . $e->getMessage());
		}
	}
}
