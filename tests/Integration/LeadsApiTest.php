<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Lead;
use Ufee\AmoV4\Services\Leads;

/**
 * @group integration
 */
class LeadsApiTest extends IntegrationTestCase
{
	public function testCreateFindUpdateAndSearchLead(): void
	{
		$name = $this->uniqueName('Lead');

		$leads = $this->api->leads();
		$this->assertInstanceOf(Leads::class, $leads);

		$lead = $leads->create(['name' => $name, 'price' => 100]);
		$lead->attachTag('amoapi-v4-itest');
		$this->assertTrue($lead->save(), 'Не удалось создать сделку');
		$this->assertNotEmpty($lead->id);
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$found = $this->api->leads()->find($lead->id);
		$this->assertInstanceOf(Lead::class, $found);
		$this->assertSame($name, $found->name);

		$updated = $name . ' updated';
		$found->name = $updated;
		$found->price = 250;
		$this->assertTrue($found->save(), 'Не удалось обновить сделку');

		$reloaded = $this->api->leads()->find($lead->id);
		$this->assertSame($updated, $reloaded->name);
		$this->assertSame(250, (int) $reloaded->price);

		$this->waitForSearch();
		$byName = $this->api->leads()->searchByName($updated, 1);
		$this->assertNotNull(
			$byName->find('id', $lead->id)->first(),
			'Сделка не найдена через searchByName'
		);
	}
}
