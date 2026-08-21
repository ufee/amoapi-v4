<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Enums\CustomFields\EmailEnum;
use Ufee\AmoV4\Enums\CustomFields\PhoneEnum;
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

		$contact->cf()->byCode(EmailEnum::CODE)->setValue($email);
		$contact->cf()->byCode(PhoneEnum::CODE)->setValue($phone);
		$contact->attachTag('amoapi-v4-itest');

		$this->assertTrue($contact->save(), 'Не удалось создать контакт');
		$this->assertNotEmpty($contact->id);
		$this->trackDelete('/api/v4/contacts', (int) $contact->id);

		$found = $this->api->contacts()->find($contact->id);
		$this->assertInstanceOf(Contact::class, $found);
		$this->assertSame((int) $contact->id, (int) $found->id);
		$this->assertSame($name, $found->name);
		$this->assertSame($email, $found->cf()->byCode(EmailEnum::CODE)->getValue());
		$this->assertSame(EmailEnum::WORK, $found->cf()->byCode(EmailEnum::CODE)->getEnumCode());
		$this->assertSame($phone, $found->cf()->byCode(PhoneEnum::CODE)->getValue());
		$this->assertSame(PhoneEnum::WORK, $found->cf()->byCode(PhoneEnum::CODE)->getEnumCode());

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

	public function testResetPhoneAndEmailClearsValues(): void
	{
		$suffix = uniqid('itest_reset_', false);
		$contact = $this->api->contacts()->create(['name' => 'ITEST Reset CF ' . $suffix]);
		$contact->cf()->byCode(EmailEnum::CODE)->setValue($suffix . '@example.com');
		$contact->cf()->byCode(PhoneEnum::CODE)->setValue('+7900' . substr((string) time(), -7));
		$this->assertTrue($contact->save(), 'Не удалось создать контакт');
		$this->assertNotEmpty($contact->id);
		$this->trackDelete('/api/v4/contacts', (int) $contact->id);

		$found = $this->api->contacts()->find($contact->id);
		$found->cf()->byCode(PhoneEnum::CODE)->reset();
		$found->cf()->byCode(EmailEnum::CODE)->reset();
		$this->assertTrue($found->save(), 'Не удалось очистить Phone/Email');

		$reloaded = $this->api->contacts()->find($contact->id);
		$this->assertNull($reloaded->cf()->byCode(PhoneEnum::CODE)->getValue());
		$this->assertNull($reloaded->cf()->byCode(EmailEnum::CODE)->getValue());
	}

	public function testFindMissingContactReturnsNull(): void
	{
		$result = $this->api->contacts()->find(PHP_INT_MAX - 1);
		$this->assertNull($result);
	}
}
