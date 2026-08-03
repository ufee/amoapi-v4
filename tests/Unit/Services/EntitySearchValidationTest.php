<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Exceptions\AmoException;
use Ufee\AmoV4\Tests\Support\EntityFixtures;
use Ufee\AmoV4\Tests\TestCase;

class EntitySearchValidationTest extends TestCase
{
	/**
	 * @dataProvider searchByNameProvider
	 */
	public function testSearchByNameRejectsShortQuery(string $method, array $args): void
	{
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('Invalid search name');
		$this->service($method, ...$args)->searchByName('ab');
	}

	/**
	 * @dataProvider searchByEmailPhoneProvider
	 */
	public function testSearchByEmailRejectsInvalidEmail(string $method, array $args): void
	{
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('Invalid search email value');
		$this->service($method, ...$args)->searchByEmail('not-an-email');
	}

	/**
	 * @dataProvider searchByEmailPhoneProvider
	 */
	public function testSearchByPhoneRejectsShortPhone(string $method, array $args): void
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid search phone value');
		$this->service($method, ...$args)->searchByPhone('12345');
	}

	/**
	 * @dataProvider searchByEmailPhoneProvider
	 */
	public function testSearchByPhoneRejectsUnknownFormat(string $method, array $args): void
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid search format');
		$this->service($method, ...$args)->searchByPhone('+79001234567', 0, [], 'unknown_format');
	}

	/**
	 * @dataProvider searchByCustomFieldProvider
	 */
	public function testSearchByCustomFieldRejectsShortQuery(string $method, array $args): void
	{
		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('Invalid cf query filter value');
		$this->service($method, ...$args)->searchByCustomField('ab', 'Город');
	}

	public function searchByNameProvider(): array
	{
		return EntityFixtures::searchByNameProvider();
	}

	public function searchByEmailPhoneProvider(): array
	{
		return EntityFixtures::searchByEmailPhoneProvider();
	}

	public function searchByCustomFieldProvider(): array
	{
		return EntityFixtures::searchByCustomFieldProvider();
	}
}
