<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

/**
 * @group integration
 */
class LinksApiTest extends IntegrationTestCase
{
	public function testAttachAndDetachLeadToContact(): void
	{
		$contact = $this->api->contacts()->create(['name' => $this->uniqueName('Contact link')]);
		$this->assertTrue($contact->save());
		$this->trackDelete('/api/v4/contacts', (int) $contact->id);

		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Lead link')]);
		$this->assertTrue($lead->save());
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$link = $contact->attachLead($lead);
		$this->assertNotFalse($link, 'Не удалось привязать сделку к контакту');

		$links = $this->api->links('contacts', $contact->id)->get(['to_entity_type' => 'leads']);
		$this->assertGreaterThan(0, $links->count());
		$this->assertNotNull($links->find('to_entity_id', $lead->id)->first());

		$this->assertTrue($contact->detachLead($lead), 'Не удалось отвязать сделку');
	}
}
