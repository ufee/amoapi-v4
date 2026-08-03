<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Paginate;
use Ufee\AmoV4\Exceptions\AmoException;
use Ufee\AmoV4\Tests\Support\StubQuery;
use Ufee\AmoV4\Tests\TestCase;

class PaginateTest extends TestCase
{
	private function apiWithToken(): \Ufee\AmoV4\ApiClient
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('stub-token');
		$api->setParam('query_delay', 0);
		return $api;
	}

	public function testFetchPageParsesEmbeddedEntities(): void
	{
		$api = $this->apiWithToken();
		$query = new StubQuery($api);
		$query->setMethod('GET')->setUrl('/api/v4/contacts');
		$query->pushResponse(200, [
			'_page' => 1,
			'_total_items' => 2,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A'],
					(object) ['id' => 2, 'name' => 'B'],
				],
			],
		]);

		$paginate = new Paginate($query, $api->contacts());
		$page = $paginate->fetchPage();

		$this->assertSame(1, $paginate->pageNum());
		$this->assertCount(2, $page);
		$this->assertSame('A', $page->first()->name);
	}

	public function testFetchPageHandles204(): void
	{
		$api = $this->apiWithToken();
		$query = new StubQuery($api);
		$query->pushResponse(204, '');

		$paginate = new Paginate($query, $api->contacts());
		$page = $paginate->fetchPage();

		$this->assertCount(0, $page);
		$this->assertFalse($paginate->hasNext());
	}

	public function testFetchAllAcrossPages(): void
	{
		$api = $this->apiWithToken();
		$query = new StubQuery($api);
		$query->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [
				'next' => (object) ['href' => 'https://example.amocrm.ru/api/v4/contacts?page=2'],
			],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A'],
				],
			],
		]);
		$query->pushResponse(200, [
			'_page' => 2,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 2, 'name' => 'B'],
				],
			],
		]);

		$paginate = new Paginate($query, $api->contacts());
		$all = $paginate->maxPages(2)->fetchAll();

		$this->assertCount(2, $all);
		$this->assertSame([1, 2], $all->fieldValues('id')->all());
	}

	public function testLoadThrowsWithoutEntityKey(): void
	{
		$api = $this->apiWithToken();
		$query = new StubQuery($api);
		$query->pushResponse(200, [
			'_embedded' => (object) ['leads' => []],
		]);

		$paginate = new Paginate($query, $api->contacts());

		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('no contacts');
		$paginate->fetchPage();
	}

	public function testMaxRowsSetsQueryArg(): void
	{
		$api = $this->makeApiClient();
		$query = new StubQuery($api);
		$paginate = new Paginate($query, $api->contacts());
		$paginate->maxRows(10);

		$this->assertSame(10, $query->args['limit']);
	}

	public function testRewindResetsState(): void
	{
		$api = $this->apiWithToken();
		$query = new StubQuery($api);
		$query->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'A']],
			],
		]);
		$query->pushResponse(200, [
			'_page' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'contacts' => [(object) ['id' => 1, 'name' => 'A']],
			],
		]);

		$paginate = new Paginate($query, $api->contacts());
		$paginate->fetchPage();
		$paginate->setPageNum(2);
		$paginate->rewind();

		$this->assertSame(1, $paginate->pageNum());
		$this->assertCount(1, $paginate->fetchPage());
	}
}
