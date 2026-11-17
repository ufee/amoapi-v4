<?php

declare(strict_types=1);

/**
 * Заглушка \Redis для unit-тестов (если расширение не установлено).
 */
if (!class_exists('Redis', false)) {
	class Redis
	{
		/** @var array<string, mixed> */
		public $data = [];

		/**
		 * @param string $key
		 * @return mixed
		 */
		public function get($key)
		{
			return array_key_exists($key, $this->data) ? $this->data[$key] : false;
		}

		/**
		 * @param string $key
		 * @param int $ttl
		 * @param mixed $value
		 */
		public function setEx($key, $ttl, $value)
		{
			$this->data[$key] = $value;
			return true;
		}

		/**
		 * @param string $key
		 */
		public function exists($key)
		{
			return array_key_exists($key, $this->data) ? 1 : 0;
		}

		/**
		 * @param string|array<int, string> $key
		 */
		public function del($key)
		{
			$n = 0;
			foreach ((array) $key as $k) {
				if (array_key_exists($k, $this->data)) {
					unset($this->data[$k]);
					$n++;
				}
			}
			return $n;
		}

		/** @var int SCAN-вызовов, для проверки, что clear() не использует KEYS */
		public $scans = 0;

		/**
		 * SCAN за один проход: итератор сразу 0
		 * @param int|null $iterator
		 * @param string|null $pattern
		 * @param int $count
		 * @return array<int, string>|false
		 */
		public function scan(&$iterator, $pattern = null, $count = 0)
		{
			$this->scans++;
			$iterator = 0;
			$keys = $this->keys($pattern ?? '*');
			return $keys === [] ? false : $keys;
		}

		/**
		 * @param string $pattern
		 * @return array<int, string>
		 */
		public function keys($pattern)
		{
			$regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
			return array_values(array_filter(array_keys($this->data), static function ($k) use ($regex) {
				return (bool) preg_match($regex, (string) $k);
			}));
		}
	}
}
