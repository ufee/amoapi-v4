<?php
/**
 * amoCRM API client Bots service
 */
namespace Ufee\AmoV4\Services;

class Bots extends Service
{
	/**
	 * Добавляет в ответ свойство is_favorite, определяющее, добавлен ли Salesbot в избранное у текущего пользователя аккаунта
	 */
	public const FAVORITE = 'favorite';

	/**
	 * Стандартный Salesbot без специализации
	 */
	public const TYPE_REGULAR = 'regular';

	/**
	 * Бот для отправки приветственных сообщений
	 */
	public const TYPE_GREETING = 'greeting';

	/**
	 * Бот для проведения рассылок
	 */
	public const TYPE_MARKETING = 'marketing';

	/**
	 * Бот для проведения NPS-опросов
	 */
	public const TYPE_NPS = 'nps';

	protected $api_path = '/api/v4/bots';
	protected $entity_key = 'items';

	protected $entity_model = '\Ufee\AmoV4\Models\Bot';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Bots';

	/** @var array */
	protected $allowed_run_entity_types = ['leads', 'contacts', 'customers'];

	/** @var array */
	protected $allowed_stop_entity_types = ['leads'];

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::FAVORITE,
		];
	}

	/**
	 * Все значения filter[type_functionality]
	 * @return string[]
	 */
	public static function typeFunctionalityValues(): array
	{
		return [
			self::TYPE_REGULAR,
			self::TYPE_GREETING,
			self::TYPE_MARKETING,
			self::TYPE_NPS,
		];
	}

	/**
	 * Установить все with-параметры обогащения ответа
	 * @return static
	 */
	public function withAll()
	{
		return $this->with(static::withValues());
	}

	/**
	 * Find bot by id
	 * @param int $bot_id
	 * @param array $with
	 * @return \Ufee\AmoV4\Models\Bot|null
	 */
	public function find($bot_id, $with = [])
	{
		if (!is_int($bot_id) || $bot_id <= 0) {
			throw new \InvalidArgumentException('Bot ID must be positive integer');
		}
		return parent::find($bot_id, $with);
	}

	/**
	 * Run bot (single) or bot tasks queue (group, max 100)
	 * @param array<int, array{bot_id:int, entity_id:int, entity_type:string}>|int $tasks_or_bot_id
	 * @param int|null $entity_id
	 * @param string $entity_type
	 */
	public function run($tasks_or_bot_id, ?int $entity_id = null, string $entity_type = 'leads'): bool
	{
		if (is_int($tasks_or_bot_id)) {
			if (is_null($entity_id) || $entity_id <= 0) {
				throw new \InvalidArgumentException('Entity ID must be positive integer');
			}
			$this->validateRunTask([
				'bot_id' => $tasks_or_bot_id,
				'entity_id' => $entity_id,
				'entity_type' => $entity_type,
			]);

			$query = $this->instance->query('POST', $this->api_path.'/'.$tasks_or_bot_id.'/run');
			$query->setJsonData([
				'entity_id' => $entity_id,
				'entity_type' => $entity_type,
			]);
			$query->execute();
			return $query->response->getCode() === 202;
		}

		if (!is_array($tasks_or_bot_id)) {
			throw new \InvalidArgumentException('Bots run expects tasks array or bot_id integer');
		}

		$tasks = $tasks_or_bot_id;
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
			throw new \InvalidArgumentException('Bots stop entity_type must be one of: leads');
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
