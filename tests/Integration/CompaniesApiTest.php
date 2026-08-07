<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Company;
use Ufee\AmoV4\Enums\CustomFields\EmailEnum;
use Ufee\AmoV4\Services\Companies;

/**
 * @group integration
 */
class CompaniesApiTest extends IntegrationTestCase
{
	public function testCreateFindUpdateAndSearchCompany(): void
	{
		$suffix = uniqid('itest_', false);
		$name = 'ITEST Company ' . $suffix;
		$email = $suffix . '@example.com';

		$companies = $this->api->companies();
		$this->assertInstanceOf(Companies::class, $companies);

		$company = $companies->create(['name' => $name]);
		$company->cf()->byCode(EmailEnum::CODE)->setValue($email);
		$company->attachTag('amoapi-v4-itest');
		$this->assertTrue($company->save(), 'Не удалось создать компанию');
		$this->assertNotEmpty($company->id);
		$this->trackDelete('/api/v4/companies', (int) $company->id);

		$found = $this->api->companies()->find($company->id);
		$this->assertInstanceOf(Company::class, $found);
		$this->assertSame($name, $found->name);
		$this->assertSame($email, $found->cf()->byCode(EmailEnum::CODE)->getValue());
		$this->assertSame(EmailEnum::WORK, $found->cf()->byCode(EmailEnum::CODE)->getEnumCode());

		$updated = $name . ' updated';
		$found->name = $updated;
		$this->assertTrue($found->save(), 'Не удалось обновить компанию');

		$reloaded = $this->api->companies()->find($company->id);
		$this->assertSame($updated, $reloaded->name);

		$this->waitForSearch();
		$byEmail = $this->api->companies()->searchByEmail($email, 1);
		$this->assertNotNull(
			$byEmail->find('id', $company->id)->first(),
			'Компания не найдена через searchByEmail'
		);

		$byName = $this->api->companies()->searchByName($updated, 1);
		$this->assertNotNull(
			$byName->find('id', $company->id)->first(),
			'Компания не найдена через searchByName'
		);
	}
}
