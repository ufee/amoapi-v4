<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Tests\TestCase;

class ServiceCrudTest extends TestCase
{
	public function testFindReturnsModel(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'id' => 15,
			'name' => 'Иван',
		]);

		$contact = $api->contacts()->find(15);

		$this->assertInstanceOf(Contact::class, $contact);
		$this->assertSame(15, $contact->id);
		$this->assertSame('Иван', $contact->name);
	}

	public function testFindReturnsNullOn404(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(404, '');

		$this->assertNull($api->contacts()->find(999));
	}

	public function testFindReturnsNullOn204(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(204, '');

		$this->assertNull($api->contacts()->find(1));
	}

	public function testFindWithSetsQueryArg(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, ['id' => 1, 'name' => 'A']);

		$api->contacts()->find(1, ['leads', 'customers']);

		$this->assertNotNull($api->lastQuery);
		$this->assertSame('leads,customers', $api->lastQuery->args['with']);
		$this->assertSame('GET', $api->lastQuery->method);
		$this->assertStringEndsWith('/api/v4/contacts/1', $api->lastQuery->url);
	}

	public function testFindByIdsUsesFilterFetchAll(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A'],
					(object) ['id' => 2, 'name' => 'B'],
				],
			],
		]);
		// fetchAll всегда пробует следующую страницу
		$api->pushResponse(204, '');

		$result = $api->contacts()->find([1, 2]);
		$this->assertCount(2, $result);
		$this->assertSame([1, 2], $result->fieldValues('id')->all());
	}

	public function testFindRejectsInvalidIdType(): void
	{
		$api = $this->makeStubApiClient();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Element ID must be integer/string or array');
		$api->contacts()->find(1.5);
	}

	public function testAddOneEntity(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 100, 'name' => 'New', 'request_id' => '0'],
				],
			],
		]);

		$result = $api->contacts()->add((object) ['name' => 'New']);
		$this->assertSame(100, $result->id);
		$this->assertSame('New', $result->name);
	}

	public function testAddManyEntities(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A'],
					(object) ['id' => 2, 'name' => 'B'],
				],
			],
		]);

		$result = $api->contacts()->add([
			['name' => 'A'],
			['name' => 'B'],
		]);
		$this->assertCount(2, $result);
		$this->assertSame(1, $result[0]->id);
	}

	public function testUpdateOneEntity(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'id' => 50,
			'name' => 'Updated',
			'updated_at' => 1700000000,
		]);

		$result = $api->contacts()->update(50, (object) ['name' => 'Updated']);
		$this->assertSame(50, $result->id);
		$this->assertSame('Updated', $result->name);
	}

	public function testUpdateManyEntities(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A1'],
					(object) ['id' => 2, 'name' => 'B1'],
				],
			],
		]);

		$result = $api->contacts()->update([
			['id' => 1, 'name' => 'A1'],
			['id' => 2, 'name' => 'B1'],
		]);
		$this->assertCount(2, $result);
		$this->assertSame('A1', $result[0]->name);
	}

	public function testPaginateFetchPageViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'leads' => [
					(object) ['id' => 7, 'name' => 'Deal'],
				],
			],
		]);

		$page = $api->leads()->paginate()->fetchPage();
		$this->assertCount(1, $page);
		$this->assertSame('Deal', $page->first()->name);
	}

	public function testFilterSetsFilterArgAndReturnsCollection(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 3, 'name' => 'Filtered'],
				],
			],
		]);
		$api->pushResponse(204, '');

		$paginate = $api->contacts()->filter(['name' => 'Filtered']);
		$all = $paginate->fetchAll();
		$this->assertCount(1, $all);
		$this->assertSame(3, $all->first()->id);
		$this->assertSame(['name' => 'Filtered'], $api->lastQuery->args['filter']);
	}
}
