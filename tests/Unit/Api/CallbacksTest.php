<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Tests\TestCase;

class CallbacksTest extends TestCase
{
	public function testOnTriggerAndOff(): void
	{
		$api = $this->makeApiClient();
		$calls = 0;
		$api->callbacks->on('custom.event', function ($arg) use (&$calls) {
			$calls++;
			$this->assertSame('x', $arg);
			return true;
		});

		$this->assertTrue($api->callbacks->has('custom.event'));
		$this->assertTrue($api->callbacks->trigger('custom.event', 'x'));
		$this->assertSame(1, $calls);

		$api->callbacks->off('custom.event');
		$this->assertFalse($api->callbacks->has('custom.event'));
		$this->assertTrue($api->callbacks->trigger('custom.event', 'x'));
		$this->assertSame(1, $calls);
	}

	public function testTriggerStopsOnFalse(): void
	{
		$api = $this->makeApiClient();
		$order = [];
		$api->callbacks->on('stop.event', function () use (&$order) {
			$order[] = 1;
			return false;
		});
		$api->callbacks->on('stop.event', function () use (&$order) {
			$order[] = 2;
			return true;
		});

		$this->assertFalse($api->callbacks->trigger('stop.event'));
		$this->assertSame([1], $order);
	}

	public function testOnceRemovesAfterFirstCall(): void
	{
		$api = $this->makeApiClient();
		$calls = 0;
		$api->callbacks->once('once.event', function () use (&$calls) {
			$calls++;
		});

		$api->callbacks->trigger('once.event');
		$api->callbacks->trigger('once.event');
		$this->assertSame(1, $calls);
		$this->assertFalse($api->callbacks->has('once.event'));
	}
}
