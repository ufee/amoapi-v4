<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Tests\TestCase;

class ModelOnCreateTest extends TestCase
{
	public function testOnCreateRunsAfterSuccessfulCreate(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 55, 'name' => 'Ivan', 'request_id' => '0'],
				],
			],
		]);

		$calledWithId = null;
		$contact = $api->contacts()->create(['name' => 'Ivan']);
		$contact->onCreate(function ($model) use (&$calledWithId) {
			$calledWithId = $model->id;
		});

		$this->assertTrue($contact->save());
		$this->assertSame(55, $calledWithId);
		$this->assertSame(55, $contact->id);
	}

	public function testOnCreateDoesNotRunOnUpdate(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, ['id' => 10, 'name' => 'Updated']);

		$called = false;
		$contact = $api->contacts()->create(['id' => 10, 'name' => 'Old']);
		$contact->onCreate(function () use (&$called) {
			$called = true;
		});
		$contact->name = 'Updated';

		$this->assertTrue($contact->save());
		$this->assertFalse($called);
	}

	public function testLeadCreateContactRegistersOnCreate(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create([
			'id' => 100,
			'name' => 'Deal',
			'responsible_user_id' => 1,
		]);

		// attachLead внутри createContact
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => null,
						'to_entity_id' => 100,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);

		$contact = $lead->createContact();
		$this->assertInstanceOf(Contact::class, $contact);
		$this->assertSame(1, $contact->responsible_user_id);

		// save контакта
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 77, 'name' => 'Ivan', 'request_id' => '0'],
				],
			],
		]);
		// onCreate → lead->attachContact($contact)
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 100,
						'to_entity_id' => 77,
						'to_entity_type' => 'contacts',
					],
				],
			],
		]);

		$contact->name = 'Ivan';
		$this->assertTrue($contact->save());
		$this->assertSame(77, $contact->id);
	}
}
