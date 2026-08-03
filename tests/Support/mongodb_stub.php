<?php

declare(strict_types=1);

namespace MongoDB;

/**
 * Заглушка MongoDB\Collection для unit-тестов (если пакет/расширение не установлены).
 */
if (!class_exists(Collection::class, false)) {
	class Collection
	{
		/** @var array<string, array> */
		public $docs = [];

		/**
		 * @param array<string, mixed> $filter
		 * @return object|null
		 */
		public function findOne(array $filter)
		{
			$id = $filter['_id'] ?? null;
			if ($id === null || !isset($this->docs[$id])) {
				return null;
			}
			return (object) ['data' => (object) $this->docs[$id]];
		}

		/**
		 * @param array<string, mixed> $filter
		 * @param array<string, mixed> $update
		 * @param array<string, mixed> $options
		 * @return object
		 */
		public function updateOne(array $filter, array $update, array $options = [])
		{
			$id = $filter['_id'];
			$existed = isset($this->docs[$id]);
			$this->docs[$id] = $update['$set']['data'] ?? [];

			return new class ($existed) {
				/** @var bool */
				private $existed;

				public function __construct(bool $existed)
				{
					$this->existed = $existed;
				}

				public function getUpsertedCount(): int
				{
					return $this->existed ? 0 : 1;
				}

				public function getMatchedCount(): int
				{
					return $this->existed ? 1 : 0;
				}
			};
		}
	}
}
