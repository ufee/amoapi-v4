<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

/**
 * Логика CountingRedis: \Redis в памяти со счётчиками команд.
 * Значения сериализуются, как при OPT_SERIALIZER = SERIALIZER_PHP: каждое чтение — новый объект.
 */
trait CountingRedisStore
{
	/** @var array<string, string> */
	public $data = [];
	/** @var array<string, int> */
	public $calls = ['get' => 0, 'setEx' => 0, 'exists' => 0, 'del' => 0, 'scan' => 0, 'keys' => 0];
	/** @var array<int, string> */
	private $scanSnapshot = [];

	public function __construct()
	{
	}

	/** @return mixed */
	private function doGet($key)
	{
		$this->calls['get']++;
		return array_key_exists($key, $this->data) ? unserialize($this->data[$key]) : false;
	}

	private function doSetEx($key, $value): bool
	{
		$this->calls['setEx']++;
		$this->data[$key] = serialize($value);
		return true;
	}

	private function doExists(array $keys): int
	{
		$this->calls['exists']++;
		return count(array_intersect($keys, array_keys($this->data)));
	}

	private function doDel(array $keys): int
	{
		$this->calls['del']++;
		$n = 0;
		foreach ($keys as $k) {
			if (array_key_exists($k, $this->data)) {
				unset($this->data[$k]);
				$n++;
			}
		}
		return $n;
	}

	/**
	 * SCAN по одному ключу за вызов: проверяет обход по итератору до 0
	 * @return array<int, string>|false
	 */
	private function doScan(&$iterator, $pattern)
	{
		$this->calls['scan']++;
		if (!$iterator) {
			// снимок на старте обхода: удаление отданных ключей не сдвигает курсор, как в Redis
			$this->scanSnapshot = $this->match($pattern ?? '*');
		}
		$offset = (int) $iterator;
		$iterator = $offset + 1 < count($this->scanSnapshot) ? $offset + 1 : 0;
		return isset($this->scanSnapshot[$offset]) ? [$this->scanSnapshot[$offset]] : false;
	}

	/** @return array<int, string> */
	private function doKeys($pattern): array
	{
		$this->calls['keys']++;
		return $this->match($pattern);
	}

	/** @return array<int, string> */
	private function match(string $pattern): array
	{
		$regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
		return array_values(array_filter(array_keys($this->data), static function ($k) use ($regex) {
			return (bool) preg_match($regex, (string) $k);
		}));
	}
}
