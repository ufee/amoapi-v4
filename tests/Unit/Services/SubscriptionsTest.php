<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Subscription;
use Ufee\AmoV4\Services\Subscriptions;
use Ufee\AmoV4\Tests\TestCase;

class SubscriptionsTest extends TestCase
{
	public function testServicePathForLeads(): void
	{
		$service = $this->service('subscriptions', 'leads', 42);
		$this->assertInstanceOf(Subscriptions::class, $service);
		$this->assertSame('/api/v4/leads/42/subscriptions', $service->api_path);
		$this->assertSame('subscriptions', $service->entity_key);
	}

	public function testServicePathForCustomers(): void
	{
		$service = $this->service('subscriptions', 'customers', '99');
		$this->assertSame('/api/v4/customers/99/subscriptions', $service->api_path);
	}

	public function testCreateModel(): void
	{
		$model = $this->service('subscriptions', 'leads', 1)->create([
			'subscriber_id' => 7,
			'type' => 'user',
		]);
		$this->assertInstanceOf(Subscription::class, $model);
		$this->assertSame(7, $model->subscriber_id);
	}

	public function testRequiresEntityArgs(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('entity_type and entity_id');
		$this->service('subscriptions', 'leads');
	}

	public function testRejectsUnsupportedEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('supports only leads or customers');
		$this->service('subscriptions', 'contacts', 1);
	}

	public function testRejectsInvalidEntityId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('entity_id must be integer/string');
		$this->service('subscriptions', 'leads', 'abc');
	}

	public function testLeadSubscriptionsHelperBuildsService(): void
	{
		$lead = $this->service('leads')->create(['id' => 55, 'name' => 'L']);
		$service = $lead->subscriptions();
		$this->assertInstanceOf(Subscriptions::class, $service);
		$this->assertSame('/api/v4/leads/55/subscriptions', $service->api_path);
	}
}
