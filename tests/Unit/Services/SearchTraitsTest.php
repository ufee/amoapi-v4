<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Tests\TestCase;

class SearchTraitsTest extends TestCase
{
	private function searchPage(array $contacts): array
	{
		return [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => $contacts,
			],
		];
	}

	public function testSearchByNameFiltersExactMatch(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->searchPage([
			(object) ['id' => 1, 'name' => 'VIP Client'],
			(object) ['id' => 2, 'name' => 'VIP Client Extra'],
		]));
		$api->pushResponse(204, '');

		$result = $api->contacts()->searchByName('VIP Client', 1);
		$this->assertCount(1, $result);
		$this->assertSame(1, $result->first()->id);
		$this->assertSame('vip client', $api->lastQuery->args['query'] ?? null);
	}

	public function testSearchByEmailFiltersExactMatch(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->searchPage([
			(object) [
				'id' => 1,
				'name' => 'A',
				'custom_fields_values' => [
					(object) [
						'field_id' => 10,
						'field_name' => 'Email',
						'field_code' => 'EMAIL',
						'field_type' => 'multitext',
						'values' => [(object) ['value' => 'a@example.com']],
					],
				],
			],
			(object) [
				'id' => 2,
				'name' => 'B',
				'custom_fields_values' => [
					(object) [
						'field_id' => 10,
						'field_name' => 'Email',
						'field_code' => 'EMAIL',
						'field_type' => 'multitext',
						'values' => [(object) ['value' => 'other@example.com']],
					],
				],
			],
		]));
		$api->pushResponse(204, '');

		$result = $api->contacts()->searchByEmail('a@example.com', 1);
		$this->assertCount(1, $result);
		$this->assertSame(1, $result->first()->id);
	}

	public function testSearchByPhoneRuMobileFiltersExactMatch(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('lang', 'ru');
		$api->pushResponse(200, $this->searchPage([
			(object) [
				'id' => 1,
				'name' => 'A',
				'custom_fields_values' => [
					(object) [
						'field_id' => 11,
						'field_name' => 'Телефон',
						'field_code' => 'PHONE',
						'field_type' => 'multitext',
						'values' => [(object) ['value' => '+7 (900) 111-22-33']],
					],
				],
			],
			(object) [
				'id' => 2,
				'name' => 'B',
				'custom_fields_values' => [
					(object) [
						'field_id' => 11,
						'field_name' => 'Телефон',
						'field_code' => 'PHONE',
						'field_type' => 'multitext',
						'values' => [(object) ['value' => '79009998877']],
					],
				],
			],
		]));
		$api->pushResponse(204, '');

		$result = $api->contacts()->searchByPhone('89001112233', 1);
		$this->assertCount(1, $result);
		$this->assertSame(1, $result->first()->id);
	}

	public function testSearchByCustomFieldExactMatch(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->searchPage([
			(object) [
				'id' => 1,
				'name' => 'A',
				'custom_fields_values' => [
					(object) [
						'field_id' => 12,
						'field_name' => 'Город',
						'field_code' => null,
						'field_type' => 'text',
						'values' => [(object) ['value' => 'Москва']],
					],
				],
			],
			(object) [
				'id' => 2,
				'name' => 'B',
				'custom_fields_values' => [
					(object) [
						'field_id' => 12,
						'field_name' => 'Город',
						'field_code' => null,
						'field_type' => 'text',
						'values' => [(object) ['value' => 'СПб']],
					],
				],
			],
		]));
		$api->pushResponse(204, '');

		$result = $api->contacts()->searchByCustomField('Москва', 'Город', 1);
		$this->assertCount(1, $result);
		$this->assertSame(1, $result->first()->id);
	}

	public function testSearchByNameSetsQueryArgOnLeads(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'leads' => [
					(object) ['id' => 7, 'name' => 'Deal One'],
				],
			],
		]);
		$api->pushResponse(204, '');

		$result = $api->leads()->searchByName('Deal One', 1);
		$this->assertCount(1, $result);
		$this->assertSame('deal one', $api->lastQuery->args['query']);
	}
}
