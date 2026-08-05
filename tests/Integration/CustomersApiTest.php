<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Customer;
use Ufee\AmoV4\Services\Customers;

/**
 * @group integration
 */
class CustomersApiTest extends IntegrationTestCase
{
	public function testCreateFindUpdateAndSearchCustomer(): void
	{
		$name = $this->uniqueName('Customer');

		$customers = $this->api->customers();
		$this->assertInstanceOf(Customers::class, $customers);

		$customer = $customers->create([
			'name' => $name,
			'next_date' => time() + 86400,
		]);
		$customer->attachTag('amoapi-v4-itest');
		$this->assertTrue($customer->save(), 'Не удалось создать покупателя');
		$this->assertNotEmpty($customer->id);
		$this->trackDelete('/api/v4/customers', (int) $customer->id);

		$found = $this->api->customers()->find($customer->id);
		$this->assertInstanceOf(Customer::class, $found);
		$this->assertSame($name, $found->name);

		$updated = $name . ' updated';
		$found->name = $updated;
		$this->assertTrue($found->save(), 'Не удалось обновить покупателя');

		$reloaded = $this->api->customers()->find($customer->id);
		$this->assertSame($updated, $reloaded->name);

		$this->waitForSearch();
		$byName = $this->api->customers()->searchByName($updated, 1);
		$this->assertNotNull(
			$byName->find('id', $customer->id)->first(),
			'Покупатель не найден через searchByName'
		);
	}
}
