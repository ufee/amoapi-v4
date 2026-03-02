<?php
/**
 * amoCRM API client Bots service
 */
namespace Ufee\AmoV4\Services;

class Bots extends Service
{
	protected $api_path = '/api/v4/bots';

	/** @var array */
	protected $allowed_run_entity_types = ['leads', 'contacts', 'customers'];

	/** @var array */
	protected $allowed_stop_entity_types = ['leads', 'customers'];

	/**
	 * Run bot tasks queue
	 * @param array<int, array{bot_id:int, entity_id:int, entity_type:string}>|int $tasks_or_bot_id
	 * @param int|null $entity_id
	 * @param string $entity_type
	 */
	public function run($tasks_or_bot_id, ?int $entity_id = null, string $entity_type = 'leads'): bool
	{
		if (is_int($tasks_or_bot_id)) {
			if (is_null($entity_id)) {
				throw new \InvalidArgumentException('Entity ID must be positive integer');
			}
			$tasks = [[
				'bot_id' => $tasks_or_bot_id,
				'entity_id' => $entity_id,
				'entity_type' => $entity_type
			]];
		}
		else if (is_array($tasks_or_bot_id)) {
			$tasks = $tasks_or_bot_id;
		}
		else {
			throw new \InvalidArgumentException('Bots run expects tasks array or bot_id integer');
		}

		if (empty($tasks)) {
			throw new \InvalidArgumentException('Bots run payload can not be empty');
		}
		if (count($tasks) > 100) {
			throw new \InvalidArgumentException('Bots run supports maximum 100 tasks per request');
		}

		foreach ($tasks as $task) {
			if (!is_array($task)) {
				throw new \InvalidArgumentException('Each bot task must be an array');
			}
			$this->validateRunTask($task);
		}

		$query = $this->instance->query('POST', $this->api_path.'/run');
		$query->setJsonData($tasks);
		$query->execute();
		return $query->response->getCode() === 202;
	}

	/**
	 * Stop bot by id for entity
	 * @param int $bot_id
	 * @param int $entity_id
	 * @param string $entity_type
	 */
	public function stop(int $bot_id, int $entity_id, string $entity_type = 'leads'): bool
	{
		if ($bot_id <= 0) {
			throw new \InvalidArgumentException('Bot ID must be positive integer');
		}
		if ($entity_id <= 0) {
			throw new \InvalidArgumentException('Entity ID must be positive integer');
		}
		if (!in_array($entity_type, $this->allowed_stop_entity_types, true)) {
			throw new \InvalidArgumentException('Bots stop entity_type must be one of: leads, customers');
		}

		$query = $this->instance->query('POST', $this->api_path.'/'.$bot_id.'/stop');
		$query->setJsonData([
			'entity_id' => $entity_id,
			'entity_type' => $entity_type
		]);
		$query->execute();
		return $query->response->getCode() === 202;
	}

	/**
	 * Validate one run task payload
	 * @param array{bot_id:int, entity_id:int, entity_type:string} $task
	 */
	protected function validateRunTask(array $task): void
	{
		if (empty($task['bot_id']) || !is_int($task['bot_id']) || $task['bot_id'] <= 0) {
			throw new \InvalidArgumentException('Bot task bot_id must be positive integer');
		}
		if (empty($task['entity_id']) || !is_int($task['entity_id']) || $task['entity_id'] <= 0) {
			throw new \InvalidArgumentException('Bot task entity_id must be positive integer');
		}
		if (empty($task['entity_type']) || !is_string($task['entity_type']) || !in_array($task['entity_type'], $this->allowed_run_entity_types, true)) {
			throw new \InvalidArgumentException('Bot task entity_type must be one of: leads, contacts, customers');
		}
	}
}
