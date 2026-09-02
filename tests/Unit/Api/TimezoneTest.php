<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Cache\AbstractStorage;
use Ufee\AmoV4\Tests\TestCase;

class TimezoneTest extends TestCase
{
	public function testTimezoneResolvedFromAccountSettings(): void
	{
		$api = $this->makeStubApiClient();
		$storage = new AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);

		$this->assertNull($api->getParam('timezone'));

		$api->pushResponse(200, [
			'id' => 1,
			'name' => 'Acc',
			'datetime_settings' => (object) [
				'timezone' => 'Asia/Almaty',
				'timezone_offset' => '+05:00',
			],
			'_embedded' => (object) [
				'users_groups' => [],
				'task_types' => [],
			],
		]);

		$this->assertSame('Asia/Almaty', $api->timezone()->getName());
		// второй вызов не должен ходить в API
		$this->assertSame('Asia/Almaty', $api->timezone()->getName());
	}

	public function testTimezoneParamOverridesAccountSettings(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('timezone', 'UTC');

		$this->assertSame('UTC', $api->timezone()->getName());
	}

	public function testTimezoneFallsBackWhenAccountUnavailable(): void
	{
		$api = $this->makeStubApiClient();
		$storage = new AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);

		$api->pushResponse(401, ['title' => 'Unauthorized']);

		$this->assertSame($api::DEFAULT_TIMEZONE, $api->timezone()->getName());
	}
}
