<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\Tests\TestCase;

class QueriesDelayTest extends TestCase
{
	public function testSubSecondDelayIsApplied(): void
	{
		$api = $this->makeApiClient();
		$api->setParam('query_delay', 0.3);

		$previous = new Query($api);
		$this->setStartTime($previous, microtime(true));
		$api->queries->pushQuery($previous);

		$query = new Query($api);
		$started = microtime(true);
		$api->callbacks->trigger('query.delay', $query);
		$elapsed = microtime(true) - $started;

		$this->assertGreaterThan(0, $query->sleep_time);
		$this->assertGreaterThanOrEqual(0.25, $elapsed);
	}

	private function setStartTime(Query $query, float $time): void
	{
		$property = new \ReflectionProperty(Query::class, 'attributes');
		$property->setAccessible(true);
		$attributes = $property->getValue($query);
		$attributes['start_time'] = $time;
		$property->setValue($query, $attributes);
	}
}
