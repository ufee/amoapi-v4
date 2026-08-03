<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Services\Contacts;

/**
 * @group integration
 */
class ContactsApiTest extends IntegrationTestCase
{
	public function testCreateFindUpdateAndSearchContact(): void
	{
		$suffix = uniqid('itest_', false);
		$name = 'ITEST Contact ' . $suffix;
		$email = $suffix . '@example.com';
		$phone = '+7900' . substr((string) time(), -7);

		$contacts = $this->api->contacts();
		$this->assertInstanceOf(Contacts::class, $contacts);

		$contact = $contacts->create(['name' => $name]);
		$this->assertInstanceOf(Contact::class, $contact);

		$contact->cf()->byCode('EMAIL')->setValue($email);
		$contact->cf()->byCode('PHONE')->setValue($phone);
		$contact->attachTag('amoapi-v4-itest');

		$this->assertTrue($contact->save(), 'Не удалось создать контакт');
		$this->assertNotEmpty($contact->id);
		$this->trackDelete('/api/v4/contacts', (int) $contact->id);

		$found = $this->api->contacts()->find($contact->id);
		$this->assertInstanceOf(Contact::class, $found);
		$this->assertSame((int) $contact->id, (int) $found->id);
		$this->assertSame($name, $found->name);

		$updatedName = $name . ' updated';
		$found->name = $updatedName;
		$this->assertTrue($found->save(), 'Не удалось обновить контакт');

		$reloaded = $this->api->contacts()->find($contact->id);
		$this->assertSame($updatedName, $reloaded->name);

		$this->waitForSearch();

		$byEmail = $this->api->contacts()->searchByEmail($email, 1);
		$this->assertNotNull(
			$byEmail->find('id', $contact->id)->first(),
			'Контакт не найден через searchByEmail'
		);

		$byPhone = $this->api->contacts()->searchByPhone($phone, 1);
		$this->assertNotNull(
			$byPhone->find('id', $contact->id)->first(),
			'Контакт не найден через searchByPhone'
		);

		$byName = $this->api->contacts()->searchByName($updatedName, 1);
		$this->assertNotNull(
			$byName->find('id', $contact->id)->first(),
			'Контакт не найден через searchByName'
		);
	}

	public function testFindMissingContactReturnsNull(): void
	{
		$result = $this->api->contacts()->find(PHP_INT_MAX - 1);
		$this->assertNull($result);
	}
}
