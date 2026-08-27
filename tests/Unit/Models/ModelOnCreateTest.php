<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\Company;
use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Models\Lead;
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

	public function testContactCreateLeadRegistersOnCreate(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create([
			'id' => 10,
			'name' => 'Ivan',
			'responsible_user_id' => 1,
			'_embedded' => [
				'companies' => [(object) ['id' => 9]],
			],
		]);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => null,
						'to_entity_id' => 10,
						'to_entity_type' => 'contacts',
					],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => null,
						'to_entity_id' => 9,
						'to_entity_type' => 'companies',
					],
				],
			],
		]);

		$lead = $contact->createLead();
		$this->assertInstanceOf(Lead::class, $lead);
		$this->assertSame(1, $lead->responsible_user_id);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'leads' => [
					(object) ['id' => 200, 'name' => 'Deal', 'request_id' => '0'],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 10,
						'to_entity_id' => 200,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);

		$lead->name = 'Deal';
		$this->assertTrue($lead->save());
		$this->assertSame(200, $lead->id);
	}

	public function testCompanyCreateLeadRegistersOnCreate(): void
	{
		$api = $this->makeStubApiClient();
		$company = $api->companies()->create([
			'id' => 9,
			'name' => 'Romashka',
			'responsible_user_id' => 2,
		]);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => null,
						'to_entity_id' => 9,
						'to_entity_type' => 'companies',
					],
				],
			],
		]);

		$lead = $company->createLead();
		$this->assertInstanceOf(Lead::class, $lead);
		$this->assertSame(2, $lead->responsible_user_id);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'leads' => [
					(object) ['id' => 300, 'name' => 'Deal', 'request_id' => '0'],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 9,
						'to_entity_id' => 300,
						'to_entity_type' => 'leads',
					],
				],
			],
		]);

		$lead->name = 'Deal';
		$this->assertTrue($lead->save());
		$this->assertSame(300, $lead->id);
	}

	public function testCompanyCreateContactRegistersOnCreate(): void
	{
		$api = $this->makeStubApiClient();
		$company = $api->companies()->create([
			'id' => 9,
			'name' => 'Romashka',
			'responsible_user_id' => 2,
		]);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => null,
						'to_entity_id' => 9,
						'to_entity_type' => 'companies',
					],
				],
			],
		]);

		$contact = $company->createContact();
		$this->assertInstanceOf(Contact::class, $contact);
		$this->assertSame(2, $contact->responsible_user_id);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 88, 'name' => 'Ivan', 'request_id' => '0'],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 9,
						'to_entity_id' => 88,
						'to_entity_type' => 'contacts',
					],
				],
			],
		]);

		$contact->name = 'Ivan';
		$this->assertTrue($contact->save());
		$this->assertSame(88, $contact->id);
	}

	public function testContactCreateCompanyRegistersOnCreate(): void
	{
		$api = $this->makeStubApiClient();
		$contact = $api->contacts()->create([
			'id' => 10,
			'name' => 'Ivan',
			'responsible_user_id' => 1,
		]);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => null,
						'to_entity_id' => 10,
						'to_entity_type' => 'contacts',
					],
				],
			],
		]);

		$company = $contact->createCompany();
		$this->assertInstanceOf(Company::class, $company);
		$this->assertSame(1, $company->responsible_user_id);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'companies' => [
					(object) ['id' => 90, 'name' => 'Romashka', 'request_id' => '0'],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 10,
						'to_entity_id' => 90,
						'to_entity_type' => 'companies',
					],
				],
			],
		]);

		$company->name = 'Romashka';
		$this->assertTrue($company->save());
		$this->assertSame(90, $company->id);
	}

	public function testLeadCreateCompanyRegistersOnCreate(): void
	{
		$api = $this->makeStubApiClient();
		$lead = $api->leads()->create([
			'id' => 100,
			'name' => 'Deal',
			'responsible_user_id' => 1,
		]);

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

		$company = $lead->createCompany();
		$this->assertInstanceOf(Company::class, $company);
		$this->assertSame(1, $company->responsible_user_id);

		$api->pushResponse(200, [
			'_embedded' => (object) [
				'companies' => [
					(object) ['id' => 91, 'name' => 'Romashka', 'request_id' => '0'],
				],
			],
		]);
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'links' => [
					(object) [
						'entity_id' => 100,
						'to_entity_id' => 91,
						'to_entity_type' => 'companies',
					],
				],
			],
		]);

		$company->name = 'Romashka';
		$this->assertTrue($company->save());
		$this->assertSame(91, $company->id);
	}
}
