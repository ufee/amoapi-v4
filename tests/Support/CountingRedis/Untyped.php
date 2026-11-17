<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

/** CountingRedis для заглушки \Redis и phpredis 5 */
class CountingRedis extends \Redis
{
	use CountingRedisStore;

	public function get($key)
	{
		return $this->doGet($key);
	}

	public function setEx($key, $ttl, $value)
	{
		return $this->doSetEx($key, $value);
	}

	public function exists($key, ...$other)
	{
		return $this->doExists(array_merge((array) $key, $other));
	}

	public function del($key, ...$other)
	{
		return $this->doDel(array_merge((array) $key, $other));
	}

	public function scan(&$iterator, $pattern = null, $count = 0, $type = null)
	{
		return $this->doScan($iterator, $pattern);
	}

	public function keys($pattern)
	{
		return $this->doKeys($pattern);
	}
}
