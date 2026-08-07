<?php
/**
 * amoCRM API client Talks service
 */
namespace Ufee\AmoV4\Services;

class Talks extends Service
{
	/**
	 * Беседа в работе
	 */
	public const STATUS_IN_WORK = 'in_work';

	/**
	 * Беседа закрыта
	 */
	public const STATUS_CLOSED = 'closed';

	/**
	 * Отправка NPS-опроса клиенту запланирована
	 */
	public const STATUS_NPS_SCHEDULED = 'nps_scheduled';

	/**
	 * NPS-опрос отправлен клиенту, ожидается оценка
	 */
	public const STATUS_NPS_IN_PROGRESS = 'nps_in_progress';

	/**
	 * Беседа в работе, в процессе работы NPS-бота возникла ошибка
	 */
	public const STATUS_WITH_ERROR = 'with_error';

	/**
	 * Тип сущности — сделка
	 */
	public const ENTITY_LEAD = 'lead';

	/**
	 * Тип сущности — покупатель
	 */
	public const ENTITY_CUSTOMER = 'customer';

	protected $api_path = '/api/v4/talks';
	protected $entity_key = 'talks';

	protected $entity_model = '\Ufee\AmoV4\Models\Talk';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Talks';

	/**
	 * Все значения status
	 * @return string[]
	 */
	public static function statusValues(): array
	{
		return [
			self::STATUS_IN_WORK,
			self::STATUS_CLOSED,
			self::STATUS_NPS_SCHEDULED,
			self::STATUS_NPS_IN_PROGRESS,
			self::STATUS_WITH_ERROR,
		];
	}

	/**
	 * Все значения filter[entity_type]
	 * @return string[]
	 */
	public static function entityTypeValues(): array
	{
		return [
			self::ENTITY_LEAD,
			self::ENTITY_CUSTOMER,
		];
	}

	/**
	 * Find talks by id
	 * @param string|int|array $elem_id
	 * @param array $with
	 * @return \Ufee\AmoV4\Models\Talk|\Ufee\AmoV4\Collections\Talks|null
	 */
	public function find($elem_id, $with = [])
	{
		if (is_array($elem_id)) {
			return $this->filter(['talk_id' => $elem_id], $with)->fetchAll();
		}
		return parent::find($elem_id, $with);
	}

	/**
	 * Close talk by id (optionally force close without NPS bot)
	 * @param int $talk_id
	 * @param bool $force_close
	 * @return bool
	 */
	public function close(int $talk_id, bool $force_close = false): bool
	{
		if ($talk_id <= 0) {
			throw new \InvalidArgumentException('Talk ID must be positive integer');
		}
		$query = $this->instance->query('POST', $this->api_path . '/' . $talk_id . '/close');
		if ($force_close) {
			$query->setJsonData([
				'force_close' => true,
			]);
		}
		$query->execute();
		return $query->response->getCode() === 202;
	}

	/**
	 * Get talk messages service
	 * @param int $talk_id
	 * @return TalkMessages
	 */
	public function messages(int $talk_id): TalkMessages
	{
		if ($talk_id <= 0) {
			throw new \InvalidArgumentException('Talk ID must be positive integer');
		}
		return new TalkMessages($this->instance, [$talk_id]);
	}
}
