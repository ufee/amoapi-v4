<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

/** CountingRedis для phpredis 6+ (PHP 8): return types как у расширения */
class CountingRedis extends \Redis
{
	use CountingRedisStore;

	public function get($key): mixed
	{
		return $this->doGet($key);
	}

	public function setEx($key, $ttl, $value): mixed
	{
		return $this->doSetEx($key, $value);
	}

	public function exists($key, ...$other): \Redis|int|bool
	{
		return $this->doExists(array_merge((array) $key, $other));
	}

	public function del($key, ...$other): \Redis|int|false
	{
		return $this->doDel(array_merge((array) $key, $other));
	}

	public function scan(&$iterator, $pattern = null, $count = 0, $type = null): array|false
	{
		return $this->doScan($iterator, $pattern);
	}

	public function keys($pattern): mixed
	{
		return $this->doKeys($pattern);
	}
}
